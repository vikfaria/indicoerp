<?php

namespace Workdo\Hrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\EmployeeTransfer;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;
use Workdo\Hrm\Models\Designation;
use App\Models\User;
use Carbon\Carbon;

class DemoEmployeeTransferSeeder extends Seeder
{
    public function run($userId): void
    {
        if (EmployeeTransfer::where('created_by', $userId)->exists()) {
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
                'Business expansion requiring experienced personnel to establish operations in new regional office location.',
                'Organizational restructuring necessitating employee relocation to optimize departmental efficiency and resource allocation.',
                'Career development opportunity providing exposure to different business functions and professional growth experience.',
                'Skill set alignment with departmental requirements offering better utilization of employee expertise and capabilities.',
                'Project assignment requiring specialized knowledge and experience available in different organizational division.',
                'Cost optimization initiative through strategic workforce redistribution and operational efficiency improvement measures.',
                'Employee request for transfer due to personal circumstances and family relocation requirements.',
                'Performance improvement opportunity through new challenges and responsibilities in different work environment.',
                'Succession planning initiative preparing employee for leadership role through cross-functional experience.',
                'Market expansion strategy requiring experienced staff to support new business development initiatives.',
                'Technology implementation project requiring technical expertise and change management support in target location.',
                'Customer service enhancement initiative through strategic placement of experienced personnel in key locations.',
                'Training and development program providing mentorship opportunities and knowledge transfer to junior staff.',
                'Operational efficiency improvement through better resource allocation and workload distribution across departments.',
                'Strategic partnership development requiring experienced personnel to manage client relationships and business growth.',
                'Quality assurance enhancement through deployment of experienced quality control specialists to critical locations.',
                'Risk management initiative requiring experienced personnel to implement compliance and governance procedures.',
                'Innovation project requiring creative thinking and problem-solving expertise in research and development division.',
                'Leadership development program providing management experience and strategic decision-making responsibilities.',
                'Client relationship management requiring experienced account managers to maintain and grow key partnerships.',
                'Process improvement initiative through deployment of lean management specialists to optimize operational workflows.',
                'Digital transformation project requiring technology adoption expertise and change management support.',
                'Regulatory compliance enhancement requiring experienced personnel to ensure adherence to industry standards.',
                'Supply chain optimization requiring logistics expertise and vendor management experience in new location.',
                'Financial management improvement requiring experienced accounting and budgeting specialists for cost control.',
                'Human resources development requiring experienced HR professionals to implement talent management programs.',
                'Sales performance enhancement requiring experienced sales professionals to drive revenue growth initiatives.',
                'Environmental sustainability initiative requiring expertise in green practices and corporate social responsibility.',
                'International expansion requiring cultural adaptation expertise and global business development experience.',
                'Emergency response planning requiring experienced personnel to establish crisis management protocols.'
            ];

            $statuses = ['pending', 'approved', 'in progress', 'rejected', 'cancelled'];
            
            // Create status array with realistic distribution
            $statusArray = array_merge(
                array_fill(0, 15, 'approved'),
                array_fill(0, 8, 'in progress'),
                array_fill(0, 4, 'pending'),
                array_fill(0, 2, 'rejected'),
                array_fill(0, 1, 'cancelled')
            );
            shuffle($statusArray);

            $transfers = [];
            for ($i = 0; $i < 30; $i++) {
                $transferDaysAgo = 175 - ($i * 5);
                $createdDaysAgo = $transferDaysAgo - 2;
                $effectiveDaysAgo = max(0, $transferDaysAgo - rand(7, 21)); // Effective date after transfer date
                
                $status = $statusArray[$i];
                
                // For approved transfers, set approved_by
                $approvedBy = ($status === 'approved') ? $approvers[array_rand($approvers)] : null;
                
                // Generate document name after every 2 records
                $document = ($i % 2 === 0) ? 'transfer' . rand(1, 4) . '.png' : null;
                
                // Get from position (current position)
                $fromIndex = $i % count($branchDeptDesig);
                $fromBDD = $branchDeptDesig[$fromIndex];
                $fromDesignationId = $fromBDD['designation_ids'][array_rand($fromBDD['designation_ids'])];
                
                // Get to position (ensure different branch)
                $availableToBDD = array_filter($branchDeptDesig, function($bdd) use ($fromBDD) {
                    return $bdd['branch_id'] !== $fromBDD['branch_id'];
                });
                
                if (empty($availableToBDD)) {
                    // If no different branch available, skip this iteration
                    continue;
                }
                
                $toBDD = $availableToBDD[array_rand($availableToBDD)];
                $toDesignationId = $toBDD['designation_ids'][array_rand($toBDD['designation_ids'])];

                $transfers[] = [
                    'employee_id' => $employees[$i % count($employees)],
                    'from_branch_id' => $fromBDD['branch_id'],
                    'from_department_id' => $fromBDD['department_id'],
                    'from_designation_id' => $fromDesignationId,
                    'to_branch_id' => $toBDD['branch_id'],
                    'to_department_id' => $toBDD['department_id'],
                    'to_designation_id' => $toDesignationId,
                    'transfer_date' => Carbon::now()->subDays($transferDaysAgo)->format('Y-m-d'),
                    'effective_date' => Carbon::now()->subDays($effectiveDaysAgo)->format('Y-m-d'),
                    'reason' => $reasons[$i % count($reasons)],
                    'status' => $status,
                    'document' => $document,
                    'approved_by' => $approvedBy,
                    'created_at' => Carbon::now()->subDays($createdDaysAgo)->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                    'updated_at' => Carbon::now()->subDays($createdDaysAgo)->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                ];
            }

            foreach ($transfers as $transfer) {
                EmployeeTransfer::updateOrCreate(
                    [
                        'employee_id' => $transfer['employee_id'],
                        'transfer_date' => $transfer['transfer_date'],
                        'created_by' => $userId
                    ],
                    array_merge($transfer, [
                        'creator_id' => $userId,
                        'created_by' => $userId,
                    ])
                );
            }
        }
    }
}