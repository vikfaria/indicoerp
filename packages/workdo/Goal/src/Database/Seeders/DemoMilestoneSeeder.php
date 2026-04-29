<?php

namespace Workdo\Goal\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Goal\Models\GoalMilestone;
use Workdo\Goal\Models\Goal;

class DemoMilestoneSeeder extends Seeder
{
    public function run($userId): void
    {
        if (GoalMilestone::where('created_by', $userId)->exists()) {
            return;
        }

        $goals = Goal::where('created_by', $userId)
                    ->where('status', 'active')
                    ->get();

        if ($goals->isEmpty()) {
            return;
        }

        $milestones = [
            [
                'milestone_name' => 'First Quarter Target',
                'milestone_description' => 'Achieve 25% of the annual goal target',
                'target_amount' => 25000.00,
                'achieved_amount' => 22000.00,
                'target_date' => now()->addMonths(3)->format('Y-m-d'),
                'achieved_date' => now()->subDays(5)->format('Y-m-d'),
                'status' => 'achieved',
            ],
            [
                'milestone_name' => 'Mid-Year Milestone',
                'milestone_description' => 'Reach 50% completion by mid-year',
                'target_amount' => 50000.00,
                'achieved_amount' => 0.00,
                'target_date' => now()->addMonths(6)->format('Y-m-d'),
                'achieved_date' => null,
                'status' => 'pending',
            ],
            [
                'milestone_name' => 'Third Quarter Goal',
                'milestone_description' => 'Complete 75% of annual target',
                'target_amount' => 75000.00,
                'achieved_amount' => 0.00,
                'target_date' => now()->addMonths(9)->format('Y-m-d'),
                'achieved_date' => null,
                'status' => 'pending',
            ],
            [
                'milestone_name' => 'Year-End Target',
                'milestone_description' => 'Achieve 100% of the annual goal',
                'target_amount' => 100000.00,
                'achieved_amount' => 0.00,
                'target_date' => now()->addYear()->format('Y-m-d'),
                'achieved_date' => null,
                'status' => 'pending',
            ],
            [
                'milestone_name' => 'Emergency Fund Setup',
                'milestone_description' => 'Build initial emergency fund',
                'target_amount' => 5000.00,
                'achieved_amount' => 5000.00,
                'target_date' => now()->subMonths(2)->format('Y-m-d'),
                'achieved_date' => now()->subMonths(1)->format('Y-m-d'),
                'status' => 'achieved',
            ],
            [
                'milestone_name' => 'Investment Portfolio',
                'milestone_description' => 'Start investment portfolio with initial amount',
                'target_amount' => 15000.00,
                'achieved_amount' => 15000.00,
                'target_date' => now()->subDays(30)->format('Y-m-d'),
                'achieved_date' => now()->subDays(25)->format('Y-m-d'),
                'status' => 'achieved',
            ],
        ];

        foreach ($milestones as $index => $milestone) {
            $goal = $goals->get($index % $goals->count());

            GoalMilestone::create(array_merge($milestone, [
                'goal_id' => $goal->id,
                'achieved_amount' => $milestone['achieved_amount'],
                'creator_id' => $userId,
                'created_by' => $userId,
            ]));
        }
    }
}
