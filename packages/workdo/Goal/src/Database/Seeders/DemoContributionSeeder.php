<?php

namespace Workdo\Goal\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Goal\Models\GoalContribution;
use Workdo\Goal\Models\Goal;

class DemoContributionSeeder extends Seeder
{
    public function run($userId): void
    {
        if (GoalContribution::where('created_by', $userId)->exists()) {
            return;
        }

        $goals = Goal::where('created_by', $userId)
                    ->where('status', 'active')
                    ->get();

        if ($goals->isEmpty()) {
            return;
        }

        $contributions = [
            [
                'contribution_date' => now()->subDays(30)->format('Y-m-d'),
                'contribution_amount' => 5000.00,
                'contribution_type' => 'manual',
                'reference_type' => 'manual',
                'reference_id' => null,
                'notes' => 'Initial contribution to start the goal',
            ],
            [
                'contribution_date' => now()->subDays(25)->format('Y-m-d'),
                'contribution_amount' => 2500.00,
                'contribution_type' => 'automatic',
                'reference_type' => 'bank_transaction',
                'reference_id' => 12345,
                'notes' => 'Monthly automatic transfer',
            ],
            [
                'contribution_date' => now()->subDays(20)->format('Y-m-d'),
                'contribution_amount' => 1000.00,
                'contribution_type' => 'automatic',
                'reference_type' => 'bank_transaction',
                'reference_id' => 67890,
                'notes' => 'Bonus allocation from quarterly performance',
            ],
            [
                'contribution_date' => now()->subDays(15)->format('Y-m-d'),
                'contribution_amount' => 3000.00,
                'contribution_type' => 'manual',
                'reference_type' => 'manual',
                'reference_id' => null,
                'notes' => 'Additional contribution from savings',
            ],
            [
                'contribution_date' => now()->subDays(10)->format('Y-m-d'),
                'contribution_amount' => 1500.00,
                'contribution_type' => 'automatic',
                'reference_type' => 'bank_transaction',
                'reference_id' => 54321,
                'notes' => 'Scheduled monthly contribution',
            ],
            [
                'contribution_date' => now()->subDays(5)->format('Y-m-d'),
                'contribution_amount' => 750.00,
                'contribution_type' => 'manual',
                'reference_type' => 'manual',
                'reference_id' => null,
                'notes' => 'Weekend side income contribution',
            ],
            [
                'contribution_date' => now()->subDays(2)->format('Y-m-d'),
                'contribution_amount' => 2000.00,
                'contribution_type' => 'automatic',
                'reference_type' => 'bank_transaction',
                'reference_id' => 98765,
                'notes' => 'Investment return allocation',
            ],
            [
                'contribution_date' => now()->format('Y-m-d'),
                'contribution_amount' => 500.00,
                'contribution_type' => 'automatic',
                'reference_type' => 'bank_transaction',
                'reference_id' => 11111,
                'notes' => 'Daily savings plan contribution',
            ],
        ];

        foreach ($contributions as $index => $contribution) {
            $goal = $goals->get($index % $goals->count());

            GoalContribution::create(array_merge($contribution, [
                'goal_id' => $goal->id,
                'creator_id' => $userId,
                'created_by' => $userId,
            ]));
        }
    }
}
