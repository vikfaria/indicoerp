<?php

namespace Workdo\Hrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\Warning;
use Workdo\Hrm\Models\WarningType;
use Workdo\Hrm\Models\Employee;
use App\Models\User;
use Carbon\Carbon;

class DemoWarningSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Warning::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        
        if (!empty($userId)) {
            $employees = User::whereIn('id', Employee::where('created_by', $userId)->pluck('user_id'))
                ->where('created_by', $userId)
                ->pluck('id')
                ->toArray();

            $warningTypes = WarningType::where('created_by', $userId)->pluck('id')->toArray();
            $warningBy = User::where('created_by', $userId)->pluck('id')->toArray();

            if (empty($employees) || empty($warningTypes)) {
                return;
            }

            $subjects = [
                'Attendance Policy Violation - Excessive Tardiness',
                'Performance Standards Not Met - Quality Issues',
                'Workplace Conduct - Inappropriate Behavior',
                'Safety Protocol Violation - Equipment Misuse',
                'Dress Code Policy - Professional Appearance',
                'Communication Standards - Unprofessional Language',
                'Time Management - Deadline Missed',
                'Customer Service - Complaint Received',
                'Technology Usage - Personal Internet Access',
                'Confidentiality Breach - Information Disclosure',
                'Insubordination - Failure to Follow Instructions',
                'Work Quality - Error Rate Above Acceptable',
                'Team Collaboration - Disruptive Behavior',
                'Policy Compliance - Procedure Not Followed',
                'Professional Development - Training Requirements',
                'Workplace Harassment - Inappropriate Comments',
                'Documentation Standards - Incomplete Records',
                'Resource Management - Wasteful Practices',
                'Meeting Attendance - Frequent Absences',
                'Project Management - Scope Deviation',
                'Client Relations - Unprofessional Interaction',
                'Data Security - Password Policy Violation',
                'Inventory Management - Discrepancy Found',
                'Quality Control - Standards Not Maintained',
                'Environmental Compliance - Waste Disposal',
                'Training Compliance - Certification Expired',
                'Equipment Care - Damage Due to Negligence',
                'Reporting Standards - Late Submission',
                'Conflict Resolution - Escalation Required',
                'Performance Improvement - Action Plan Needed'
            ];

            $descriptions = [
                'Employee consistently arrives late to work affecting team productivity and client service delivery standards.',
                'Work quality below acceptable standards with frequent errors requiring additional review and correction processes.',
                'Inappropriate workplace behavior including unprofessional comments and disruptive actions affecting team morale.',
                'Safety protocol violations observed during equipment operation creating potential hazards for workplace safety.',
                'Dress code policy violations with inappropriate attire not meeting professional workplace appearance standards.',
                'Unprofessional communication including inappropriate language and tone during client and colleague interactions.',
                'Consistent failure to meet project deadlines affecting team schedules and client deliverable commitments.',
                'Customer service complaints received regarding unprofessional behavior and inadequate service quality delivery.',
                'Excessive personal internet usage during work hours affecting productivity and violating technology usage policies.',
                'Confidentiality breach involving unauthorized disclosure of sensitive company information to external parties.',
                'Insubordination demonstrated through failure to follow direct supervisor instructions and established procedures.',
                'Work quality issues with error rates exceeding acceptable standards requiring immediate improvement measures.',
                'Disruptive team behavior affecting collaboration and creating negative work environment for colleagues.',
                'Policy compliance failures including failure to follow established procedures and organizational guidelines.',
                'Professional development requirements not met including mandatory training and certification renewal deadlines.',
                'Workplace harassment complaints involving inappropriate comments and behavior creating hostile work environment.',
                'Documentation standards not maintained with incomplete records affecting audit compliance and operational efficiency.',
                'Resource management issues including wasteful practices and inefficient use of company materials and supplies.',
                'Frequent meeting absences affecting project coordination and team communication regarding important business matters.',
                'Project management issues including scope deviation and failure to adhere to established project parameters.',
                'Client relations problems involving unprofessional interactions and failure to maintain appropriate business relationships.',
                'Data security violations including password policy breaches and unauthorized access to confidential information.',
                'Inventory management discrepancies found during audit requiring investigation and corrective action implementation.',
                'Quality control standards not maintained resulting in defective products and customer satisfaction issues.',
                'Environmental compliance violations including improper waste disposal and failure to follow sustainability protocols.',
                'Training compliance issues with expired certifications affecting job performance and regulatory compliance requirements.',
                'Equipment damage due to negligence and failure to follow proper care and maintenance procedures.',
                'Reporting standards violations including late submission of required documents and incomplete information provided.',
                'Conflict resolution issues requiring escalation due to inability to resolve workplace disputes professionally.',
                'Performance improvement required with formal action plan needed to address ongoing productivity and quality concerns.'
            ];

            $severities = ['Low', 'Medium', 'High'];
            $employeeResponses = [
                'I acknowledge the warning and will improve my performance immediately.',
                'I understand the concerns and commit to following all policies.',
                'I will take corrective action to address these issues.',
                'I accept responsibility and will work on improvement.',
                'I will ensure this does not happen again in the future.'
            ];

            // Create status array with realistic distribution (18 approved, 9 pending, 3 rejected)
            $statusArray = array_merge(
                array_fill(0, 18, 'approved'),
                array_fill(0, 9, 'pending'),
                array_fill(0, 3, 'rejected')
            );
            shuffle($statusArray);

            $warnings = [];
            for ($i = 0; $i < 30; $i++) {
                $warningDaysAgo = 175 - ($i * 5);
                $createdDaysAgo = $warningDaysAgo - 1;
                
                $status = $statusArray[$i];
                $severity = $severities[$i % 3];
                
                // Generate document name after every 2 records
                $document = ($i % 2 === 0) ? 'warning' . rand(1, 4) . '.png' : null;
                
                // Employee response for some warnings
                $employeeResponse = (rand(0, 1)) ? $employeeResponses[array_rand($employeeResponses)] : null;

                $warnings[] = [
                    'employee_id' => $employees[$i % count($employees)],
                    'warning_by' => $warningBy[array_rand($warningBy)],
                    'warning_type_id' => $warningTypes[$i % count($warningTypes)],
                    'subject' => $subjects[$i % count($subjects)],
                    'severity' => $severity,
                    'warning_date' => Carbon::now()->subDays($warningDaysAgo)->format('Y-m-d'),
                    'description' => $descriptions[$i % count($descriptions)],
                    'document' => $document,
                    'status' => $status,
                    'employee_response' => $employeeResponse,
                    'created_at' => Carbon::now()->subDays($createdDaysAgo)->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                    'updated_at' => Carbon::now()->subDays($createdDaysAgo)->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                ];
            }

            foreach ($warnings as $warning) {
                Warning::updateOrCreate(
                    [
                        'employee_id' => $warning['employee_id'],
                        'warning_date' => $warning['warning_date'],
                        'subject' => $warning['subject'],
                        'created_by' => $userId
                    ],
                    array_merge($warning, [
                        'creator_id' => $userId,
                        'created_by' => $userId,
                    ])
                );
            }
        }
    }
}