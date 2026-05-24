<?php

namespace App\Services;

use App\Models\FiscalDocumentSeries;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Generates SHA-256 hash chains for fiscal documents.
 * Each document's hash includes the previous document's hash in the same series,
 * creating an immutable chain that detects tampering.
 *
 * Based on the Portuguese/Mozambican fiscal legislation approach (AT certification).
 */
class FiscalHashService
{
    /**
     * Generate a fiscal hash for a document and update the series chain.
     *
     * @param Model $document The document being hashed (invoice, credit note, etc.)
     * @param FiscalDocumentSeries $series The document series
     * @param int $sequence The sequence number within the series
     * @return array{hash: string, hash_control: string, sequence: int}
     */
    public function generateHash(Model $document, FiscalDocumentSeries $series, int $sequence): array
    {
        $previousHash = $series->last_hash ?? '';

        $payload = $this->buildCanonicalPayload($document, $series, $sequence, $previousHash);

        $hash = hash('sha256', $payload);

        // Hash control = first 4 chars (for printing on documents)
        $hashControl = substr($hash, 0, 4);

        // Update the series with the new hash
        $series->updateLastHash($hash);

        return [
            'hash' => $hash,
            'hash_control' => $hashControl,
            'sequence' => $sequence,
        ];
    }

    /**
     * Build the canonical payload for hashing.
     * Format: date;datetime;docNumber;total;previousHash
     *
     * The order and format are critical — any change breaks the chain.
     */
    public function buildCanonicalPayload(
        Model $document,
        FiscalDocumentSeries $series,
        int $sequence,
        string $previousHash
    ): string {
        $docDate = $this->resolveDocumentDate($document);
        $docDatetime = $this->resolveDocumentDatetime($document);
        $docNumber = $series->formatDocumentNumber($sequence);
        $total = $this->resolveDocumentTotal($document);

        return implode(';', [
            $docDate,                           // YYYY-MM-DD
            $docDatetime,                       // YYYY-MM-DDTHH:MM:SS
            $docNumber,                         // e.g. FT A/1
            number_format($total, 2, '.', ''),  // e.g. 1234.56
            $previousHash,                      // SHA-256 of previous doc or empty
        ]);
    }

    /**
     * Verify the hash chain integrity for a series within a date range.
     *
     * @return array{valid: bool, errors: array, checked: int}
     */
    public function verifyChain(int $seriesId, ?string $fromDate = null, ?string $toDate = null): array
    {
        $series = FiscalDocumentSeries::with('fiscalDocumentType')->findOrFail($seriesId);
        $tableName = $this->resolveDocumentTable($series->fiscalDocumentType->category);

        $query = DB::table($tableName)
            ->where('fiscal_series_id', $seriesId)
            ->whereNotNull('fiscal_hash')
            ->orderBy('document_sequence', 'asc');

        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('created_at', '<=', $toDate);
        }

        $documents = $query->get();

        $errors = [];
        $previousHash = '';
        $checked = 0;

        foreach ($documents as $document) {
            $checked++;
            $expectedPayload = implode(';', [
                $document->issue_date ?? $document->created_at,
                $document->created_at,
                $series->formatDocumentNumber($document->document_sequence),
                number_format($document->total ?? $document->amount ?? 0, 2, '.', ''),
                $previousHash,
            ]);

            $expectedHash = hash('sha256', $expectedPayload);

            if ($document->fiscal_hash !== $expectedHash) {
                $errors[] = [
                    'sequence' => $document->document_sequence,
                    'expected' => substr($expectedHash, 0, 8) . '...',
                    'found' => substr($document->fiscal_hash, 0, 8) . '...',
                    'message' => "Hash inválido no documento #{$document->document_sequence}",
                ];
            }

            $previousHash = $document->fiscal_hash;
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'checked' => $checked,
        ];
    }

    /**
     * Resolve the document date.
     */
    private function resolveDocumentDate(Model $document): string
    {
        foreach (['issue_date', 'invoice_date', 'journal_date', 'date', 'created_at'] as $field) {
            $value = $document->getAttribute($field);
            if ($value) {
                return $value instanceof \DateTimeInterface
                    ? $value->format('Y-m-d')
                    : date('Y-m-d', strtotime($value));
            }
        }

        return date('Y-m-d');
    }

    /**
     * Resolve the document datetime.
     */
    private function resolveDocumentDatetime(Model $document): string
    {
        $dt = $document->getAttribute('created_at') ?? now();

        return $dt instanceof \DateTimeInterface
            ? $dt->format('Y-m-d\TH:i:s')
            : date('Y-m-d\TH:i:s', strtotime($dt));
    }

    /**
     * Resolve the document total amount.
     */
    private function resolveDocumentTotal(Model $document): float
    {
        foreach (['grand_total', 'total', 'total_amount', 'amount', 'total_debit'] as $field) {
            $value = $document->getAttribute($field);
            if ($value !== null) {
                return (float) $value;
            }
        }

        return 0.0;
    }

    /**
     * Resolve the database table for a document category.
     */
    private function resolveDocumentTable(string $category): string
    {
        return match ($category) {
            'sales' => 'sales_invoices',
            'purchases' => 'purchase_invoices',
            'payments' => 'customer_payments',
            'movements' => 'delivery_notes',
            default => 'sales_invoices',
        };
    }
}
