<?php

namespace Workdo\BudgetPlanner\Database\Seeders;

use Workdo\BudgetPlanner\Models\BudgetPeriod;
use Illuminate\Database\Seeder;
use App\Models\Status;

class DemoBudgetPeriodSeeder extends Seeder
{
    public function run($userId): void
    {
        if (BudgetPeriod::where('created_by', $userId)->exists()) {
            return;
        }

        $budgetPeriods = [
            [
                'period_name' => 'Q1 2024 Budget',
                'financial_year' => '2024',
                'start_date' => '2024-01-01',
                'end_date' => '2024-03-31',
                'status' => 'active',
            ],
            [
                'period_name' => 'Q2 2024 Budget',
                'financial_year' => '2024',
                'start_date' => '2024-04-01',
                'end_date' => '2024-06-30',
                'status' => 'active',
            ],
            [
                'period_name' => 'Q3 2024 Budget',
                'financial_year' => '2024',
                'start_date' => '2024-07-01',
                'end_date' => '2024-09-30',
                'status' => 'active',
            ],
            [
                'period_name' => 'Q4 2024 Budget',
                'financial_year' => '2024',
                'start_date' => '2024-10-01',
                'end_date' => '2024-12-31',
                'status' => 'active',
            ],
            [
                'period_name' => 'Annual Budget 2024',
                'financial_year' => '2024',
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'status' => 'active',
            ],
        ];

        foreach ($budgetPeriods as $period) {
            BudgetPeriod::create(array_merge($period, [
                'approved_by' => \App\Models\User::where('created_by', $userId)->inRandomOrder()->first()?->id,
                'creator_id' => $userId,
                'created_by' => $userId,
            ]));
        }
    }
}
