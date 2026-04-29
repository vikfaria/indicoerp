<?php

namespace Workdo\Goal\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Goal\Models\GoalTracking;
use Workdo\Goal\Models\Goal;

class DemoTrackingSeeder extends Seeder
{
    public function run($userId): void
    {
        if (GoalTracking::where('created_by', $userId)->exists()) {
            return;
        }

        $goals = Goal::where('created_by', $userId)
                    ->where('status', 'active')
                    ->get();
        
        if ($goals->isEmpty()) {
            return;
        }

        $trackings = [
            [
                'tracking_date' => now()->subDays(30)->format('Y-m-d'),
                'previous_amount' => 0.00,
                'contribution_amount' => 5000.00,
                'current_amount' => 5000.00,
                'progress_percentage' => 5.00,
                'days_remaining' => 335,
                'projected_completion_date' => now()->addDays(400)->format('Y-m-d'),
                'on_track_status' => 'behind',
            ],
            [
                'tracking_date' => now()->subDays(25)->format('Y-m-d'),
                'previous_amount' => 5000.00,
                'contribution_amount' => 2500.00,
                'current_amount' => 7500.00,
                'progress_percentage' => 7.50,
                'days_remaining' => 330,
                'projected_completion_date' => now()->addDays(380)->format('Y-m-d'),
                'on_track_status' => 'behind',
            ],
            [
                'tracking_date' => now()->subDays(20)->format('Y-m-d'),
                'previous_amount' => 7500.00,
                'contribution_amount' => 1000.00,
                'current_amount' => 8500.00,
                'progress_percentage' => 8.50,
                'days_remaining' => 325,
                'projected_completion_date' => now()->addDays(370)->format('Y-m-d'),
                'on_track_status' => 'on_track',
            ],
            [
                'tracking_date' => now()->subDays(15)->format('Y-m-d'),
                'previous_amount' => 8500.00,
                'contribution_amount' => 3000.00,
                'current_amount' => 11500.00,
                'progress_percentage' => 11.50,
                'days_remaining' => 320,
                'projected_completion_date' => now()->addDays(350)->format('Y-m-d'),
                'on_track_status' => 'on_track',
            ],
            [
                'tracking_date' => now()->subDays(10)->format('Y-m-d'),
                'previous_amount' => 11500.00,
                'contribution_amount' => 1500.00,
                'current_amount' => 13000.00,
                'progress_percentage' => 13.00,
                'days_remaining' => 315,
                'projected_completion_date' => now()->addDays(340)->format('Y-m-d'),
                'on_track_status' => 'on_track',
            ],
            [
                'tracking_date' => now()->subDays(5)->format('Y-m-d'),
                'previous_amount' => 13000.00,
                'contribution_amount' => 750.00,
                'current_amount' => 13750.00,
                'progress_percentage' => 13.75,
                'days_remaining' => 310,
                'projected_completion_date' => now()->addDays(330)->format('Y-m-d'),
                'on_track_status' => 'ahead',
            ],
            [
                'tracking_date' => now()->subDays(2)->format('Y-m-d'),
                'previous_amount' => 13750.00,
                'contribution_amount' => 2000.00,
                'current_amount' => 15750.00,
                'progress_percentage' => 15.75,
                'days_remaining' => 307,
                'projected_completion_date' => now()->addDays(320)->format('Y-m-d'),
                'on_track_status' => 'ahead',
            ],
            [
                'tracking_date' => now()->format('Y-m-d'),
                'previous_amount' => 15750.00,
                'contribution_amount' => 500.00,
                'current_amount' => 16250.00,
                'progress_percentage' => 16.25,
                'days_remaining' => 305,
                'projected_completion_date' => now()->addDays(315)->format('Y-m-d'),
                'on_track_status' => 'ahead',
            ],
        ];

        foreach ($trackings as $index => $tracking) {
            $goal = $goals->get($index % $goals->count());
            
            GoalTracking::create(array_merge($tracking, [
                'goal_id' => $goal->id,
                'creator_id' => $userId,
                'created_by' => $userId,
            ]));
        }
    }
}