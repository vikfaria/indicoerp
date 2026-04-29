<?php

namespace Workdo\Performance\Database\Seeders;

use Workdo\Performance\Models\PerformanceEmployeeGoal;
use Workdo\Performance\Models\PerformanceGoalType;
use Illuminate\Database\Seeder;
use App\Models\User;
use Carbon\Carbon;

class DemoPerformanceEmployeeGoalSeeder extends Seeder
{
    public function run($userId): void
    {
        if (PerformanceEmployeeGoal::where('created_by', $userId)->exists()) {
            return;
        }

        if (!empty($userId)) {
            $goalTitles = [
                'Increase Sales Revenue by 25%', 'Complete Advanced Leadership Training', 'Reduce Customer Support Response Time',
                'Launch New Product Feature', 'Improve Code Quality Score', 'Mentor 3 Junior Team Members',
                'Achieve 95% Customer Satisfaction', 'Complete 40 Hours of Training', 'Reduce Project Delivery Time by 20%',
                'Implement 5 Process Improvements', 'Implement New CRM System', 'Complete Compliance Documentation',
                'Launch Marketing Campaign', 'Expand Client Portfolio by 15 Accounts', 'Develop Mobile Application',
                'Establish Strategic Partnership', 'Conduct Annual Team Building Event', 'Achieve 100% Compliance Rate',
                'Optimize Database Performance', 'Create Employee Handbook'
            ];

            $goalDescriptions = [
                'Achieve a 25% increase in quarterly sales revenue through new client acquisition and upselling',
                'Successfully complete the advanced leadership certification program',
                'Decrease average response time to customer inquiries from 6 hours to 2 hours',
                'Successfully design, develop, and launch the new analytics dashboard feature',
                'Increase code review rating from 7.5 to 9.0 through best practices implementation',
                'Provide structured mentorship to three junior developers throughout the quarter',
                'Maintain customer satisfaction rating above 95% for all handled cases',
                'Participate in professional development courses totaling 40 hours',
                'Optimize workflows to reduce average project delivery time by 20%',
                'Identify and implement five process improvements to increase team efficiency',
                'Deploy and configure new customer relationship management system',
                'Finalize all regulatory compliance documentation for annual audit',
                'Design and launch comprehensive digital marketing campaign',
                'Acquire 15 new client accounts through networking and outreach',
                'Create and deploy mobile application for customer self-service',
                'Negotiate and finalize strategic partnership with key industry player',
                'Organize and execute comprehensive team building activities',
                'Ensure all team processes meet regulatory compliance requirements',
                'Improve database query performance by 50% through optimization',
                'Develop comprehensive employee handbook and policies'
            ];

            // Create 25% distribution for each status (5 each)
            $statuses = array_merge(
                array_fill(0, 5, 'completed'),
                array_fill(0, 5, 'in_progress'),
                array_fill(0, 5, 'overdue'),
                array_fill(0, 5, 'not_started')
            );
            shuffle($statuses);

            $goalData = [];
            for ($i = 0; $i < 20; $i++) {
                $status = $statuses[$i];
                $progress = 0;
                $startDate = Carbon::now()->subDays(rand(30, 180));
                $endDate = Carbon::now()->addDays(rand(30, 180));

                switch ($status) {
                    case 'completed':
                        $progress = 100;
                        $endDate = Carbon::now()->subDays(rand(5, 30));
                        break;
                    case 'in_progress':
                        $progress = rand(25, 95);
                        break;
                    case 'overdue':
                        $progress = rand(20, 80);
                        $endDate = Carbon::now()->subDays(rand(5, 45));
                        break;
                    case 'not_started':
                        $progress = 0;
                        $startDate = Carbon::now()->addDays(rand(1, 30));
                        $endDate = Carbon::now()->addDays(rand(60, 300));
                        break;
                }

                $goalData[] = [
                    'title' => $goalTitles[$i],
                    'description' => $goalDescriptions[$i],
                    'target' => rand(1, 5) == 1 ? rand(50000, 500000) : rand(1, 100),
                    'progress' => $progress,
                    'status' => $status,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d')
                ];
            }

            $goalTypes = PerformanceGoalType::where('created_by', $userId)->where('status', 'active')->get();
            $users = User::emp()->where('created_by', $userId)->pluck('id')->toArray();
            
            if ($goalTypes->isEmpty() || empty($users)) {
                return;
            }

            foreach ($goalData as $goal) {
                PerformanceEmployeeGoal::create([
                    'employee_id' => $users[array_rand($users)],
                    'goal_type_id' => $goalTypes->random()->id,
                    'title' => $goal['title'],
                    'description' => $goal['description'],
                    'start_date' => $goal['start_date'],
                    'end_date' => $goal['end_date'],
                    'target' => $goal['target'],
                    'progress' => $goal['progress'],
                    'status' => $goal['status'],
                    'creator_id' => $userId,
                    'created_by' => $userId,
                ]);
            }
        }
    }
}