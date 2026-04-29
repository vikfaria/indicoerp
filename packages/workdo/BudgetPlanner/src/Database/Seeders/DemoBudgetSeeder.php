<?php

namespace Workdo\BudgetPlanner\Database\Seeders;

use Workdo\BudgetPlanner\Models\Budget;
use Workdo\BudgetPlanner\Models\BudgetPeriod;
use Illuminate\Database\Seeder;

class DemoBudgetSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Budget::where('created_by', $userId)->exists()) {
            return;
        }

        $budgetPeriod = BudgetPeriod::where('created_by', $userId)->first();

        if (!$budgetPeriod) {
            return;
        }

        $budgets = [
            [
                'budget_name' => 'Marketing Budget',
                'period_id' => $budgetPeriod->id,
                'budget_type' => 'operational',
                'total_budget_amount' => 50000.00,
                'status' => 'approved',
            ],
            [
                'budget_name' => 'IT Infrastructure',
                'period_id' => $budgetPeriod->id,
                'budget_type' => 'capital',
                'total_budget_amount' => 75000.00,
                'status' => 'active',
            ],
            [
                'budget_name' => 'HR Operations',
                'period_id' => $budgetPeriod->id,
                'budget_type' => 'operational',
                'total_budget_amount' => 30000.00,
                'status' => 'approved',
            ],
            [
                'budget_name' => 'Office Supplies',
                'period_id' => $budgetPeriod->id,
                'budget_type' => 'operational',
                'total_budget_amount' => 15000.00,
                'status' => 'draft',
            ],
            [
                'budget_name' => 'Research & Development',
                'period_id' => $budgetPeriod->id,
                'budget_type' => 'capital',
                'total_budget_amount' => 100000.00,
                'status' => 'active',
            ],
        ];

        foreach ($budgets as $budget) {
            Budget::create(array_merge($budget, [
                'approved_by' => \App\Models\User::where('created_by', $userId)->inRandomOrder()->first()?->id,
                'creator_id' => $userId,
                'created_by' => $userId,
            ]));
        }
    }
}
