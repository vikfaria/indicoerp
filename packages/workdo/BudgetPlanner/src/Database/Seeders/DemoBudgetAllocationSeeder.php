<?php

namespace Workdo\BudgetPlanner\Database\Seeders;

use Workdo\BudgetPlanner\Models\BudgetAllocation;
use Workdo\BudgetPlanner\Models\Budget;
use Workdo\Account\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class DemoBudgetAllocationSeeder extends Seeder
{
    public function run($userId): void
    {
        if (BudgetAllocation::where('created_by', $userId)->exists()) {
            return;
        }

        $budget = Budget::where('created_by', $userId)->first();
        $accounts = ChartOfAccount::where('created_by', $userId)->limit(5)->get();

        if (!$budget || $accounts->isEmpty()) {
            return;
        }

        $allocations = [
            [
                'allocated_amount' => 20000.00,
                'spent_amount' => 5000.00,
                'remaining_amount' => 15000.00,
            ],
            [
                'allocated_amount' => 15000.00,
                'spent_amount' => 2500.00,
                'remaining_amount' => 12500.00,
            ],
            [
                'allocated_amount' => 10000.00,
                'spent_amount' => 0.00,
                'remaining_amount' => 10000.00,
            ],
            [
                'allocated_amount' => 8000.00,
                'spent_amount' => 1200.00,
                'remaining_amount' => 6800.00,
            ],
            [
                'allocated_amount' => 12000.00,
                'spent_amount' => 3500.00,
                'remaining_amount' => 8500.00,
            ],
        ];

        foreach ($allocations as $index => $allocation) {
            $account = $accounts->get($index) ?? $accounts->first();

            BudgetAllocation::create(array_merge($allocation, [
                'budget_id' => $budget->id,
                'account_id' => $account->id,
                'creator_id' => $userId,
                'created_by' => $userId,
            ]));
        }
    }
}
