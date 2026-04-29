<?php

namespace Workdo\SupportTicket\Database\Seeders;

use Workdo\SupportTicket\Models\SupportTicketSetting;
use Illuminate\Database\Seeder;

class DemoSupportTicketSettingsSeeder extends Seeder
{
    public function run($userId): void
    {
        if (SupportTicketSetting::where('created_by', $userId)->exists()) {
            return;
        }

        if (!empty($userId)) 
        {
            $settings = [
                'terms_conditions' => json_encode([
                    'content' => '<h2>Terms and Conditions</h2>
                    <h3>1. Support Ticket Submission</h3>
                    <p>By submitting a support ticket, you agree to provide accurate information and follow our support guidelines.</p>

                    <h3>2. Response Time</h3>
                    <p>We aim to respond to all tickets within 24-48 hours during business days. Critical issues will be prioritized.</p>

                    <h3>3. Ticket Resolution</h3>
                    <p>We will work diligently to resolve your issue. Some complex problems may require additional time and collaboration.</p>

                    <h3>4. Communication</h3>
                    <p>Please respond promptly to our requests for additional information to ensure timely resolution of your ticket.</p>

                    <h3>5. Privacy</h3>
                    <p>All information provided in support tickets will be kept confidential and used only for support purposes.</p>',
                    'enabled' => true
                ]),

                'privacy_policy' => json_encode([
                    'content' => '<h2>Privacy Policy</h2>
                    <h3>Information We Collect</h3>
                    <p>We collect information you provide when submitting support tickets, including your name, email, and issue details.</p>

                    <h3>How We Use Your Information</h3>
                    <ul>
                        <li>To provide technical support and resolve issues</li>
                        <li>To send ticket updates and notifications</li>
                        <li>To improve our support services</li>
                        <li>To maintain support history for better assistance</li>
                    </ul>

                    <h3>Information Sharing</h3>
                    <p>We do not sell, trade, or share your personal information with third parties without your consent, except as required by law.</p>

                    <h3>Data Security</h3>
                    <p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>

                    <h3>Contact Us</h3>
                    <p>If you have questions about this Privacy Policy, please contact us at privacy@example.com</p>',
                    'enabled' => true
                ]),

                'faq_settings' => json_encode([
                    'faq_title' => 'Frequently Asked Questions',
                    'faq_description' => 'Find answers to common questions about our support ticket system.',
                    'faq_questions' => [
                        [
                            'title' => 'How do I submit a support ticket?',
                            'description' => 'Click on "Create Ticket" button, fill in your details, describe your issue clearly, and submit. You will receive a confirmation email with your ticket ID.'
                        ],
                        [
                            'title' => 'How can I track my ticket status?',
                            'description' => 'Use the ticket ID provided in your confirmation email to search and view your ticket status on our support portal.'
                        ],
                        [
                            'title' => 'What information should I include in my ticket?',
                            'description' => 'Please provide detailed information about your issue, including steps to reproduce, error messages, and any relevant screenshots.'
                        ],
                        [
                            'title' => 'How long does it take to get a response?',
                            'description' => 'We typically respond within 24-48 hours during business days. Critical issues are prioritized and may receive faster responses.'
                        ],
                        [
                            'title' => 'Can I update my ticket after submission?',
                            'description' => 'Yes, you can add additional information or comments to your existing ticket using the ticket ID and our support portal.'
                        ],
                        [
                            'title' => 'What are the different ticket priorities?',
                            'description' => 'We have Low, Medium, High, and Critical priority levels. Critical issues affecting system functionality are handled first.'
                        ],
                        [
                            'title' => 'Is my ticket information secure?',
                            'description' => 'Yes, all ticket information is encrypted and stored securely. Only authorized support staff can access your ticket details.'
                        ],
                        [
                            'title' => 'Can I close my ticket?',
                            'description' => 'Yes, you can request to close your ticket if your issue has been resolved. Our support team can also close resolved tickets.'
                        ]
                    ]
                ]),

                'title_text' => 'SupportHub',
                'footer_text' => '© ' . date('Y') . ' SupportHub. All rights reserved.',

                // Brand Settings
                'logo_dark' => '',
                'favicon' => '',
                'titleText' => 'SupportHub',
                'footerText' => '© ' . date('Y') . ' SupportHub. All rights reserved.',

                // Title Sections
                'title_sections' => json_encode([
                    'create_ticket' => [
                        'title' => 'Create Support Ticket',
                        'description' => 'Submit a new support request and get help from our team'
                    ],
                    'search_ticket' => [
                        'title' => 'Search Tickets',
                        'description' => 'Find and track your existing support tickets'
                    ],
                    'knowledge_base' => [
                        'title' => 'Knowledge Base',
                        'description' => 'Browse our comprehensive help documentation'
                    ],
                    'faq' => [
                        'title' => 'Frequently Asked Questions',
                        'description' => 'Quick answers to common questions'
                    ],
                    'contact' => [
                        'title' => 'Contact Support',
                        'description' => 'Get in touch with our support team directly'
                    ]
                ]),

                // CTA Sections
                'cta_sections' => json_encode([
                    'knowledge_base' => [
                        'title' => 'Need More Help?',
                        'description' => 'Browse our comprehensive knowledge base for detailed guides and tutorials'
                    ],
                    'faq' => [
                        'title' => 'Still Have Questions?',
                        'description' => 'Check out our FAQ section for quick answers to common questions'
                    ]
                ]),

                // Support Information
                'support_information' => json_encode([
                    'response_time' => 'We typically respond to all support tickets within 24-48 hours during business days. Critical issues are prioritized and may receive faster responses.',
                    'opening_hours' => '09:00',
                    'closing_hours' => '18:00',
                    'phone_support' => '+1 (555) 123-4567',
                    'business_hours' => [
                        ['day' => 'Monday', 'is_open' => true],
                        ['day' => 'Tuesday', 'is_open' => true],
                        ['day' => 'Wednesday', 'is_open' => true],
                        ['day' => 'Thursday', 'is_open' => true],
                        ['day' => 'Friday', 'is_open' => true],
                        ['day' => 'Saturday', 'is_open' => false],
                        ['day' => 'Sunday', 'is_open' => false]
                    ]
                ]),
            ];

            foreach ($settings as $key => $value) {
                SupportTicketSetting::updateOrCreate(
                    [
                        'key' => $key,
                        'created_by' => $userId
                    ],
                    [
                        'value' => $value,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}