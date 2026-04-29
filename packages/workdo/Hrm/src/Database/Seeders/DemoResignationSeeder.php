<?php

namespace Workdo\Hrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\Resignation;
use Workdo\Hrm\Models\Employee;
use App\Models\User;
use Carbon\Carbon;

class DemoResignationSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Resignation::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        
        if (!empty($userId)) {
            $employees = User::whereIn('id', Employee::where('created_by', $userId)->pluck('user_id'))
                ->where('created_by', $userId)
                ->pluck('id')
                ->toArray();

            $approvers = User::where('created_by', $userId)->pluck('id')->toArray();

            if (empty($employees)) {
                return;
            }

            $reasons = [
                'Career advancement opportunity with better growth prospects and professional development in new organization.',
                'Personal relocation due to family circumstances requiring immediate move to different geographical location.',
                'Higher compensation package offered by competitor company with enhanced benefits and work-life balance.',
                'Pursuing advanced education and professional certification requiring full-time commitment and dedicated focus.',
                'Health concerns necessitating reduced work stress and flexible schedule not available in current position.',
                'Entrepreneurial venture launch requiring complete dedication and focus on building new business from ground up.',
                'Family responsibilities including elderly care and childcare obligations demanding more flexible working arrangements.',
                'Career change to different industry sector aligning with long-term professional goals and personal interests.',
                'Work-life balance improvement seeking position with reduced travel requirements and better schedule flexibility.',
                'Professional growth stagnation prompting search for challenging role with advancement opportunities and skill development.',
                'Spouse job transfer requiring family relocation to different city making current position geographically unfeasible.',
                'Better work environment and company culture fit found in new organization with improved team dynamics.',
                'Compensation disparity addressed through new position offering competitive salary and comprehensive benefits package.',
                'Remote work opportunity providing flexibility and eliminating daily commute while maintaining professional growth trajectory.',
                'Industry specialization focus requiring transition to company with specific domain expertise and technical resources.',
                'Management style differences creating workplace tension and hindering professional satisfaction and career progression.',
                'Educational pursuit including graduate degree program requiring flexible schedule and reduced work commitment.',
                'Startup opportunity offering equity participation and innovative work environment with cutting-edge technology projects.',
                'Retirement planning transition involving gradual reduction of work responsibilities and preparation for future lifestyle.',
                'International assignment opportunity providing global exposure and cross-cultural experience in multinational organization.',
                'Creative industry transition pursuing passion project with artistic fulfillment and personal satisfaction priorities.',
                'Consulting career launch leveraging accumulated expertise and industry knowledge for independent professional practice.',
                'Non-profit sector transition aligning with personal values and social impact goals for meaningful contribution.',
                'Technology industry shift seeking exposure to emerging technologies and digital transformation initiatives.',
                'Leadership role opportunity offering increased responsibility and strategic decision-making authority in growing company.',
                'Work schedule conflict resolution through position offering better alignment with personal commitments and lifestyle.',
                'Professional network expansion through role in larger organization with broader industry connections and opportunities.',
                'Skill diversification pursuit requiring exposure to different business functions and cross-functional collaboration experience.',
                'Company culture mismatch resolution through transition to organization with better alignment of values and practices.',
                'Career pivot opportunity leveraging transferable skills in new industry sector with growth potential and innovation.'
            ];

            // Create status array with realistic distribution (18 accepted, 9 pending, 3 rejected)
            $statusArray = array_merge(
                array_fill(0, 18, 'accepted'),
                array_fill(0, 9, 'pending'),
                array_fill(0, 3, 'rejected')
            );
            shuffle($statusArray);

            $resignations = [];
            for ($i = 0; $i < 30; $i++) {
                $resignationDaysAgo = 175 - ($i * 5);
                $createdDaysAgo = $resignationDaysAgo - 2;
                $lastWorkingDaysAgo = max(0, $resignationDaysAgo - 30); // 30 days notice period
                
                $status = $statusArray[$i];
                
                // For accepted resignations, set approved_by
                $approvedBy = ($status === 'accepted') ? $approvers[array_rand($approvers)] : null;
                
                // Generate document name for some resignations
                $document = (rand(0, 1)) ? 'resignation' . rand(1, 4) . '.png' : null;

                $resignations[] = [
                    'employee_id' => $employees[$i % count($employees)],
                    'last_working_date' => Carbon::now()->subDays($lastWorkingDaysAgo)->format('Y-m-d'),
                    'reason' => $reasons[$i % count($reasons)],
                    'description' => 'Additional details regarding resignation process and transition planning for smooth handover.',
                    'status' => $status,
                    'document' => $document,
                    'approved_by' => $approvedBy,
                    'created_at' => Carbon::now()->subDays($createdDaysAgo)->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                    'updated_at' => Carbon::now()->subDays($createdDaysAgo)->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                ];
            }

            foreach ($resignations as $resignation) {
                Resignation::updateOrCreate(
                    [
                        'employee_id' => $resignation['employee_id'],
                        'last_working_date' => $resignation['last_working_date'],
                        'created_by' => $userId
                    ],
                    array_merge($resignation, [
                        'creator_id' => $userId,
                        'created_by' => $userId,
                    ])
                );
            }
        }
    }
}