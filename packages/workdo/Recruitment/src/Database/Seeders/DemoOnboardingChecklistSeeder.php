<?php

namespace Workdo\Recruitment\Database\Seeders;

use Workdo\Recruitment\Models\OnboardingChecklist;
use Illuminate\Database\Seeder;



class DemoOnboardingChecklistSeeder extends Seeder
{
    public function run($userId): void
    {
        if (OnboardingChecklist::where('created_by', $userId)->exists()) {
            return;
        }

        $checklists = [
            [
                'name' => 'IT Setup & Equipment',
                'description' => 'Provide laptop, phone, access cards, and set up all necessary IT accounts and software access for the new employee.',
                'is_default' => true,
                'status' => true
            ],
            [
                'name' => 'HR Documentation',
                'description' => 'Complete all required HR paperwork including employment contract, tax forms, emergency contacts, and benefits enrollment.',
                'is_default' => true,
                'status' => true
            ],
            [
                'name' => 'Workspace Setup',
                'description' => 'Assign desk/office space, provide office supplies, and ensure workspace is ready with all necessary equipment.',
                'is_default' => true,
                'status' => true
            ],
            [
                'name' => 'Team Introduction',
                'description' => 'Introduce new employee to team members, key stakeholders, and schedule meet-and-greet sessions with colleagues.',
                'is_default' => true,
                'status' => true
            ],
            [
                'name' => 'Company Orientation',
                'description' => 'Conduct comprehensive company orientation covering mission, values, culture, policies, and organizational structure.',
                'is_default' => true,
                'status' => true
            ],
            [
                'name' => 'Role-Specific Training',
                'description' => 'Provide job-specific training, review role expectations, KPIs, and connect with direct supervisor for goal setting.',
                'is_default' => false,
                'status' => true
            ],
            [
                'name' => 'Security & Compliance',
                'description' => 'Complete security training, sign confidentiality agreements, and ensure compliance with industry regulations.',
                'is_default' => true,
                'status' => true
            ],
            [
                'name' => 'Benefits Enrollment',
                'description' => 'Review and enroll in health insurance, retirement plans, and other employee benefits programs.',
                'is_default' => true,
                'status' => true
            ],
            [
                'name' => 'Payroll Setup',
                'description' => 'Set up payroll information, direct deposit, tax withholdings, and review compensation structure.',
                'is_default' => true,
                'status' => true
            ],
            [
                'name' => 'Manager Check-in',
                'description' => 'Schedule regular check-ins with direct manager for first 90 days to ensure smooth transition and address concerns.',
                'is_default' => false,
                'status' => true
            ],
            [
                'name' => 'Buddy System Assignment',
                'description' => 'Assign an experienced employee as a buddy to help with questions and provide informal support during first month.',
                'is_default' => false,
                'status' => true
            ],
            [
                'name' => 'Company Handbook Review',
                'description' => 'Review employee handbook, code of conduct, and ensure understanding of company policies and procedures.',
                'is_default' => true,
                'status' => true
            ],
            [
                'name' => 'Emergency Procedures',
                'description' => 'Brief on emergency procedures, evacuation routes, safety protocols, and emergency contact information.',
                'is_default' => true,
                'status' => true
            ],
            [
                'name' => 'Performance Review Process',
                'description' => 'Explain performance review process, evaluation criteria, and schedule initial performance discussions.',
                'is_default' => false,
                'status' => true
            ],
            [
                'name' => 'Professional Development',
                'description' => 'Discuss career development opportunities, training programs, and create initial professional development plan.',
                'is_default' => false,
                'status' => false
            ]
        ];

        foreach ($checklists as $checklist) {
            OnboardingChecklist::create([
                'name' => $checklist['name'],
                'description' => $checklist['description'],
                'is_default' => $checklist['is_default'],
                'status' => $checklist['status'],
                'creator_id' => $userId,
                'created_by' => $userId,
            ]);
        }
    }
}
