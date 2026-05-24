<?php

namespace App\Observers;

use App\Models\FiscalDocumentSeries;
use App\Models\FiscalDocumentType;
use App\Services\FiscalHashService;
use App\Services\FiscalValidationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Observer that generates fiscal hash chains when documents are posted.
 * Attached to SalesInvoice, PurchaseInvoice, and return models.
 */
class FiscalDocumentObserver
{
    public function __construct(
        private readonly FiscalHashService $hashService,
        private readonly FiscalValidationService $validationService,
    ) {}

    /**
     * Handle the "updating" event.
     * When status transitions to "posted" or "approved", generate fiscal hash.
     */
    public function updating(Model $document): void
    {
        // Only act when status is changing to 'posted' or 'approved'
        if (!$document->isDirty('status') || !in_array($document->status, ['posted', 'approved'])) {
            return;
        }

        // Check if fiscal hash columns exist on this table
        if (!Schema::hasColumn($document->getTable(), 'fiscal_hash')) {
            return;
        }

        // Skip if hash already generated
        if (!empty($document->fiscal_hash)) {
            return;
        }

        try {
            $companyId = $document->created_by ?? creatorId();

            // 1. Validate period is open
            $date = $document->invoice_date ?? $document->issue_date ?? $document->credit_note_date ?? $document->debit_note_date ?? $document->created_at;
            if ($date) {
                $this->validationService->validatePeriodOpen($date, $companyId);
            }

            // 2. Resolve the document type and series
            $docTypeCode = $this->resolveDocumentTypeCode($document);
            $series = $this->resolveOrCreateSeries($companyId, $docTypeCode);

            if (!$series) {
                Log::warning("FiscalDocumentObserver: Could not resolve series for {$docTypeCode} company {$companyId}");
                return;
            }

            // 3. Get next sequence
            $sequence = $series->getNextSequence();

            // 4. Generate hash
            $hashResult = $this->hashService->generateHash($document, $series, $sequence);

            // 5. Set fiscal fields on the document
            $document->fiscal_hash = $hashResult['hash'];
            $document->fiscal_hash_control = $hashResult['hash_control'];
            $document->document_sequence = $sequence;
            $document->fiscal_series_id = $series->id;

            Log::info("FiscalDocumentObserver: Hash generated for {$docTypeCode} #{$sequence}", [
                'hash_control' => $hashResult['hash_control'],
                'series' => $series->series_code,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions so the controller catches them
            throw $e;
        } catch (\Throwable $e) {
            Log::error("FiscalDocumentObserver: Failed to generate hash", [
                'error' => $e->getMessage(),
                'document' => $document->getTable(),
                'id' => $document->id,
            ]);
        }
    }

    /**
     * Handle the "updated" event — prevent modification of posted/approved documents.
     */
    public function saving(Model $document): void
    {
        // If the document was already posted/approved and is being modified, validate mutability
        $originalStatus = $document->getOriginal('status');
        if (!$document->isDirty('status') && in_array($originalStatus, ['posted', 'approved'])) {
            // Allow balance_amount updates (payments) and fiscal_submission_status
            $allowedFields = [
                'balance_amount', 'fiscal_submission_status',
                'fiscal_submission_reference', 'fiscal_submission_at',
                'fiscal_submission_message', 'is_cancelled',
                'cancellation_reason', 'cancellation_date',
                'cancelled_by', 'rectification_reference',
            ];

            $dirty = array_keys($document->getDirty());
            $disallowed = array_diff($dirty, $allowedFields);

            if (!empty($disallowed)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'document' => __('Documento fiscal postado não pode ser alterado. Use nota de crédito/débito para correcções. Campos: :fields', [
                        'fields' => implode(', ', $disallowed),
                    ]),
                ]);
            }
        }
    }

    /**
     * Resolve the fiscal document type code from a model.
     */
    private function resolveDocumentTypeCode(Model $document): string
    {
        $table = $document->getTable();

        return match ($table) {
            'sales_invoices' => 'FT',
            'purchase_invoices' => 'FT', // Purchase invoices use same doc type but different series
            'sales_invoice_returns' => 'NC',
            'purchase_returns' => 'NC',
            'credit_notes' => 'NC',
            'debit_notes' => 'ND',
            default => 'FT',
        };
    }

    /**
     * Resolve or auto-create a document series for the company.
     */
    private function resolveOrCreateSeries(int $companyId, string $docTypeCode): ?FiscalDocumentSeries
    {
        $docType = FiscalDocumentType::where('code', $docTypeCode)
            ->where('is_active', true)
            ->first();

        if (!$docType) {
            return null;
        }

        $year = (int) date('Y');

        // Try to find an active series
        $series = FiscalDocumentSeries::where('company_id', $companyId)
            ->where('fiscal_document_type_id', $docType->id)
            ->where('fiscal_year', $year)
            ->where('is_active', true)
            ->first();

        if ($series) {
            return $series;
        }

        // Auto-create default series "A" for the current year
        return FiscalDocumentSeries::create([
            'company_id' => $companyId,
            'fiscal_document_type_id' => $docType->id,
            'series_code' => 'A',
            'fiscal_year' => $year,
            'last_sequence' => 0,
            'last_hash' => null,
            'is_active' => true,
            'valid_from' => "{$year}-01-01",
            'valid_to' => "{$year}-12-31",
            'created_by' => $companyId,
        ]);
    }
}
