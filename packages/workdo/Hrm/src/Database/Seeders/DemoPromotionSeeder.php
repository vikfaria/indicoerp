<?php

namespace Workdo\Hrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\Promotion;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;
use Workdo\Hrm\Models\Designation;
use App\Models\User;
use Carbon\Carbon;

class DemoPromotionSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Promotion::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        
        if (!empty($userId)) {
            $employees = User::whereIn('id', Employee::where('created_by', $userId)->pluck('user_id'))
                ->where('created_by', $userId)
                ->pluck('id')
                ->toArray();

            // Get branch-department-designation relationships
            $branchDeptDesig = [];
            $departments = Department::where('created_by', $userId)->get();
            foreach ($departments as $dept) {
                $designations = Designation::where('department_id', $dept->id)
                    ->where('branch_id', $dept->branch_id)
                    ->where('created_by', $userId)
                    ->pluck('id')
                    ->toArray();
                if (!empty($designations)) {
                    $branchDeptDesig[] = [
                        'branch_id' => $dept->branch_id,
                        'department_id' => $dept->id,
                        'designation_ids' => $designations
                    ];
                }
            }

            $approvers = User::where('created_by', $userId)->pluck('id')->toArray();

            if (empty($employees) || empty($branchDeptDesig)) {
                return;
            }

            $reasons = [
                'Outstanding performance excellence and exceptional leadership skills demonstrated consistently over the past evaluation period.',
                'Promotion to senior position based on exceptional project delivery and effective team management capabilities.',
                'Career advancement opportunity with increased responsibilities and strategic leadership role in department.',
                'Recognition of technical expertise and significant contribution to company growth and innovation initiatives.',
                'Exceptional customer service performance and ability to handle complex client relationships effectively.',
                'Demonstrated strong analytical skills and process improvement initiatives that enhanced operational efficiency.',
                'Leadership potential recognized through successful mentoring of junior staff and cross-functional collaboration.',
                'Outstanding sales performance exceeding targets and building strong customer relationships consistently.',
                'Innovation and creativity in problem-solving that resulted in cost savings and improved productivity.',
                'Excellent communication skills and ability to manage stakeholder relationships at all organizational levels.',
                'Consistent professional development and acquisition of new skills relevant to advanced responsibilities.',
                'Strong project management capabilities and successful delivery of critical business initiatives on time.',
                'Exceptional quality assurance performance and commitment to maintaining highest standards of work.',
                'Demonstrated ability to adapt to changing business requirements and lead organizational transformation.',
                'Outstanding training and development contribution through knowledge sharing and skill transfer programs.',
                'Strategic thinking and planning capabilities that contributed to long-term business success.',
                'Excellent financial management skills and cost optimization initiatives that improved departmental performance.',
                'Strong compliance and governance adherence ensuring regulatory requirements are consistently met.',
                'Exceptional crisis management skills demonstrated during challenging situations and emergency responses.',
                'Outstanding vendor management and negotiation skills that resulted in favorable business agreements.',
                'Demonstrated commitment to diversity and inclusion initiatives creating positive workplace culture.',
                'Excellent research and development contribution leading to breakthrough innovations and patents.',
                'Strong digital transformation leadership driving technology adoption and modernization initiatives.',
                'Outstanding environmental sustainability efforts promoting green practices and corporate social responsibility.',
                'Exceptional data analytics skills providing insights that drove informed business decision-making processes.',
                'Strong change management leadership successfully guiding organizational adaptation and employee engagement.',
                'Outstanding international business development expanding company presence in global markets.',
                'Excellent supply chain optimization resulting in improved efficiency and reduced operational costs.',
                'Demonstrated expertise in emerging technologies and successful implementation of innovative solutions.',
                'Outstanding community engagement representing company values and building positive public relations.'
            ];

            // Create status array with realistic distribution (18 approved, 9 pending, 3 rejected)
            $statusArray = array_merge(
                array_fill(0, 18, 'approved'),
                array_fill(0, 9, 'pending'),
                array_fill(0, 3, 'rejected')
            );
            shuffle($statusArray);

            $promotions = [];
            for ($i = 0; $i < 30; $i++) {
                $effectiveDaysAgo = 175 - ($i * 5);
                $createdDaysAgo = $effectiveDaysAgo - 3;

                // Get previous position (proper branch-dept-designation match)
                $prevIndex = $i % count($branchDeptDesig);
                $prevBDD = $branchDeptDesig[$prevIndex];
                $prevDesignationId = $prevBDD['designation_ids'][array_rand($prevBDD['designation_ids'])];

                // Get current position (different from previous)
                $currIndex = ($i + 1) % count($branchDeptDesig);
                $currBDD = $branchDeptDesig[$currIndex];
                $currDesignationId = $currBDD['designation_ids'][array_rand($currBDD['designation_ids'])];

                $status = $statusArray[$i];

                // For approved promotions, set approved_by
                $approvedBy = ($status === 'approved') ? $approvers[array_rand($approvers)] : null;

                // Generate document name for some promotions
                $document = (rand(0, 1)) ? 'promotion' . rand(1, 4) . '.png' : null;

                $promotions[] = [
                    'employee_id' => $employees[$i % count($employees)],
                    'previous_branch_id' => $prevBDD['branch_id'],
                    'previous_department_id' => $prevBDD['department_id'],
                    'previous_designation_id' => $prevDesignationId,
                    'current_branch_id' => $currBDD['branch_id'],
                    'current_department_id' => $currBDD['department_id'],
                    'current_designation_id' => $currDesignationId,
                    'effective_date' => Carbon::now()->subDays($effectiveDaysAgo)->format('Y-m-d'),
                    'reason' => $reasons[$i % count($reasons)],
                    'status' => $status,
                    'document' => $document,
                    'approved_by' => $approvedBy,
                    'created_at' => Carbon::now()->subDays($createdDaysAgo)->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                    'updated_at' => Carbon::now()->subDays($createdDaysAgo)->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                ];
            }

            foreach ($promotions as $promotion) {
                Promotion::updateOrCreate(
                    [
                        'employee_id' => $promotion['employee_id'],
                        'effective_date' => $promotion['effective_date'],
                        'created_by' => $userId
                    ],
                    array_merge($promotion, [
                        'creator_id' => $userId,
                        'created_by' => $userId,
                    ])
                );
            }
        }
    }
}