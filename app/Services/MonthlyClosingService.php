<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use App\Models\MonthlyClosingChecklist;
use Workdo\Account\Models\OpeningBalance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

        $this->assertFiscalYearNotFinalized($period);

        DB::transaction(function () use ($periodId, $companyId, $period): void {
            // Generate checklist
            MonthlyClosingChecklist::generateForPeriod($periodId, $companyId);
            $this->resetChecklist($periodId);

            $period->update(['status' => 'closing']);
        });

        return $period->fresh();
    }

    /**
     * Complete a checklist item.
     */
    public function completeCheckItem(int $checkId, ?string $notes = null): MonthlyClosingChecklist
    {
        $check = MonthlyClosingChecklist::findOrFail($checkId);
        $period = AccountingPeriod::findOrFail($check->accounting_period_id);

        if (!$period->isClosing()) {
            throw ValidationException::withMessages([
                'period' => __('O período deve estar em estado "em fecho" para alterar a checklist.'),
            ]);
        }

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
        $period = AccountingPeriod::findOrFail($check->accounting_period_id);

        if (!$period->isClosing()) {
            throw ValidationException::withMessages([
                'period' => __('O período deve estar em estado "em fecho" para alterar a checklist.'),
            ]);
        }

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
        $checklistQuery = MonthlyClosingChecklist::where('accounting_period_id', $periodId);
        $checklistItems = (clone $checklistQuery)
            ->orderBy('id')
            ->get()
            ->map(fn (MonthlyClosingChecklist $check): array => [
                'check_key' => $check->check_key,
                'check_name' => $check->check_name,
                'status' => $check->status,
                'completed_by' => $check->completed_by,
                'completed_at' => $check->completed_at?->toIso8601String(),
                'notes' => $check->notes,
            ])
            ->values()
            ->all();

        $checklistSummary = [
            'total' => (clone $checklistQuery)->count(),
            'completed' => (clone $checklistQuery)->where('status', 'completed')->count(),
            'skipped' => (clone $checklistQuery)->where('status', 'skipped')->count(),
            'pending' => (clone $checklistQuery)->where('status', 'pending')->count(),
        ];

        DB::transaction(function () use ($period, $snapshot, $checklistItems, $checklistSummary): void {
            $period->update([
                'status' => 'closed',
                'closed_by' => Auth::id(),
                'closed_at' => now(),
                'snapshot' => $snapshot,
                'close_checklist' => [
                    'generated_at' => now()->toIso8601String(),
                    'summary' => $checklistSummary,
                    'items' => $checklistItems,
                ],
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

        $this->assertFiscalYearNotFinalized($period);

        if (mb_strlen(trim($reason)) < 10) {
            throw ValidationException::withMessages([
                'reason' => __('O motivo de reabertura deve ter pelo menos 10 caracteres.'),
            ]);
        }

        DB::transaction(function () use ($period, $reason): void {
            $period->update([
                'status' => 'open',
                'reopen_reason' => $reason,
                'reopened_by' => Auth::id(),
                'reopened_at' => now(),
            ]);

            $this->resetChecklist($period->id);
        });

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

    private function resetChecklist(int $periodId): void
    {
        MonthlyClosingChecklist::where('accounting_period_id', $periodId)->update([
            'status' => 'pending',
            'completed_by' => null,
            'completed_at' => null,
            'notes' => null,
        ]);
    }

    private function assertFiscalYearNotFinalized(AccountingPeriod $period): void
    {
        $nextYear = (string) ((int) $period->fiscal_year + 1);

        if (OpeningBalance::query()
            ->where('financial_year', $nextYear)
            ->where('created_by', $period->company_id)
            ->exists()) {
            throw ValidationException::withMessages([
                'period' => __('O exercício já foi encerrado. A reabertura ou novo fecho deste período não é permitida.'),
            ]);
        }
    }
}
