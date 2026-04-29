<?php

namespace Workdo\Performance\Database\Seeders;

use Workdo\Performance\Models\PerformanceEmployeeReview;
use Workdo\Performance\Models\PerformanceReviewCycle;
use Workdo\Performance\Models\PerformanceIndicator;
use Illuminate\Database\Seeder;
use App\Models\User;
use Carbon\Carbon;

class DemoPerformanceEmployeeReviewSeeder extends Seeder
{
    public function run($userId): void
    {
        if (PerformanceEmployeeReview::where('created_by', $userId)->exists()) {
            return;
        }

        if (!empty($userId)) {
            $reviewCycles = PerformanceReviewCycle::where('created_by', $userId)->where('status', 'active')->get();
            $users = User::emp()->where('created_by', $userId)->pluck('id')->toArray();
            $indicators = PerformanceIndicator::where('created_by', $userId)->where('status', 'active')->get();

            if ($reviewCycles->isEmpty() || empty($users) || $indicators->isEmpty()) {
                return;
            }

            $reviewData = [
                ['pros' => 'Exceptional work quality and strong team collaboration', 'cons' => 'Could improve time management during peak periods', 'status' => 'completed', 'review_date' => Carbon::now()->subMonths(5)->format('Y-m-d'), 'completion_date' => Carbon::now()->subMonths(5)->addDays(7)->format('Y-m-d')],
                ['pros' => 'Highly productive and delivers results ahead of schedule', 'cons' => 'Needs to work on delegating tasks more effectively', 'status' => 'completed', 'review_date' => Carbon::now()->subMonths(4)->format('Y-m-d'), 'completion_date' => Carbon::now()->subMonths(4)->addDays(5)->format('Y-m-d')],
                ['pros' => 'Outstanding performance across all metrics', 'cons' => 'Sometimes takes on too much responsibility', 'status' => 'completed', 'review_date' => Carbon::now()->subMonths(3)->format('Y-m-d'), 'completion_date' => Carbon::now()->subMonths(3)->addDays(3)->format('Y-m-d')],
                ['pros' => 'Excellent interpersonal skills and team collaboration', 'cons' => 'Productivity could be improved with better prioritization', 'status' => 'completed', 'review_date' => Carbon::now()->subMonths(2)->format('Y-m-d'), 'completion_date' => Carbon::now()->subMonths(2)->addDays(8)->format('Y-m-d')],
                ['pros' => 'Top performer with excellent results', 'cons' => 'Could be more patient with slower team members', 'status' => 'completed', 'review_date' => Carbon::now()->subDays(30)->format('Y-m-d'), 'completion_date' => Carbon::now()->subDays(23)->format('Y-m-d')],
                ['pros' => 'Good communicator and team player with willingness to learn', 'cons' => 'Needs improvement in technical skills and meeting deadlines', 'status' => 'in_progress', 'review_date' => Carbon::now()->subDays(25)->format('Y-m-d'), 'completion_date' => null],
                ['pros' => 'Solid performer with consistent results', 'cons' => 'Could be more proactive in communication', 'status' => 'in_progress', 'review_date' => Carbon::now()->subDays(20)->format('Y-m-d'), 'completion_date' => null],
                ['pros' => 'High-quality work output with attention to detail', 'cons' => 'Could benefit from more cross-functional collaboration', 'status' => 'in_progress', 'review_date' => Carbon::now()->subDays(15)->format('Y-m-d'), 'completion_date' => null],
                ['pros' => 'Excellent team player and communicator', 'cons' => 'Could push for more challenging assignments', 'status' => 'in_progress', 'review_date' => Carbon::now()->subDays(10)->format('Y-m-d'), 'completion_date' => null],
                ['pros' => 'Reliable and consistent performer', 'cons' => 'Needs to develop critical thinking skills', 'status' => 'in_progress', 'review_date' => Carbon::now()->subDays(5)->format('Y-m-d'), 'completion_date' => null],
                ['pros' => null, 'cons' => null, 'status' => 'pending', 'review_date' => Carbon::now()->format('Y-m-d'), 'completion_date' => null],
                ['pros' => null, 'cons' => null, 'status' => 'pending', 'review_date' => Carbon::now()->addDays(5)->format('Y-m-d'), 'completion_date' => null],
                ['pros' => null, 'cons' => null, 'status' => 'pending', 'review_date' => Carbon::now()->addDays(10)->format('Y-m-d'), 'completion_date' => null],
                ['pros' => 'Shows improvement in productivity and meets requirements', 'cons' => 'Needs improvement in quality and communication skills', 'status' => 'cancelled', 'review_date' => Carbon::now()->subMonths(6)->format('Y-m-d'), 'completion_date' => null],
                ['pros' => 'Highly efficient and collaborative team member', 'cons' => 'Occasionally rushes through tasks affecting quality', 'status' => 'cancelled', 'review_date' => Carbon::now()->subMonths(5)->addDays(15)->format('Y-m-d'), 'completion_date' => null]
            ];

            // Shuffle the array to randomize status order
            shuffle($reviewData);

            foreach ($reviewData as $review) {
                // Generate ratings only for non-pending records
                $rating = [];
                if ($review['status'] !== 'pending' && $review['status'] !== 'cancelled') {
                    // Select 5-8 random indicators for each review
                    $selectedIndicators = $indicators->random(rand(5, min(8, $indicators->count())));

                    foreach ($selectedIndicators as $indicator) {
                        // Generate rating based on performance level
                        $ratingValue = match ($review['status']) {
                            'completed' => rand(4, 5), // Excellent performance
                            'in_progress' => rand(3, 4), // Good performance
                            default => rand(3, 5)
                        };

                        $rating[$indicator->id] = $ratingValue;
                    }
                }

                PerformanceEmployeeReview::create([
                    'user_id' => $users[array_rand($users)],
                    'reviewer_id' => $users[array_rand($users)],
                    'review_cycle_id' => $reviewCycles->random()->id,
                    'review_date' => $review['review_date'],
                    'completion_date' => $review['completion_date'],
                    'rating' => !empty($rating) ? json_encode($rating) : null,
                    'pros' => $review['pros'],
                    'cons' => $review['cons'],
                    'status' => $review['status'],
                    'creator_id' => $userId,
                    'created_by' => $userId,
                ]);
            }
        }
    }
}
