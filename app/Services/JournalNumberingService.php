<?php

namespace App\Services;

use App\Models\AccountingJournal;
use App\Models\AccountingPeriod;
use App\Models\JournalNumberSequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Workdo\Account\Models\JournalEntry;

/**
 * Generates sequential, gap-free journal entry numbers per company/fiscal year/journal.
 * Format: {PREFIX}/{YEAR}/{SEQUENCE} e.g. VD/2026/00001
 */
class JournalNumberingService
{
    /**
     * Generate the next journal number for a given context.
     * Uses a database lock to ensure uniqueness.
     */
    public function generateNumber(
        int $companyId,
        string $journalDate,
        ?int $accountingJournalId = null
    ): array {
        $year = date('Y', strtotime($journalDate));

        // Resolve prefix
        $prefix = 'JE'; // Default
        if ($accountingJournalId) {
            $journal = AccountingJournal::find($accountingJournalId);
            if ($journal) {
                $prefix = $journal->getPrefix();
            }
        }

        // Resolve accounting period
        $period = AccountingPeriod::forCompany($companyId)
            ->forDate($journalDate)
            ->first();

        $sequence = $this->allocateSequence(
            companyId: $companyId,
            fiscalYear: $year,
            prefix: $prefix,
            scopeKey: 'prefix:' . $prefix,
            accountingJournalId: $accountingJournalId
        );

        $journalNumber = sprintf('%s/%s/%05d', $prefix, $year, $sequence);

        return [
            'journal_number' => $journalNumber,
            'fiscal_year' => $year,
            'period_number' => $period?->period_number,
            'accounting_period_id' => $period?->id,
        ];
    }

    /**
     * Allocate the next sequence number for a scope in a way that survives
     * concurrent requests and company-level reuse of the same visible number.
     */
    private function allocateSequence(
        int $companyId,
        string $fiscalYear,
        string $prefix,
        string $scopeKey,
        ?int $accountingJournalId = null
    ): int {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return DB::transaction(function () use ($companyId, $fiscalYear, $prefix, $scopeKey, $accountingJournalId) {
                    $sequenceRow = JournalNumberSequence::query()
                        ->where('created_by', $companyId)
                        ->where('fiscal_year', $fiscalYear)
                        ->where('scope_key', $scopeKey)
                        ->where('prefix', $prefix)
                        ->lockForUpdate()
                        ->first();

                    if (!$sequenceRow) {
                        $sequenceRow = new JournalNumberSequence();
                        $sequenceRow->created_by = $companyId;
                        $sequenceRow->fiscal_year = $fiscalYear;
                        $sequenceRow->scope_key = $scopeKey;
                        $sequenceRow->prefix = $prefix;
                        $sequenceRow->last_sequence = $this->getCurrentSequenceSeed($companyId, $fiscalYear, $prefix, $accountingJournalId);
                        $sequenceRow->save();
                    }

                    $sequenceRow->last_sequence = (int) $sequenceRow->last_sequence + 1;
                    $sequenceRow->save();

                    return (int) $sequenceRow->last_sequence;
                });
            } catch (QueryException $exception) {
                if ($this->isDuplicateSequenceRowViolation($exception)) {
                    continue;
                }

                throw $exception;
            }
        }

        throw new \RuntimeException('Unable to allocate a journal number sequence safely.');
    }

    private function getCurrentSequenceSeed(
        int $companyId,
        string $fiscalYear,
        string $prefix,
        ?int $accountingJournalId = null
    ): int {
        $lastJournalNumber = JournalEntry::query()
            ->where('created_by', $companyId)
            ->where('fiscal_year', $fiscalYear)
            ->where('journal_number', 'like', $prefix . '/' . $fiscalYear . '/%')
            ->when($accountingJournalId, fn ($query) => $query->where('accounting_journal_id', $accountingJournalId))
            ->orderByDesc('id')
            ->value('journal_number');

        if (!is_string($lastJournalNumber) || $lastJournalNumber === '') {
            return 0;
        }

        return $this->extractSequenceFromJournalNumber($lastJournalNumber);
    }

    private function extractSequenceFromJournalNumber(string $journalNumber): int
    {
        if (preg_match('/(\d+)$/', $journalNumber, $matches)) {
            return max(0, (int) $matches[1]);
        }

        return 0;
    }

    private function isDuplicateSequenceRowViolation(QueryException $exception): bool
    {
        $errorInfo = $exception->errorInfo ?? [];
        $sqlState = $errorInfo[0] ?? $exception->getCode();
        $driverCode = $errorInfo[1] ?? null;

        return in_array((string) $sqlState, ['23000', '23505'], true)
            || $driverCode === 1062
            || str_contains($exception->getMessage(), 'Duplicate entry');
    }

    /**
     * Validate that the journal number follows the correct sequence.
     */
    public function validateSequence(string $journalNumber, int $companyId): bool
    {
        $parts = explode('/', $journalNumber);

        if (count($parts) !== 3) {
            return false;
        }

        [$prefix, $year, $seqStr] = $parts;
        $sequence = (int) $seqStr;

        if ($sequence <= 0) {
            return false;
        }

        if ($sequence === 1) {
            return true;
        }

        // Check previous exists
        $previousNumber = sprintf('%s/%s/%05d', $prefix, $year, $sequence - 1);

        return JournalEntry::where('created_by', $companyId)
            ->where('journal_number', $previousNumber)
            ->exists();
    }
}
