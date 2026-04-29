<?php

namespace Workdo\SupportTicket\Database\Seeders;

use Workdo\SupportTicket\Models\Faq;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DemoFaqSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Faq::where('created_by', $userId)->exists()) {
            return;
        }

        $faqs = [
            [
                'title' => 'How do I create a new support ticket?',
                'description' => 'To create a new support ticket, navigate to the support section and click "New Ticket". Fill in the required information including subject, description, and select appropriate category like Technical Support or Billing. Our team responds within 24 hours based on ticket priority and category.',
                'created_at' => Carbon::now()->subMonths(6)->subDays(5)
            ],
            [
                'title' => 'What are your support hours and response times?',
                'description' => 'Our support team operates Monday through Friday from 9:00 AM to 6:00 PM EST. Technical Support tickets receive priority response within 4-8 hours, while General Inquiries are handled within 24 hours. Emergency issues marked as high priority receive immediate attention.',
                'created_at' => Carbon::now()->subMonths(5)->subDays(28)
            ],
            [
                'title' => 'How can I track my support ticket status?',
                'description' => 'You can track your ticket status in real-time through your account dashboard. Tickets progress through statuses: Open, In Progress, On Hold, and Closed. You will receive email notifications for each status change and when support agents add responses.',
                'created_at' => Carbon::now()->subMonths(5)->subDays(15)
            ],
            [
                'title' => 'What information should I include when reporting a bug?',
                'description' => 'When creating a Bug Report ticket, please include: detailed steps to reproduce the issue, expected vs actual behavior, browser/device information, screenshots or error messages, and your account details. This helps our technical team resolve issues faster.',
                'created_at' => Carbon::now()->subMonths(5)->subDays(2)
            ],
            [
                'title' => 'How do I upgrade my subscription plan?',
                'description' => 'You can upgrade your subscription anytime from your account dashboard under Billing & Payment section. Select your desired plan and the changes take effect immediately. For enterprise solutions, create a Feature Request ticket for custom pricing discussions.',
                'created_at' => Carbon::now()->subMonths(4)->subDays(22)
            ],
            [
                'title' => 'What payment methods do you accept for billing?',
                'description' => 'We accept all major credit cards including Visa, MasterCard, American Express, PayPal, and bank transfers for enterprise accounts. All payments are processed securely through encrypted gateways. For billing issues, use the Billing & Payment ticket category.',
                'created_at' => Carbon::now()->subMonths(4)->subDays(10)
            ],
            [
                'title' => 'How can I reset my password securely?',
                'description' => 'Click "Forgot Password" on the login page, enter your registered email address, and follow the instructions sent to your email. The reset link expires after 24 hours for security. If issues persist, create an Account Management ticket for assistance.',
                'created_at' => Carbon::now()->subMonths(4)->subDays(1)
            ],
            [
                'title' => 'Is my data secure and regularly backed up?',
                'description' => 'Yes, we implement enterprise-grade security measures. All data is encrypted in transit and at rest using AES-256 encryption. We perform automated daily backups stored in multiple secure locations. Our infrastructure is SOC 2 compliant and undergoes regular security audits.',
                'created_at' => Carbon::now()->subMonths(3)->subDays(25)
            ],
            [
                'title' => 'Can I integrate with third-party applications using APIs?',
                'description' => 'Absolutely! We provide comprehensive REST API documentation and webhooks for seamless integration with third-party applications. We also offer pre-built integrations with popular tools. For custom integrations, submit an Integration Support ticket for technical assistance.',
                'created_at' => Carbon::now()->subMonths(3)->subDays(18)
            ],
            [
                'title' => 'How do I add team members and manage permissions?',
                'description' => 'Navigate to Settings > Team Management and click "Invite Member". Enter their email address and assign appropriate roles with specific permissions. Team members receive invitation emails to join your account. For permission issues, create an Account Management ticket.',
                'created_at' => Carbon::now()->subMonths(3)->subDays(8)
            ],
            [
                'title' => 'What should I do if I experience performance issues?',
                'description' => 'If you encounter slow loading times or system lag, first clear your browser cache and cookies. Check your internet connection and try using a different browser. For persistent performance problems, create a Performance Issues ticket with detailed information about your experience.',
                'created_at' => Carbon::now()->subMonths(2)->subDays(28)
            ],
            [
                'title' => 'How do I cancel my subscription and what happens to my data?',
                'description' => 'You can cancel your subscription anytime from the billing dashboard. Your account remains active until the current billing period ends. All data is retained for 30 days after cancellation for potential reactivation. For cancellation assistance, use Billing & Payment tickets.',
                'created_at' => Carbon::now()->subMonths(2)->subDays(20)
            ],
            [
                'title' => 'Do you offer custom enterprise solutions and dedicated support?',
                'description' => 'Yes, we provide custom enterprise solutions including dedicated servers, custom integrations, specialized training, and priority support channels. Contact our sales team through a Feature Request ticket to discuss your specific requirements and receive custom pricing quotes.',
                'created_at' => Carbon::now()->subMonths(2)->subDays(12)
            ],
            [
                'title' => 'How can I access the knowledge base for self-service help?',
                'description' => 'Our comprehensive knowledge base contains detailed guides, tutorials, and troubleshooting articles organized by categories like Getting Started, API Documentation, and Best Practices. Access it through your dashboard or before creating tickets for faster resolution of common issues.',
                'created_at' => Carbon::now()->subMonths(2)->subDays(5)
            ],
            [
                'title' => 'What security measures should I implement for my account?',
                'description' => 'Enable two-factor authentication, use strong unique passwords, regularly review account activity logs, and limit team member permissions to necessary functions only. For security concerns or suspected unauthorized access, immediately create a Security Concerns ticket for urgent investigation.',
                'created_at' => Carbon::now()->subMonths(1)->subDays(25)
            ],
            [
                'title' => 'How do I request new features or enhancements?',
                'description' => 'Submit feature requests through the Feature Request ticket category with detailed descriptions of desired functionality, use cases, and business impact. Our product team reviews all requests and provides feedback on feasibility, timeline, and potential inclusion in future releases.',
                'created_at' => Carbon::now()->subMonths(1)->subDays(18)
            ],
            [
                'title' => 'What training resources are available for new users?',
                'description' => 'We offer comprehensive training resources including video tutorials, documentation guides, webinar sessions, and one-on-one training calls for enterprise customers. Access training materials through the knowledge base or request personalized training via Training & Education tickets.',
                'created_at' => Carbon::now()->subMonths(1)->subDays(10)
            ],
            [
                'title' => 'How do I export my data or generate reports?',
                'description' => 'Data export and reporting features are available in your account dashboard under the Reports section. You can export data in various formats including CSV, PDF, and Excel. For custom reporting requirements or bulk data exports, create a Technical Support ticket.',
                'created_at' => Carbon::now()->subDays(22)
            ],
            [
                'title' => 'What happens during scheduled maintenance windows?',
                'description' => 'Scheduled maintenance is performed during low-traffic hours with advance notice via email and dashboard notifications. Most maintenance occurs without service interruption. For critical updates requiring downtime, we provide detailed schedules and alternative access methods when possible.',
                'created_at' => Carbon::now()->subDays(12)
            ],
            [
                'title' => 'How can I provide feedback or suggestions for improvement?',
                'description' => 'We value your feedback! Submit suggestions through General Inquiry tickets, participate in user surveys, or join our beta testing programs for new features. Your input helps us prioritize development efforts and improve the overall user experience for our community.',
                'created_at' => Carbon::now()->subDays(3)
            ]
        ];

        foreach ($faqs as $faqData) {
            Faq::create([
                'title' => $faqData['title'],
                'description' => $faqData['description'],
                'creator_id' => $userId,
                'created_by' => $userId,
                'created_at' => $faqData['created_at'],
            ]);
        }
    }
}