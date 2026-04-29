<?php

namespace Workdo\Account\Observers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Workdo\Account\Models\JournalEntry;
use Workdo\Account\Models\MozFiscalClosing;

class JournalEntryObserver
{
    public function creating(JournalEntry $journalEntry): void
    {
        if (empty($journalEntry->created_by) || empty($journalEntry->journal_date)) {
            return;
        }

        if (!Schema::hasTable('mz_fiscal_closings')) {
            return;
        }

        try {
            $closedPeriod = MozFiscalClosing::query()
                ->where('created_by', $journalEntry->created_by)
                ->where('status', 'closed')
                ->whereDate('period_from', '<=', $journalEntry->journal_date)
                ->whereDate('period_to', '>=', $journalEntry->journal_date)
                ->orderByDesc('period_to')
                ->first();
        } catch (\Throwable) {
            return;
        }

        if (!$closedPeriod) {
            return;
        }

        throw ValidationException::withMessages([
            'journal_date' => __('The fiscal period is closed. Reopen the period before posting entries in this date range.'),
        ]);
    }
}

