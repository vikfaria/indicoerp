<?php

namespace Workdo\Hrm\Database\Seeders;

use Workdo\Hrm\Models\HrmDocument;
use Workdo\Hrm\Models\DocumentCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Workdo\Hrm\Models\Employee;

class DemoHrmDocumentSeeder extends Seeder
{
    public function run($userId): void
    {
        if (HrmDocument::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        
        if (!empty($userId)) {
            $users = User::whereIn('id', Employee::where('created_by', $userId)->pluck('user_id'))
                ->where('created_by', $userId)
                ->pluck('id')
                ->toArray();

            $uploaders = !empty($users) ? array_slice($users, 0, min(5, count($users))) : [$userId];
            $approvers = !empty($users) ? array_slice($users, 0, min(3, count($users))) : [$userId];

            $documents = [
                ['title' => 'Employee Handbook 2024', 'category' => 'Employment Records', 'desc' => 'Comprehensive guide covering company policies, procedures, benefits, and workplace expectations for all employees.', 'status' => 'approve', 'created_at' => Carbon::now()->subDays(175)->addHours(9)->addMinutes(15)],
                ['title' => 'Code of Conduct Policy', 'category' => 'Legal Documents', 'desc' => 'Ethical guidelines and behavioral standards defining professional conduct and integrity expectations for staff.', 'status' => 'approve', 'created_at' => Carbon::now()->subDays(170)->addHours(10)->addMinutes(30)],
                ['title' => 'Leave Application Form', 'category' => 'Employment Records', 'desc' => 'Standard form template for requesting various types of leave including vacation, sick, and personal time off.', 'status' => 'pending', 'created_at' => Carbon::now()->subDays(165)->addHours(14)->addMinutes(45)],
                ['title' => 'Performance Review Guidelines', 'category' => 'Performance Reviews', 'desc' => 'Detailed process and criteria for conducting annual and quarterly employee performance evaluations and assessments.', 'status' => 'approve', 'created_at' => Carbon::now()->subDays(160)->addHours(11)->addMinutes(20)],
                ['title' => 'Workplace Safety Manual', 'category' => 'Legal Documents', 'desc' => 'Comprehensive safety protocols, emergency procedures, and health guidelines for maintaining secure workplace environment.', 'status' => 'reject', 'created_at' => Carbon::now()->subDays(155)->addHours(13)->addMinutes(10)],
                ['title' => 'Expense Reimbursement Policy', 'category' => 'Financial Documents', 'desc' => 'Guidelines and procedures for submitting, approving, and processing business expense reimbursement claims.', 'status' => 'pending', 'created_at' => Carbon::now()->subDays(150)->addHours(15)->addMinutes(35)],
                ['title' => 'Remote Work Agreement', 'category' => 'Contract Documents', 'desc' => 'Terms and conditions for remote work arrangements including productivity expectations and communication protocols.', 'status' => 'approve', 'created_at' => Carbon::now()->subDays(145)->addHours(8)->addMinutes(50)],
                ['title' => 'New Employee Onboarding', 'category' => 'Training Certificates', 'desc' => 'Step-by-step onboarding process covering orientation, training schedules, and integration procedures for new hires.', 'status' => 'reject', 'created_at' => Carbon::now()->subDays(140)->addHours(12)->addMinutes(25)],
                ['title' => 'IT Security Procedures', 'category' => 'Professional Licenses', 'desc' => 'Information technology security protocols, password policies, and data protection measures for system access.', 'status' => 'pending', 'created_at' => Carbon::now()->subDays(135)->addHours(16)->addMinutes(40)],
                ['title' => 'Benefits Enrollment Guide', 'category' => 'Insurance Papers', 'desc' => 'Comprehensive guide for employee benefits selection including health insurance, retirement plans, and wellness programs.', 'status' => 'approve', 'created_at' => Carbon::now()->subDays(130)->addHours(9)->addMinutes(55)],
                ['title' => 'Disciplinary Action Policy', 'category' => 'Legal Documents', 'desc' => 'Framework for addressing employee misconduct including progressive discipline procedures and corrective action steps.', 'status' => 'approve', 'created_at' => Carbon::now()->subDays(125)->addHours(11)->addMinutes(15)],
                ['title' => 'Training Development Plan', 'category' => 'Training Certificates', 'desc' => 'Professional development framework outlining skill enhancement opportunities and career advancement pathways for employees.', 'status' => 'pending', 'created_at' => Carbon::now()->subDays(120)->addHours(14)->addMinutes(30)],
                ['title' => 'Emergency Response Protocol', 'category' => 'Legal Documents', 'desc' => 'Detailed emergency procedures covering fire safety, medical emergencies, and evacuation plans for workplace incidents.', 'status' => 'approve', 'created_at' => Carbon::now()->subDays(115)->addHours(10)->addMinutes(45)],
                ['title' => 'Confidentiality Agreement', 'category' => 'Contract Documents', 'desc' => 'Non-disclosure agreement protecting company proprietary information, trade secrets, and confidential business data.', 'status' => 'reject', 'created_at' => Carbon::now()->subDays(110)->addHours(13)->addMinutes(20)],
                ['title' => 'Payroll Processing Manual', 'category' => 'Financial Documents', 'desc' => 'Comprehensive guide for payroll administration including salary calculations, deductions, and payment processing procedures.', 'status' => 'approve', 'created_at' => Carbon::now()->subDays(105)->addHours(15)->addMinutes(10)],
                ['title' => 'Quality Assurance Standards', 'category' => 'Professional Licenses', 'desc' => 'Quality control procedures and standards ensuring consistent service delivery and product excellence across operations.', 'status' => 'pending', 'created_at' => Carbon::now()->subDays(100)->addHours(8)->addMinutes(35)],
                ['title' => 'Travel Expense Policy', 'category' => 'Travel Documents', 'desc' => 'Guidelines for business travel expenses including accommodation, transportation, and meal allowance reimbursement procedures.', 'status' => 'approve', 'created_at' => Carbon::now()->subDays(95)->addHours(12)->addMinutes(50)],
                ['title' => 'Performance Improvement Plan', 'category' => 'Performance Reviews', 'desc' => 'Structured approach for addressing performance deficiencies with clear goals, timelines, and support mechanisms.', 'status' => 'reject', 'created_at' => Carbon::now()->subDays(90)->addHours(16)->addMinutes(25)],
                ['title' => 'Equipment Usage Agreement', 'category' => 'Contract Documents', 'desc' => 'Terms and conditions for company equipment usage including laptops, mobile devices, and office equipment responsibility.', 'status' => 'approve', 'created_at' => Carbon::now()->subDays(85)->addHours(9)->addMinutes(40)],
                ['title' => 'Health Insurance Enrollment', 'category' => 'Insurance Papers', 'desc' => 'Health insurance plan options, coverage details, and enrollment procedures for employees and their dependents.', 'status' => 'pending', 'created_at' => Carbon::now()->subDays(80)->addHours(11)->addMinutes(55)],
                ['title' => 'Workplace Harassment Policy', 'category' => 'Legal Documents', 'desc' => 'Anti-harassment policy defining prohibited behaviors, reporting procedures, and investigation processes for workplace incidents.', 'status' => 'approve', 'created_at' => Carbon::now()->subDays(75)->addHours(14)->addMinutes(15)],
                ['title' => 'Professional Development Fund', 'category' => 'Financial Documents', 'desc' => 'Guidelines for accessing professional development funding including conference attendance, certification, and training expenses.', 'status' => 'reject', 'created_at' => Carbon::now()->subDays(70)->addHours(10)->addMinutes(30)],
                ['title' => 'Data Protection Guidelines', 'category' => 'Professional Licenses', 'desc' => 'Data privacy and protection protocols ensuring compliance with regulations and safeguarding sensitive information.', 'status' => 'approve', 'created_at' => Carbon::now()->subDays(65)->addHours(13)->addMinutes(45)],
                ['title' => 'Flexible Work Schedule', 'category' => 'Employment Records', 'desc' => 'Policy framework for flexible working arrangements including core hours, schedule variations, and approval processes.', 'status' => 'pending', 'created_at' => Carbon::now()->subDays(60)->addHours(15)->addMinutes(20)],
                ['title' => 'Retirement Plan Guide', 'category' => 'Financial Documents', 'desc' => 'Comprehensive guide to company retirement benefits including contribution matching, vesting schedules, and investment options.', 'status' => 'approve', 'created_at' => Carbon::now()->subDays(55)->addHours(8)->addMinutes(10)],
                ['title' => 'Vendor Management Policy', 'category' => 'Contract Documents', 'desc' => 'Procedures for vendor selection, contract negotiation, performance monitoring, and relationship management with external suppliers.', 'status' => 'reject', 'created_at' => Carbon::now()->subDays(50)->addHours(12)->addMinutes(35)],
                ['title' => 'Innovation Initiative Guidelines', 'category' => 'Training Certificates', 'desc' => 'Framework for employee innovation programs including idea submission, evaluation criteria, and implementation support processes.', 'status' => 'approve', 'created_at' => Carbon::now()->subDays(45)->addHours(16)->addMinutes(50)],
                ['title' => 'Environmental Sustainability Plan', 'category' => 'Legal Documents', 'desc' => 'Corporate environmental responsibility initiatives including waste reduction, energy conservation, and sustainable business practices.', 'status' => 'pending', 'created_at' => Carbon::now()->subDays(40)->addHours(9)->addMinutes(25)],
                ['title' => 'Customer Service Standards', 'category' => 'Professional Licenses', 'desc' => 'Service excellence guidelines defining customer interaction standards, response times, and quality assurance measures.', 'status' => 'approve', 'created_at' => Carbon::now()->subDays(35)->addHours(11)->addMinutes(40)],
                ['title' => 'Business Continuity Plan', 'category' => 'Legal Documents', 'desc' => 'Comprehensive disaster recovery and business continuity procedures ensuring operational resilience during emergencies and disruptions.', 'status' => 'approve', 'created_at' => Carbon::now()->subDays(30)->addHours(14)->addMinutes(55)],
                ['title' => 'Flexible Work Schedule', 'category' => 'Employment Records', 'desc' => 'Policy framework for flexible working arrangements including core hours, schedule variations, and approval processes.', 'status' => 'pending', 'document' => 'flexible_schedule.pdf', 'created_at' => Carbon::now()->subDays(60)->addHours(15)->addMinutes(20)],
                ['title' => 'Retirement Plan Guide', 'category' => 'Financial Documents', 'desc' => 'Comprehensive guide to company retirement benefits including contribution matching, vesting schedules, and investment options.', 'status' => 'approve', 'document' => 'retirement_guide.pdf', 'created_at' => Carbon::now()->subDays(55)->addHours(8)->addMinutes(10)],
                ['title' => 'Vendor Management Policy', 'category' => 'Contract Documents', 'desc' => 'Procedures for vendor selection, contract negotiation, performance monitoring, and relationship management with external suppliers.', 'status' => 'reject', 'document' => 'vendor_policy.pdf', 'created_at' => Carbon::now()->subDays(50)->addHours(12)->addMinutes(35)],
                ['title' => 'Innovation Initiative Guidelines', 'category' => 'Training Certificates', 'desc' => 'Framework for employee innovation programs including idea submission, evaluation criteria, and implementation support processes.', 'status' => 'approve', 'document' => 'innovation_guide.pdf', 'created_at' => Carbon::now()->subDays(45)->addHours(16)->addMinutes(50)],
                ['title' => 'Environmental Sustainability Plan', 'category' => 'Legal Documents', 'desc' => 'Corporate environmental responsibility initiatives including waste reduction, energy conservation, and sustainable business practices.', 'status' => 'pending', 'document' => 'sustainability.pdf', 'created_at' => Carbon::now()->subDays(40)->addHours(9)->addMinutes(25)],
                ['title' => 'Customer Service Standards', 'category' => 'Professional Licenses', 'desc' => 'Service excellence guidelines defining customer interaction standards, response times, and quality assurance measures.', 'status' => 'approve', 'document' => 'service_standards.pdf', 'created_at' => Carbon::now()->subDays(35)->addHours(11)->addMinutes(40)],
                ['title' => 'Business Continuity Plan', 'category' => 'Legal Documents', 'desc' => 'Comprehensive disaster recovery and business continuity procedures ensuring operational resilience during emergencies and disruptions.', 'status' => 'approve', 'document' => 'continuity_plan.pdf', 'created_at' => Carbon::now()->subDays(30)->addHours(14)->addMinutes(55)]
            ];

            foreach ($documents as $index => $document) {
                $categoryRecord = DocumentCategory::where('document_type', $document['category'])
                    ->where('created_by', $userId)
                    ->first();

                if (!$categoryRecord) {
                    $categoryRecord = DocumentCategory::where('created_by', $userId)->first();
                }

                $uploadedBy = $uploaders[$index % count($uploaders)];
                $finalApprovedBy = $document['status'] === 'approve' ? $approvers[$index % count($approvers)] : null;
                $effectiveDate = $document['status'] === 'approve' ? $document['created_at']->toDateString() : null;
                $randomDocument = 'hrm_document' . rand(1, 4) . '.png';

                HrmDocument::updateOrCreate(
                    [
                        'title' => $document['title'],
                        'created_by' => $userId
                    ],
                    [
                        'description' => $document['desc'],
                        'document_category_id' => $categoryRecord?->id,
                        'document' => $randomDocument,
                        'effective_date' => $effectiveDate,
                        'status' => $document['status'],
                        'uploaded_by' => $uploadedBy,
                        'approved_by' => $finalApprovedBy,
                        'creator_id' => $userId,
                        'created_by' => $userId,
                        'created_at' => $document['created_at'],
                        'updated_at' => $document['created_at']
                    ]
                );
            }
        }
    }
}