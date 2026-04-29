<?php

namespace Workdo\Recruitment\Database\Seeders;

use Workdo\Recruitment\Models\RecruitmentSetting;
use Illuminate\Database\Seeder;

class DemoRecruitmentSettingSeeder extends Seeder
{
    public function run($userId): void
    {
        if (RecruitmentSetting::where('created_by', $userId)->exists()) {
            return;
        }

        // 1. Brand Settings - only title_text and footer_text
        $brandSettings = [
            [
                'key' => 'title_text',
                'value' => 'Join Our Amazing Team',
                'created_by' => $userId,
            ],
            [
                'key' => 'footer_text',
                'value' => '© ' . date('Y') . ' WorkDo Technologies. All rights reserved. Building the future together.',
                'created_by' => $userId,
            ],
        ];

        foreach ($brandSettings as $setting) {
            RecruitmentSetting::updateOrCreate(
                ['key' => $setting['key'], 'created_by' => $userId],
                ['value' => $setting['value'], 'updated_at' => now()]
            );
        }

        // 2. About Company Section
        $aboutCompanySettings = [
            [
                'key' => 'our_mission',
                'value' => 'To empower businesses with innovative technology solutions and exceptional talent.',
                'created_by' => $userId,
            ],
            [
                'key' => 'company_size',
                'value' => '500-1000 employees',
                'created_by' => $userId,
            ],
            [
                'key' => 'industry',
                'value' => 'Technology & Software Development',
                'created_by' => $userId,
            ],
        ];

        foreach ($aboutCompanySettings as $setting) {
            RecruitmentSetting::updateOrCreate(
                ['key' => $setting['key'], 'created_by' => $userId],
                ['value' => $setting['value'], 'updated_at' => now()]
            );
        }

        // 3. Application Tips Section
        $applicationTips = [
            ['title' => 'Tailor your resume to match the job requirements and highlight relevant experience'],
            ['title' => 'Write a compelling cover letter that showcases your passion for the role'],
            ['title' => 'Research our company culture and values before applying'],
            ['title' => 'Ensure your LinkedIn profile is up-to-date and professional'],
            ['title' => 'Prepare specific examples of your achievements and problem-solving skills']
        ];

        RecruitmentSetting::updateOrCreate(
            ['key' => 'application_tips', 'created_by' => $userId],
            ['value' => json_encode($applicationTips), 'updated_at' => now()]
        );

        // 4. What Happens Next Section
        $whatHappensNext = [
            [
                'title' => 'Application Review',
                'description' => 'Our HR team will carefully review your application and qualifications within 3-5 days.',
                'icon' => 'Search'
            ],
            [
                'title' => 'Initial Screening',
                'description' => 'Qualified candidates will receive a phone or video call for initial screening.',
                'icon' => 'Phone'
            ],
            [
                'title' => 'Technical Interview',
                'description' => 'Technical assessment and in-depth interview with our engineering team.',
                'icon' => 'Code2'
            ],
            [
                'title' => 'Final Decision',
                'description' => 'We will notify you of our decision and next steps within 48 hours.',
                'icon' => 'CheckCircle'
            ]
        ];

        RecruitmentSetting::updateOrCreate(
            ['key' => 'what_happens_next', 'created_by' => $userId],
            ['value' => json_encode($whatHappensNext), 'updated_at' => now()]
        );

        // 5. Need Help Section
        $needHelpSettings = [
            [
                'key' => 'need_help_description',
                'value' => 'Have questions about your application? Our HR team is here to help you succeed.',
                'created_by' => $userId,
            ],
            [
                'key' => 'need_help_email',
                'value' => 'careers@workdo.io',
                'created_by' => $userId,
            ],
            [
                'key' => 'need_help_phone',
                'value' => '+917802984720',
                'created_by' => $userId,
            ],
        ];

        foreach ($needHelpSettings as $setting) {
            RecruitmentSetting::updateOrCreate(
                ['key' => $setting['key'], 'created_by' => $userId],
                ['value' => $setting['value'], 'updated_at' => now()]
            );
        }

        // 6. Tracking FAQ Section
        $trackingFaq = [
            [
                'question' => 'How can I track my application status?',
                'answer' => 'Use your unique tracking ID provided in the confirmation email to check your application status on our careers page.'
            ],
            [
                'question' => 'How long does the recruitment process take?',
                'answer' => 'Our typical recruitment process takes 2-3 weeks from application submission to final decision, depending on the role.'
            ],
            [
                'question' => 'What should I do if I forgot my tracking ID?',
                'answer' => 'Contact our HR team at careers@workdo.io with your full name and email address used for the application.'
            ],
            [
                'question' => 'Can I apply for multiple positions simultaneously?',
                'answer' => 'Yes, you can apply for multiple positions. Each application will have its own tracking ID and status.'
            ],
            [
                'question' => 'Will I be notified if my application is unsuccessful?',
                'answer' => 'Yes, we notify all candidates about their application status via email, regardless of the outcome.'
            ]
        ];

        RecruitmentSetting::updateOrCreate(
            ['key' => 'tracking_faq', 'created_by' => $userId],
            ['value' => json_encode($trackingFaq), 'updated_at' => now()]
        );

        // 7. Dashboard Welcome Card
        $dashboardWelcomeCard = [
            'card_title' => 'Talent Acquisition Hub',
            'card_description' => 'Streamline your hiring process with our comprehensive recruitment management system. From job posting to candidate onboarding, manage your entire talent pipeline efficiently.',
            'button_text' => 'Copy Career Portal',
            'button_icon' => 'Copy'
        ];

        RecruitmentSetting::updateOrCreate(
            ['key' => 'dashboard_welcome_card', 'created_by' => $userId],
            ['value' => json_encode($dashboardWelcomeCard), 'updated_at' => now()]
        );
    }
}
