<?php

namespace Workdo\Hrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\Complaint;
use Workdo\Hrm\Models\ComplaintType;
use Workdo\Hrm\Models\Employee;
use App\Models\User;
use Carbon\Carbon;

class DemoComplaintSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Complaint::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        if (!empty($userId)) {
            $employees = User::whereIn('id', Employee::where('created_by', $userId)->pluck('user_id'))
                ->where('created_by', $userId)
                ->pluck('id')
                ->toArray();

            $complaintTypes = ComplaintType::where('created_by', $userId)->pluck('id')->toArray();
            $resolvers = User::where('created_by', $userId)->pluck('id')->toArray();

            if (empty($employees) || empty($complaintTypes)) {
                return;
            }

            $subjects = [
                'Workplace Harassment - Inappropriate Comments and Behavior',
                'Discrimination Based on Gender and Age Factors',
                'Bullying and Intimidation by Senior Colleagues',
                'Unfair Treatment in Performance Evaluation Process',
                'Hostile Work Environment - Verbal Abuse',
                'Sexual Harassment - Unwanted Advances and Comments',
                'Racial Discrimination in Promotion Opportunities',
                'Retaliation for Whistleblowing Activities',
                'Unsafe Working Conditions - Equipment Malfunction',
                'Wage and Hour Violations - Overtime Issues',
                'Privacy Violation - Personal Information Misuse',
                'Favoritism in Task Assignment and Recognition',
                'Breach of Confidentiality Agreement Terms',
                'Unprofessional Conduct by Management Personnel',
                'Workplace Violence - Threats and Aggressive Behavior',
                'Disability Discrimination - Accommodation Denial',
                'Religious Discrimination - Prayer Time Restrictions',
                'Age Discrimination in Training Opportunities',
                'Wrongful Termination Threat and Intimidation',
                'Health and Safety Protocol Violations',
                'Theft of Personal Property from Workspace',
                'Misuse of Company Resources and Equipment',
                'Conflict of Interest - Undisclosed Relationships',
                'Substance Abuse in Workplace Environment',
                'Data Security Breach - Unauthorized Access',
                'Environmental Health Hazards - Chemical Exposure',
                'Ergonomic Issues - Repetitive Strain Injuries',
                'Noise Pollution - Excessive Workplace Disturbance',
                'Temperature Control - Uncomfortable Working Conditions',
                'Parking and Transportation Issues Resolution'
            ];

            $descriptions = [
                'Employee experiencing inappropriate comments and unwelcome behavior from colleagues creating hostile work environment affecting productivity.',
                'Discrimination complaints regarding unfair treatment based on gender and age factors affecting career advancement opportunities.',
                'Bullying and intimidation tactics used by senior colleagues creating psychological stress and affecting work performance.',
                'Unfair treatment during performance evaluation process with biased assessment and lack of objective criteria application.',
                'Hostile work environment characterized by verbal abuse and unprofessional language affecting employee mental health.',
                'Sexual harassment incidents involving unwanted advances and inappropriate comments creating uncomfortable workplace atmosphere.',
                'Racial discrimination in promotion opportunities with qualified candidates overlooked based on ethnic background considerations.',
                'Retaliation activities following whistleblowing reports including isolation and negative performance reviews affecting career progression.',
                'Unsafe working conditions due to equipment malfunction and inadequate maintenance creating potential safety hazards.',
                'Wage and hour violations including unpaid overtime and incorrect calculation of compensation affecting financial stability.',
                'Privacy violation through misuse of personal information and unauthorized access to confidential employee data.',
                'Favoritism in task assignment and recognition programs creating unequal opportunities and affecting team morale.',
                'Breach of confidentiality agreement terms through unauthorized disclosure of sensitive company information to competitors.',
                'Unprofessional conduct by management personnel including inappropriate behavior and failure to maintain professional standards.',
                'Workplace violence incidents involving threats and aggressive behavior creating fear and unsafe working environment.',
                'Disability discrimination through denial of reasonable accommodation requests affecting ability to perform job functions.',
                'Religious discrimination including restrictions on prayer time and religious observance affecting spiritual well-being.',
                'Age discrimination in training opportunities with younger employees receiving preferential treatment in professional development.',
                'Wrongful termination threats and intimidation tactics used to suppress legitimate complaints and concerns.',
                'Health and safety protocol violations including failure to provide protective equipment and safety training.',
                'Theft of personal property from workspace including electronic devices and personal belongings affecting security.',
                'Misuse of company resources and equipment for personal purposes violating organizational policies and procedures.',
                'Conflict of interest situations involving undisclosed relationships affecting business decisions and fairness.',
                'Substance abuse incidents in workplace environment creating safety concerns and affecting professional atmosphere.',
                'Data security breach through unauthorized access to confidential information affecting company and client privacy.',
                'Environmental health hazards including chemical exposure and inadequate ventilation affecting employee health and safety.',
                'Ergonomic issues causing repetitive strain injuries due to inadequate workspace design and equipment configuration.',
                'Noise pollution from excessive workplace disturbance affecting concentration and productivity during work hours.',
                'Temperature control issues creating uncomfortable working conditions affecting employee comfort and performance.',
                'Parking and transportation issues requiring resolution to improve employee accessibility and convenience.'
            ];

            $statuses = ['pending', 'in review', 'assigned', 'in progress', 'resolved'];
            
            // Create status array with realistic distribution
            $statusArray = array_merge(
                array_fill(0, 12, 'resolved'),
                array_fill(0, 8, 'in progress'),
                array_fill(0, 5, 'assigned'),
                array_fill(0, 3, 'in review'),
                array_fill(0, 2, 'pending')
            );
            shuffle($statusArray);

            $complaints = [];
            for ($i = 0; $i < 30; $i++) {
                $complaintDaysAgo = 175 - ($i * 5);
                $createdDaysAgo = $complaintDaysAgo - 1;
                
                $status = $statusArray[$i];
                
                // For resolved complaints, set resolved_by and resolution_date
                $resolvedBy = ($status === 'resolved') ? $resolvers[array_rand($resolvers)] : null;
                $resolutionDate = ($status === 'resolved') ? 
                    Carbon::now()->subDays($complaintDaysAgo - rand(7, 30))->format('Y-m-d') : null;
                
                // Generate document name after every 2 records
                $document = ($i % 2 === 0) ? 'complaint' . rand(1, 4) . '.png' : null;
                
                // Set employee_id and ensure against_employee_id is different
                $employeeId = $employees[$i % count($employees)];
                $availableAgainstEmployees = array_filter($employees, function($id) use ($employeeId) {
                    return $id !== $employeeId;
                });
                $againstEmployee = $availableAgainstEmployees[array_rand($availableAgainstEmployees)];

                $complaints[] = [
                    'employee_id' => $employeeId,
                    'against_employee_id' => $againstEmployee,
                    'complaint_type_id' => $complaintTypes[$i % count($complaintTypes)],
                    'subject' => $subjects[$i % count($subjects)],
                    'description' => $descriptions[$i % count($descriptions)],
                    'complaint_date' => Carbon::now()->subDays($complaintDaysAgo)->format('Y-m-d'),
                    'status' => $status,
                    'document' => $document,
                    'resolved_by' => $resolvedBy,
                    'resolution_date' => $resolutionDate,
                    'created_at' => Carbon::now()->subDays($createdDaysAgo)->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                    'updated_at' => Carbon::now()->subDays($createdDaysAgo)->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                ];
            }

            foreach ($complaints as $complaint) {
                Complaint::updateOrCreate(
                    [
                        'employee_id' => $complaint['employee_id'],
                        'complaint_date' => $complaint['complaint_date'],
                        'subject' => $complaint['subject'],
                        'created_by' => $userId
                    ],
                    array_merge($complaint, [
                        'creator_id' => $userId,
                        'created_by' => $userId,
                    ])
                );
            }
        }
    }
}