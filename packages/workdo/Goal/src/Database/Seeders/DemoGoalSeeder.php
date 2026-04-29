<?php

namespace Workdo\Goal\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Goal\Models\Goal;
use Workdo\Goal\Models\GoalCategory;

class DemoGoalSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Goal::where('created_by', $userId)->exists()) {
            return;
        }

        $financialCategory = GoalCategory::where('created_by', $userId)
                            ->where('category_code', 'FINANCE')
                            ->first();

        if (!$financialCategory) {
            return;
        }

        $goals = [
            [
                'goal_name' => 'Emergency Fund',
                'goal_description' => 'Build an emergency fund to cover 6 months of expenses',
                'goal_type' => 'savings',
                'target_amount' => 50000.00,
                'current_amount' => 15000.00,
                'start_date' => '2024-01-01',
                'target_date' => '2024-12-31',
                'priority' => 'high',
                'status' => 'draft',
            ],
            [
                'goal_name' => 'Investment Portfolio',
                'goal_description' => 'Diversify investment portfolio with stocks and bonds',
                'goal_type' => 'expense_reduction',
                'target_amount' => 100000.00,
                'current_amount' => 25000.00,
                'start_date' => '2024-02-01',
                'target_date' => '2025-02-01',
                'priority' => 'medium',
                'status' => 'active',
            ],
            [
                'goal_name' => 'Credit Card Debt',
                'goal_description' => 'Pay off all credit card debt completely',
                'goal_type' => 'debt_reduction',
                'target_amount' => 8000.00,
                'current_amount' => 3000.00,
                'start_date' => '2024-01-15',
                'target_date' => '2024-08-15',
                'priority' => 'critical',
                'status' => 'active',
            ],
            [
                'goal_name' => 'Annual Revenue Target',
                'goal_description' => 'Achieve annual revenue target for the business',
                'goal_type' => 'expense_reduction',
                'target_amount' => 500000.00,
                'current_amount' => 125000.00,
                'start_date' => '2024-01-01',
                'target_date' => '2024-12-31',
                'priority' => 'high',
                'status' => 'draft',
            ],
            [
                'goal_name' => 'Operational Cost Reduction',
                'goal_description' => 'Reduce monthly operational expenses by optimizing processes',
                'goal_type' => 'savings',
                'target_amount' => 12000.00,
                'current_amount' => 4000.00,
                'start_date' => '2024-03-01',
                'target_date' => '2024-09-01',
                'priority' => 'medium',
                'status' => 'active',
            ],
            [
                'goal_name' => 'Vacation Fund',
                'goal_description' => 'Save money for a family vacation to Europe',
                'goal_type' => 'savings',
                'target_amount' => 15000.00,
                'current_amount' => 8000.00,
                'start_date' => '2024-01-01',
                'target_date' => '2024-06-30',
                'priority' => 'low',
                'status' => 'draft',
            ],
            [
                'goal_name' => 'Retirement Savings',
                'goal_description' => 'Contribute to retirement fund for long-term financial security',
                'goal_type' => 'savings',
                'target_amount' => 200000.00,
                'current_amount' => 45000.00,
                'start_date' => '2024-01-01',
                'target_date' => '2026-12-31',
                'priority' => 'high',
                'status' => 'active',
            ],
            [
                'goal_name' => 'Home Down Payment',
                'goal_description' => 'Save for down payment on first home purchase',
                'goal_type' => 'savings',
                'target_amount' => 80000.00,
                'current_amount' => 20000.00,
                'start_date' => '2024-01-01',
                'target_date' => '2025-06-30',
                'priority' => 'high',
                'status' => 'draft',
            ],
            [
                'goal_name' => 'Student Loan Payoff',
                'goal_description' => 'Pay off remaining student loan balance',
                'goal_type' => 'debt_reduction',
                'target_amount' => 25000.00,
                'current_amount' => 10000.00,
                'start_date' => '2024-02-01',
                'target_date' => '2025-12-31',
                'priority' => 'medium',
                'status' => 'active',
            ],
            [
                'goal_name' => 'Business Expansion',
                'goal_description' => 'Generate additional revenue through business expansion',
                'goal_type' => 'expense_reduction',
                'target_amount' => 150000.00,
                'current_amount' => 150000.00,
                'start_date' => '2023-06-01',
                'target_date' => '2024-05-31',
                'priority' => 'high',
                'status' => 'completed',
            ],
        ];

        foreach ($goals as $goalData) {
            Goal::create(array_merge($goalData, [
                'category_id' => $financialCategory->id,
                'creator_id' => $userId,
                'created_by' => $userId,
            ]));
        }
    }
}
