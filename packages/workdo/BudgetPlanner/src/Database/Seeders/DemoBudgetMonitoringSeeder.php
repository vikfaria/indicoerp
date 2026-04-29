<?php

namespace Workdo\BudgetPlanner\Database\Seeders;

use Workdo\BudgetPlanner\Models\BudgetMonitoring;
use Workdo\BudgetPlanner\Models\Budget;
use Illuminate\Database\Seeder;

class DemoBudgetMonitoringSeeder extends Seeder
{
    public function run($userId): void
    {
        if (BudgetMonitoring::where('created_by', $userId)->exists()) {
            return;
        }

        $budget = Budget::where('created_by', $userId)->first();

        if (!$budget) {
            return;
        }

        $monitorings = [
            [
                'monitoring_date' => '2024-01-31',
                'total_allocated' => 50000.00,
                'total_spent' => 12000.00,
                'total_remaining' => 38000.00,
                'variance_amount' => -2000.00,
                'variance_percentage' => -4.00,
            ],
            [
                'monitoring_date' => '2024-02-29',
                'total_allocated' => 50000.00,
                'total_spent' => 25000.00,
                'total_remaining' => 25000.00,
                'variance_amount' => 0.00,
                'variance_percentage' => 0.00,
            ],
            [
                'monitoring_date' => '2024-03-31',
                'total_allocated' => 50000.00,
                'total_spent' => 42000.00,
                'total_remaining' => 8000.00,
                'variance_amount' => 5000.00,
                'variance_percentage' => 10.00,
            ],
            [
                'monitoring_date' => '2024-04-30',
                'total_allocated' => 50000.00,
                'total_spent' => 48500.00,
                'total_remaining' => 1500.00,
                'variance_amount' => 1500.00,
                'variance_percentage' => 3.00,
            ],
            [
                'monitoring_date' => '2024-05-31',
                'total_allocated' => 50000.00,
                'total_spent' => 50000.00,
                'total_remaining' => 0.00,
                'variance_amount' => 0.00,
                'variance_percentage' => 0.00,
            ],
        ];

        foreach ($monitorings as $monitoring) {
            BudgetMonitoring::create(array_merge($monitoring, [
                'budget_id' => $budget->id,
                'creator_id' => $userId,
                'created_by' => $userId,
            ]));
        }
    }
}