<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use App\Models\MonthlyClosingChecklist;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Workdo\Account\Models\JournalEntry;

/**
 * Manages monthly and annual closing processes for SCE Moçambique compliance.
 */
class MonthlyClosingService
{
    public function __construct(
        private readonly FiscalValidationService $fiscalValidation,
    ) {}

    /**
     * Initiate the closing process for a period.
     * Creates the checklist and sets period status to 'closing'.
     */
    public function initiateClosing(int $periodId, int $companyId): AccountingPeriod
    {
        $period = AccountingPeriod::where('id', $periodId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        if (!$period->isOpen()) {
            throw ValidationException::withMessages([
                'period' => __('Apenas períodos abertos podem iniciar o processo de fecho.'),
            ]);
        }

        // Generate checklist
        MonthlyClosingChecklist::generateForPeriod($periodId, $companyId);

        $period->update(['status' => 'closing']);

        return $period->fresh();
    }

    /**
     * Complete a checklist item.
     */
    public function completeCheckItem(int $checkId, ?string $notes = null): MonthlyClosingChecklist
    {
        $check = MonthlyClosingChecklist::findOrFail($checkId);

        $check->update([
            'status' => 'completed',
            'completed_by' => Auth::id(),
            'completed_at' => now(),
            'notes' => $notes,
        ]);

        return $check;
    }

    /**
     * Skip a checklist item (with reason).
     */
    public function skipCheckItem(int $checkId, string $reason): MonthlyClosingChecklist
    {
        $check = MonthlyClosingChecklist::findOrFail($checkId);

        $check->update([
            'status' => 'skipped',
            'completed_by' => Auth::id(),
            'completed_at' => now(),
            'notes' => $reason,
        ]);

        return $check;
    }

    /**
     * Finalize the period closing.
     * All checklist items must be completed/skipped.
     */
    public function finalizePeriodClosing(int $periodId, int $companyId): AccountingPeriod
    {
        $period = AccountingPeriod::where('id', $periodId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        if (!$period->isClosing()) {
            throw ValidationException::withMessages([
                'period' => __('O período deve estar em estado "em fecho" para ser finalizado.'),
            ]);
        }

        // Check that all checklist items are complete
        if (!MonthlyClosingChecklist::isChecklistComplete($periodId)) {
            $pending = MonthlyClosingChecklist::where('accounting_period_id', $periodId)
                ->where('status', 'pending')
                ->pluck('check_name')
                ->implode(', ');

            throw ValidationException::withMessages([
                'checklist' => __('Itens pendentes na checklist: :items', ['items' => $pending]),
            ]);
        }

        // Create snapshot of period balances
        $snapshot = $this->createPeriodSnapshot($period);

        DB::transaction(function () use ($period, $snapshot) {
            $period->update([
                'status' => 'closed',
                'closed_by' => Auth::id(),
                'closed_at' => now(),
                'snapshot' => $snapshot,
            ]);
        });

        return $period->fresh();
    }

    /**
     * Reopen a closed period (requires elevated permissions).
     */
    public function reopenPeriod(int $periodId, int $companyId, string $reason): AccountingPeriod
    {
        $period = AccountingPeriod::where('id', $periodId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        if (!$period->isClosed()) {
            throw ValidationException::withMessages([
                'period' => __('Apenas períodos fechados podem ser reabertos.'),
            ]);
        }

        if (mb_strlen(trim($reason)) < 10) {
            throw ValidationException::withMessages([
                'reason' => __('O motivo de reabertura deve ter pelo menos 10 caracteres.'),
            ]);
        }

        $period->update([
            'status' => 'open',
            'reopen_reason' => $reason,
            'reopened_by' => Auth::id(),
            'reopened_at' => now(),
        ]);

        return $period->fresh();
    }

    /**
     * Get closing status summary for a period.
     */
    public function getClosingStatus(int $periodId): array
    {
        $period = AccountingPeriod::findOrFail($periodId);

        $checklist = MonthlyClosingChecklist::where('accounting_period_id', $periodId)
            ->get();

        $total = $checklist->count();
        $completed = $checklist->where('status', 'completed')->count();
        $skipped = $checklist->where('status', 'skipped')->count();
        $pending = $checklist->where('status', 'pending')->count();

        return [
            'period' => $period,
            'checklist' => $checklist,
            'summary' => [
                'total' => $total,
                'completed' => $completed,
                'skipped' => $skipped,
                'pending' => $pending,
                'progress_percent' => $total > 0 ? round(($completed + $skipped) / $total * 100) : 0,
                'can_finalize' => $pending === 0 && $total > 0,
            ],
        ];
    }

    /**
     * Create a balance snapshot for the period.
     */
    private function createPeriodSnapshot(AccountingPeriod $period): array
    {
        $totals = DB::table('journal_entries as je')
            ->join('journal_entry_items as jei', 'je.id', '=', 'jei.journal_entry_id')
            ->where('je.created_by', $period->company_id)
            ->where('je.status', 'posted')
            ->whereBetween('je.journal_date', [$period->start_date, $period->end_date])
            ->selectRaw('SUM(jei.debit_amount) as total_debits, SUM(jei.credit_amount) as total_credits, COUNT(DISTINCT je.id) as entry_count')
            ->first();

        return [
            'total_debits' => (float) ($totals->total_debits ?? 0),
            'total_credits' => (float) ($totals->total_credits ?? 0),
            'entry_count' => (int) ($totals->entry_count ?? 0),
            'closed_at' => now()->toIso8601String(),
            'closed_by' => Auth::id(),
        ];
    }
}
