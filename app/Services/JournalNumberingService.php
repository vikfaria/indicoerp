<?php

namespace App\Services;

use App\Models\AccountingJournal;
use App\Models\AccountingPeriod;
use Illuminate\Support\Facades\DB;
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

        // Get next sequence within lock
        $sequence = DB::transaction(function () use ($companyId, $year, $accountingJournalId, $prefix) {
            // Lock for update to prevent race conditions
            $lastEntry = JournalEntry::where('created_by', $companyId)
                ->where('fiscal_year', $year)
                ->when($accountingJournalId, fn ($q) => $q->where('accounting_journal_id', $accountingJournalId))
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            if ($lastEntry && $lastEntry->journal_number) {
                // Extract sequence from existing format PREFIX/YEAR/SEQUENCE
                $parts = explode('/', $lastEntry->journal_number);
                $lastSeq = (int) ($parts[2] ?? 0);
                return $lastSeq + 1;
            }

            // Fallback: count existing entries
            $count = JournalEntry::where('created_by', $companyId)
                ->where('fiscal_year', $year)
                ->when($accountingJournalId, fn ($q) => $q->where('accounting_journal_id', $accountingJournalId))
                ->count();

            return $count + 1;
        });

        $journalNumber = sprintf('%s/%s/%05d', $prefix, $year, $sequence);

        return [
            'journal_number' => $journalNumber,
            'fiscal_year' => $year,
            'period_number' => $period?->period_number,
            'accounting_period_id' => $period?->id,
        ];
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
