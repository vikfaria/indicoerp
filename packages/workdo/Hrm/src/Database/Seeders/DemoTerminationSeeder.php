<?php

namespace Workdo\Hrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\Termination;
use Workdo\Hrm\Models\TerminationType;
use Workdo\Hrm\Models\Employee;
use App\Models\User;
use Carbon\Carbon;

class DemoTerminationSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Termination::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        
        if (!empty($userId)) {
            $employees = User::whereIn('id', Employee::where('created_by', $userId)->pluck('user_id'))
                ->where('created_by', $userId)
                ->pluck('id')
                ->toArray();

            $terminationTypes = TerminationType::where('created_by', $userId)->pluck('id')->toArray();
            $approvers = User::where('created_by', $userId)->pluck('id')->toArray();

            if (empty($employees) || empty($terminationTypes)) {
                return;
            }

            $reasons = [
                'Performance issues and failure to meet established job requirements despite multiple improvement opportunities.',
                'Violation of company policies including code of conduct and workplace safety regulations.',
                'Attendance problems with excessive absenteeism and tardiness affecting team productivity and operations.',
                'Misconduct including inappropriate behavior towards colleagues and violation of professional standards.',
                'Redundancy due to organizational restructuring and elimination of position from company structure.',
                'Budget constraints requiring workforce reduction and cost optimization measures for business sustainability.',
                'Insubordination and failure to follow direct supervisor instructions and established chain of command.',
                'Breach of confidentiality agreement and unauthorized disclosure of sensitive company information.',
                'Poor work quality consistently below acceptable standards despite training and performance improvement plans.',
                'Dishonesty including falsification of records and misrepresentation of work activities and achievements.',
                'Harassment complaints substantiated through investigation requiring immediate termination for workplace safety.',
                'Theft of company property including equipment and supplies resulting in criminal charges.',
                'Substance abuse affecting work performance and creating safety hazards in workplace environment.',
                'Conflict of interest violations including unauthorized outside employment with competitor organizations.',
                'Technology misuse including inappropriate internet usage and violation of computer security policies.',
                'Customer complaints regarding unprofessional behavior and failure to provide adequate service quality.',
                'Safety violations creating hazardous conditions and endangering employee welfare and operational integrity.',
                'Discrimination complaints substantiated through investigation requiring immediate corrective action and termination.',
                'Fraud including expense account manipulation and unauthorized financial transactions affecting company resources.',
                'Abandonment of position without proper notification and failure to report for scheduled work assignments.',
                'Incompetence demonstrated through inability to perform essential job functions despite adequate training.',
                'Workplace violence including threats and aggressive behavior creating hostile work environment.',
                'Embezzlement of company funds through unauthorized access and manipulation of financial accounts.',
                'Gross negligence resulting in significant financial loss and damage to company reputation.',
                'Contract violation including breach of employment agreement terms and conditions.',
                'Medical incapacity preventing performance of essential job functions with no reasonable accommodation available.',
                'Layoff due to economic downturn requiring temporary workforce reduction and operational cost management.',
                'Restructuring elimination of department requiring position consolidation and organizational efficiency improvement.',
                'Merger integration resulting in duplicate positions and need for workforce optimization.',
                'Automation implementation replacing manual processes and reducing need for human resources.'
            ];

            // Create status array with realistic distribution (18 approved, 9 pending, 3 rejected)
            $statusArray = array_merge(
                array_fill(0, 18, 'approved'),
                array_fill(0, 9, 'pending'),
                array_fill(0, 3, 'rejected')
            );
            shuffle($statusArray);

            $terminations = [];
            for ($i = 0; $i < 30; $i++) {
                $terminationDaysAgo = 175 - ($i * 5);
                $createdDaysAgo = $terminationDaysAgo - 2;
                $noticeDaysAgo = $terminationDaysAgo + 14; // Notice given 14 days before termination
                
                $status = $statusArray[$i];
                
                // For approved terminations, set approved_by
                $approvedBy = ($status === 'approved') ? $approvers[array_rand($approvers)] : null;
                
                // Generate document name after every 2 records
                $document = ($i % 2 === 0) ? 'termination' . rand(1, 4) . '.png' : null;

                $terminations[] = [
                    'employee_id' => $employees[$i % count($employees)],
                    'termination_type_id' => $terminationTypes[$i % count($terminationTypes)],
                    'notice_date' => Carbon::now()->subDays($noticeDaysAgo)->format('Y-m-d'),
                    'termination_date' => Carbon::now()->subDays($terminationDaysAgo)->format('Y-m-d'),
                    'reason' => $reasons[$i % count($reasons)],
                    'description' => 'Additional details regarding termination process and final settlement procedures for employee separation.',
                    'status' => $status,
                    'document' => $document,
                    'approved_by' => $approvedBy,
                    'created_at' => Carbon::now()->subDays($createdDaysAgo)->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                    'updated_at' => Carbon::now()->subDays($createdDaysAgo)->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                ];
            }

            foreach ($terminations as $termination) {
                Termination::updateOrCreate(
                    [
                        'employee_id' => $termination['employee_id'],
                        'termination_date' => $termination['termination_date'],
                        'created_by' => $userId
                    ],
                    array_merge($termination, [
                        'creator_id' => $userId,
                        'created_by' => $userId,
                    ])
                );
            }
        }
    }
}