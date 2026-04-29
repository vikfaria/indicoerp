<?php

namespace Workdo\Hrm\Database\Seeders;

use Workdo\Hrm\Models\Acknowledgment;
use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\HrmDocument;
use App\Models\User;
use Carbon\Carbon;
use Workdo\Hrm\Models\Employee;

class DemoAcknowledgmentSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Acknowledgment::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        if (!empty($userId)) {
            $users = User::whereIn('id', Employee::where('created_by', $userId)->pluck('user_id'))
                ->where('created_by', $userId)
                ->pluck('id')
                ->toArray();
            $documents = HrmDocument::where('created_by', $userId)->pluck('id')->toArray();

            if (empty($users) || empty($documents)) {
                return;
            }

            $acknowledgments = [
                ['status' => 'acknowledged', 'note' => 'I have thoroughly reviewed and understood all company policies outlined in the employee handbook document.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(175)->addHours(9)->addMinutes(15)],
                ['status' => 'acknowledged', 'note' => 'Safety guidelines and emergency procedures have been carefully read and acknowledged for workplace compliance.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(170)->addHours(10)->addMinutes(30)],
                ['status' => 'pending', 'note' => 'Currently reviewing the leave application procedures and will provide acknowledgment within the specified timeframe.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(165)->addHours(14)->addMinutes(45)],
                ['status' => 'acknowledged', 'note' => 'Performance review guidelines are clear and comprehensive, providing excellent framework for evaluation processes.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(160)->addHours(11)->addMinutes(20)],
                ['status' => 'pending', 'note' => 'Workplace safety manual requires additional clarification on section three regarding emergency evacuation procedures.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(155)->addHours(13)->addMinutes(10)],
                ['status' => 'acknowledged', 'note' => 'Expense reimbursement policy has been reviewed and all procedures are understood for future claims.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(150)->addHours(15)->addMinutes(35)],
                ['status' => 'acknowledged', 'note' => 'Remote work agreement terms are acceptable and I commit to maintaining productivity standards.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(145)->addHours(8)->addMinutes(50)],
                ['status' => 'pending', 'note' => 'New employee onboarding materials are being reviewed and acknowledgment will be provided shortly.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(140)->addHours(12)->addMinutes(25)],
                ['status' => 'acknowledged', 'note' => 'IT security procedures are comprehensive and I understand all password and data protection requirements.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(135)->addHours(16)->addMinutes(40)],
                ['status' => 'acknowledged', 'note' => 'Benefits enrollment guide provides clear instructions for health insurance and retirement plan selections.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(130)->addHours(9)->addMinutes(55)],
                ['status' => 'acknowledged', 'note' => 'Disciplinary action policy framework is well-structured and provides fair progressive discipline procedures.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(125)->addHours(11)->addMinutes(15)],
                ['status' => 'pending', 'note' => 'Training development plan requires more time for thorough review of career advancement pathways.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(120)->addHours(14)->addMinutes(30)],
                ['status' => 'acknowledged', 'note' => 'Emergency response protocol is detailed and covers all necessary safety procedures for workplace incidents.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(115)->addHours(10)->addMinutes(45)],
                ['status' => 'pending', 'note' => 'Confidentiality agreement terms need legal review before providing final acknowledgment and acceptance.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(110)->addHours(13)->addMinutes(20)],
                ['status' => 'acknowledged', 'note' => 'Payroll processing manual is comprehensive and clearly explains salary calculations and deduction procedures.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(105)->addHours(15)->addMinutes(10)],
                ['status' => 'pending', 'note' => 'Quality assurance standards document is extensive and requires additional time for complete understanding.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(100)->addHours(8)->addMinutes(35)],
                ['status' => 'acknowledged', 'note' => 'Travel expense policy guidelines are clear and provide excellent framework for business trip reimbursements.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(95)->addHours(12)->addMinutes(50)],
                ['status' => 'pending', 'note' => 'Performance improvement plan template needs discussion with supervisor before final acknowledgment can be provided.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(90)->addHours(16)->addMinutes(25)],
                ['status' => 'acknowledged', 'note' => 'Equipment usage agreement terms are reasonable and I accept responsibility for company property care.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(85)->addHours(9)->addMinutes(40)],
                ['status' => 'pending', 'note' => 'Health insurance enrollment options require consultation with family before making final benefit selections.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(80)->addHours(11)->addMinutes(55)],
                ['status' => 'acknowledged', 'note' => 'Workplace harassment policy is comprehensive and provides clear reporting procedures for incident management.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(75)->addHours(14)->addMinutes(15)],
                ['status' => 'pending', 'note' => 'Professional development fund guidelines need clarification regarding conference attendance approval processes and procedures.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(70)->addHours(10)->addMinutes(30)],
                ['status' => 'acknowledged', 'note' => 'Data protection guidelines are thorough and I understand all compliance requirements for information security.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(65)->addHours(13)->addMinutes(45)],
                ['status' => 'acknowledged', 'note' => 'Flexible work schedule policy provides excellent work-life balance options with clear core hours requirements.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(60)->addHours(15)->addMinutes(20)],
                ['status' => 'acknowledged', 'note' => 'Retirement plan guide is detailed and provides comprehensive information about contribution matching and investment options.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(55)->addHours(8)->addMinutes(10)],
                ['status' => 'pending', 'note' => 'Vendor management policy requires additional review of contract negotiation procedures before final acknowledgment.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(50)->addHours(12)->addMinutes(35)],
                ['status' => 'acknowledged', 'note' => 'Innovation initiative guidelines are inspiring and provide excellent framework for employee idea submission processes.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(45)->addHours(16)->addMinutes(50)],
                ['status' => 'pending', 'note' => 'Environmental sustainability plan is comprehensive but needs discussion regarding individual employee responsibility implementation.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(40)->addHours(9)->addMinutes(25)],
                ['status' => 'acknowledged', 'note' => 'Customer service standards are well-defined and provide clear guidelines for maintaining service excellence.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(35)->addHours(11)->addMinutes(40)],
                ['status' => 'acknowledged', 'note' => 'Business continuity plan is thorough and provides comprehensive disaster recovery procedures for operational resilience.', 'has_document' => true, 'has_assigned' => true, 'created_at' => Carbon::now()->subDays(30)->addHours(14)->addMinutes(55)]
            ];

            foreach ($acknowledgments as $index => $acknowledgment) {
                $employeeId = $users[$index % count($users)];
                $assignedBy = $users[($index + 1) % count($users)];
                $documentId = $acknowledgment['has_document'] ? $documents[$index % count($documents)] : null;

                $acknowledgedAt = null;
                if ($acknowledgment['status'] === 'acknowledged') {
                    $acknowledgedAt = $acknowledgment['created_at']->copy()->addHours(rand(1, 48))->addMinutes(rand(0, 59));
                }

                Acknowledgment::updateOrCreate(
                    [
                        'employee_id' => $employeeId,
                        'document_id' => $documentId,
                        'created_by' => $userId
                    ],
                    [
                        'status' => $acknowledgment['status'],
                        'acknowledgment_note' => $acknowledgment['note'],
                        'acknowledged_at' => $acknowledgedAt,
                        'assigned_by' => $acknowledgment['has_assigned'] ? $assignedBy : null,
                        'creator_id' => $userId,
                        'created_by' => $userId,
                        'created_at' => $acknowledgment['created_at'],
                        'updated_at' => $acknowledgedAt ?? $acknowledgment['created_at']
                    ]
                );
            }
        }
    }
}