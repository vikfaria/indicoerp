<?php

namespace Workdo\SupportTicket\Database\Seeders;

use Workdo\SupportTicket\Models\Contact;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DemoContactSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Contact::where('created_by', $userId)->exists()) {
            return;
        }

        $contacts = [
            [
                'first_name' => 'Alexander',
                'last_name' => 'Mitchell',
                'email' => 'alexander.mitchell@contact.com',
                'subject' => 'Technical Support Integration Inquiry',
                'message' => 'We are evaluating your support ticket system for our enterprise needs. Could you provide information about API integration capabilities, custom field support, and multi-language features? Our team handles over 1000 tickets monthly.',
                'created_at' => Carbon::now()->subMonths(6)->subDays(2)
            ],
            [
                'first_name' => 'Victoria',
                'last_name' => 'Harrison',
                'email' => 'victoria.harrison@contact.com',
                'subject' => 'Billing System Integration Questions',
                'message' => 'Our billing department needs to integrate with your support system. We require automated ticket creation for payment issues, subscription changes, and invoice disputes. What billing integrations do you currently support?',
                'created_at' => Carbon::now()->subMonths(5)->subDays(25)
            ],
            [
                'first_name' => 'Benjamin',
                'last_name' => 'Foster',
                'email' => 'benjamin.foster@contact.com',
                'subject' => 'Feature Request System Demo',
                'message' => 'We need a robust feature request management system. Can your platform handle feature voting, roadmap planning, and customer feedback collection? We would like to schedule a demo to see these capabilities.',
                'created_at' => Carbon::now()->subMonths(5)->subDays(18)
            ],
            [
                'first_name' => 'Catherine',
                'last_name' => 'Reynolds',
                'email' => 'catherine.reynolds@contact.com',
                'subject' => 'Bug Tracking Workflow Setup',
                'message' => 'Our development team needs an efficient bug tracking workflow. We require integration with GitHub, automated severity classification, and developer assignment features. Can your system handle complex bug reporting workflows?',
                'created_at' => Carbon::now()->subMonths(5)->subDays(10)
            ],
            [
                'first_name' => 'Nicholas',
                'last_name' => 'Patterson',
                'email' => 'nicholas.patterson@contact.com',
                'subject' => 'Account Management Portal Requirements',
                'message' => 'We need a comprehensive account management system for our customers. This includes profile management, subscription handling, team member invitations, and permission controls. What account management features do you offer?',
                'created_at' => Carbon::now()->subMonths(4)->subDays(28)
            ],
            [
                'first_name' => 'Stephanie',
                'last_name' => 'Coleman',
                'email' => 'stephanie.coleman@contact.com',
                'subject' => 'Integration Support for Third-Party Tools',
                'message' => 'Our company uses multiple third-party tools including Slack, Jira, and Salesforce. We need seamless integration support for ticket synchronization, notifications, and data sharing. What integration options are available?',
                'created_at' => Carbon::now()->subMonths(4)->subDays(20)
            ],
            [
                'first_name' => 'Christopher',
                'last_name' => 'Hughes',
                'email' => 'christopher.hughes@contact.com',
                'subject' => 'Performance Monitoring and Analytics',
                'message' => 'We require detailed performance monitoring for our support operations. This includes response time analytics, agent productivity metrics, and customer satisfaction tracking. What reporting and analytics features do you provide?',
                'created_at' => Carbon::now()->subMonths(4)->subDays(12)
            ],
            [
                'first_name' => 'Amanda',
                'last_name' => 'Richardson',
                'email' => 'amanda.richardson@contact.com',
                'subject' => 'Security Compliance and Data Protection',
                'message' => 'Our organization has strict security requirements including SOC 2 compliance, GDPR adherence, and data encryption standards. Can you provide detailed information about your security measures and compliance certifications?',
                'created_at' => Carbon::now()->subMonths(3)->subDays(25)
            ],
            [
                'first_name' => 'Jonathan',
                'last_name' => 'Cooper',
                'email' => 'jonathan.cooper@contact.com',
                'subject' => 'Training Resources and User Education',
                'message' => 'We need comprehensive training resources for our support team. This includes video tutorials, documentation guides, best practices, and certification programs. What training and education materials do you offer?',
                'created_at' => Carbon::now()->subMonths(3)->subDays(18)
            ],
            [
                'first_name' => 'Rebecca',
                'last_name' => 'Morgan',
                'email' => 'rebecca.morgan@contact.com',
                'subject' => 'General Inquiry About Platform Capabilities',
                'message' => 'We are researching support platforms for our growing business. Could you provide an overview of your core features, pricing structure, and implementation timeline? We serve approximately 10,000 customers across multiple regions.',
                'created_at' => Carbon::now()->subMonths(3)->subDays(8)
            ],
            [
                'first_name' => 'Matthew',
                'last_name' => 'Stewart',
                'email' => 'matthew.stewart@contact.com',
                'subject' => 'Knowledge Base Management System',
                'message' => 'We need a robust knowledge base system with article management, category organization, search functionality, and analytics. Can your platform handle large-scale documentation with version control and collaborative editing features?',
                'created_at' => Carbon::now()->subMonths(2)->subDays(28)
            ],
            [
                'first_name' => 'Elizabeth',
                'last_name' => 'Turner',
                'email' => 'elizabeth.turner@contact.com',
                'subject' => 'Multi-Language Support Requirements',
                'message' => 'Our global customer base requires multi-language support for tickets, knowledge base articles, and user interfaces. We need support for English, Spanish, French, German, and Japanese. What internationalization features do you offer?',
                'created_at' => Carbon::now()->subMonths(2)->subDays(20)
            ],
            [
                'first_name' => 'Daniel',
                'last_name' => 'Phillips',
                'email' => 'daniel.phillips@contact.com',
                'subject' => 'Automated Workflow and Escalation Rules',
                'message' => 'We need sophisticated automation including ticket routing, escalation rules, SLA management, and automated responses. Our support process requires complex workflow automation based on ticket categories and customer tiers.',
                'created_at' => Carbon::now()->subMonths(2)->subDays(15)
            ],
            [
                'first_name' => 'Michelle',
                'last_name' => 'Campbell',
                'email' => 'michelle.campbell@contact.com',
                'subject' => 'Customer Portal and Self-Service Options',
                'message' => 'We want to implement a comprehensive customer portal with self-service options, ticket tracking, knowledge base access, and account management. What customer-facing features and customization options are available?',
                'created_at' => Carbon::now()->subMonths(2)->subDays(8)
            ],
            [
                'first_name' => 'Anthony',
                'last_name' => 'Parker',
                'email' => 'anthony.parker@contact.com',
                'subject' => 'Enterprise Deployment and Scalability',
                'message' => 'We are planning an enterprise deployment for 500+ support agents across multiple departments. We need information about scalability, performance optimization, load balancing, and high availability configurations.',
                'created_at' => Carbon::now()->subMonths(1)->subDays(25)
            ],
            [
                'first_name' => 'Samantha',
                'last_name' => 'Evans',
                'email' => 'samantha.evans@contact.com',
                'subject' => 'Mobile Application and Remote Access',
                'message' => 'Our support team requires mobile access for ticket management, customer communication, and real-time notifications. Do you offer native mobile applications with full functionality for iOS and Android platforms?',
                'created_at' => Carbon::now()->subMonths(1)->subDays(18)
            ],
            [
                'first_name' => 'William',
                'last_name' => 'Roberts',
                'email' => 'william.roberts@contact.com',
                'subject' => 'Data Migration and System Integration',
                'message' => 'We are migrating from our current support system and need assistance with data migration, system integration, and user training. What migration tools and professional services do you provide for enterprise customers?',
                'created_at' => Carbon::now()->subMonths(1)->subDays(10)
            ],
            [
                'first_name' => 'Jessica',
                'last_name' => 'Martinez',
                'email' => 'jessica.martinez@contact.com',
                'subject' => 'Custom Branding and White-Label Solutions',
                'message' => 'We need a white-label solution with custom branding, domain configuration, and personalized user interfaces. Our brand guidelines require specific color schemes, logos, and styling. What customization options are available?',
                'created_at' => Carbon::now()->subDays(22)
            ],
            [
                'first_name' => 'Ryan',
                'last_name' => 'Anderson',
                'email' => 'ryan.anderson@contact.com',
                'subject' => 'API Documentation and Developer Resources',
                'message' => 'Our development team needs comprehensive API documentation, SDKs, and developer resources for custom integrations. We require REST API access, webhook support, and detailed technical documentation for implementation.',
                'created_at' => Carbon::now()->subDays(15)
            ],
            [
                'first_name' => 'Lauren',
                'last_name' => 'Thompson',
                'email' => 'lauren.thompson@contact.com',
                'subject' => 'Pricing and Implementation Timeline',
                'message' => 'We are finalizing our vendor selection and need detailed pricing information for enterprise features, implementation timeline, training schedule, and ongoing support options. Could you provide a comprehensive proposal for our requirements?',
                'created_at' => Carbon::now()->subDays(5)
            ]
        ];

        foreach ($contacts as $contactData) {
            Contact::create([
                'name' => $contactData['first_name'] . ' ' . $contactData['last_name'],
                'first_name' => $contactData['first_name'],
                'last_name' => $contactData['last_name'],
                'email' => $contactData['email'],
                'subject' => $contactData['subject'],
                'message' => $contactData['message'],
                'creator_id' => $userId,
                'created_by' => $userId,
                'created_at' => $contactData['created_at'],
            ]);
        }
    }
}