<?php

namespace Workdo\BudgetPlanner\Services;

use Illuminate\Support\Facades\Auth;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\JournalEntryItem;
use Workdo\BudgetPlanner\Models\Budget;
use Workdo\BudgetPlanner\Models\BudgetAllocation;
use Workdo\BudgetPlanner\Models\BudgetMonitoring;

class BudgetService
{
    public function updateBudgetSpendingForAccounts($journalEntry)
    {
        $affectedAccounts = $journalEntry->items->pluck('account_id')->unique();

        foreach($affectedAccounts as $accountId) {
            $activeBudgets = Budget::where('status', 'active')
                ->whereHas('allocations', function($q) use ($accountId) {
                    $q->where('account_id', $accountId);
                })->get();
            if ($activeBudgets->isEmpty()) {
                continue;
            }

            foreach($activeBudgets as $budget) {
                $this->updateBudgetSpending($budget->id);
            }
        }
    }

    public function updateBudgetSpending($budgetId)
    {
        $budget = Budget::with('budgetPeriod')->find($budgetId);
        $period = $budget->budgetPeriod;

        // Get all allocations for this budget
        $allocations = BudgetAllocation::where('budget_id', $budgetId)->get();
        foreach($allocations as $allocation) {
            // Calculate actual spending from journal entries
            $actualSpent = $this->calculateActualSpending(
                $allocation->account_id,
                $period->start_date,
                $period->end_date
            );
            // Update allocation amounts
            $allocation->spent_amount = $actualSpent;
            $allocation->remaining_amount = $allocation->allocated_amount - $actualSpent;
            $allocation->save();
        }

        // Create budget monitoring record
        $this->createBudgetMonitoring($budgetId);

        return true;
    }


    public function calculateActualSpending($accountId, $startDate, $endDate) {
        $account = ChartOfAccount::find($accountId);

        if (!$account) {
            return 0;
        }

        $startDate = $startDate->format('Y-m-d');
        $endDate = $endDate->format('Y-m-d');
        $items = JournalEntryItem::where('account_id', $accountId)
            ->whereHas('journalEntry', function($q) use ($startDate, $endDate) {
                $q->whereBetween('journal_date', [$startDate, $endDate])
                  ->where('status', 'posted');
            })->get();

        $totalDebit = $items->sum('debit_amount');
        $totalCredit = $items->sum('credit_amount');

        if ($account->normal_balance === 'debit') {
            $actualSpent = $totalDebit - $totalCredit;
        } else {
            $actualSpent = $totalCredit - $totalDebit;
        }
        return max(0, $actualSpent);
    }


    public function createBudgetMonitoring($budgetId) {
        $budget = Budget::find($budgetId);
        $allocations = $budget->allocations;
        // Calculate totals
        $totalAllocated = $allocations->sum('allocated_amount');
        $totalSpent = $allocations->sum('spent_amount');
        $totalRemaining = $allocations->sum('remaining_amount');
        $varianceAmount = $totalAllocated - $totalSpent;
        $variancePercentage = $totalAllocated > 0 ? ($varianceAmount / $totalAllocated) * 100 : 0;

        // Create monitoring record
        $monitoring = new BudgetMonitoring();
        $monitoring->budget_id = $budgetId;
        $monitoring->monitoring_date = now();
        $monitoring->total_allocated = $totalAllocated;
        $monitoring->total_spent = $totalSpent;
        $monitoring->total_remaining = $totalRemaining;
        $monitoring->variance_amount = $varianceAmount;
        $monitoring->variance_percentage = $variancePercentage;
        $monitoring->creator_id = Auth::id();
        $monitoring->created_by = creatorId();
        $monitoring->save();

        return $monitoring->id;
    }

}
