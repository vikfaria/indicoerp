<?php

namespace Workdo\SupportTicket\Database\Seeders;

use Workdo\SupportTicket\Models\Ticket;
use Workdo\SupportTicket\Models\TicketCategory;
use Workdo\SupportTicket\Models\Conversion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DemoTicketSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Ticket::where('created_by', $userId)->exists()) {
            return;
        }

        $categories = TicketCategory::where('created_by', $userId)->pluck('id', 'name')->toArray();

        // Get users for backend tickets
        $staffUsers = User::where('created_by', $userId)->where('type', 'staff')->pluck('id')->toArray();
        $clientUsers = User::where('created_by', $userId)->where('type', 'client')->pluck('id')->toArray();
        $vendorUsers = User::where('created_by', $userId)->where('type', 'vendor')->pluck('id')->toArray();

        // Frontend Flow Tickets (10 records) - All have attachments
        $frontendTickets = [
            [
                'name' => 'Alexander Mitchell',
                'email' => 'alexander.mitchell@email.com',
                'account_type' => 'custom',
                'user_id' => null,
                'category' => 'Technical Support',
                'subject' => 'Login Authentication Issues on Mobile App',
                'status' => 'In Progress',
                'description' => '<p>I am experiencing persistent login authentication issues on the mobile application. The app crashes immediately after entering credentials and shows error code AUTH_001. This started occurring after the latest system update.</p>',
                'attachments' => 'login_error_screenshot.pdf',
                'created_at' => Carbon::now()->subMonths(6)->subDays(5)
            ],
            [
                'name' => 'Victoria Harrison',
                'email' => 'victoria.harrison@email.com',
                'account_type' => 'custom',
                'user_id' => null,
                'category' => 'Billing & Payment',
                'subject' => 'Unexpected Billing Charges on Invoice',
                'status' => 'In Progress',
                'description' => '<p>I noticed unexpected charges on my latest invoice for account #VH12345. The billing amount appears significantly higher than previous months without any plan changes. Could you please review and clarify these charges?</p>',
                'attachments' => 'payment_gateway_logs.pdf',
                'created_at' => Carbon::now()->subMonths(5)->subDays(28)
            ],
            [
                'name' => 'Benjamin Foster',
                'email' => 'benjamin.foster@email.com',
                'account_type' => 'custom',
                'user_id' => null,
                'category' => 'Feature Request',
                'subject' => 'Advanced Analytics Dashboard Enhancement',
                'status' => 'On Hold',
                'description' => '<p>Our team requires advanced analytics dashboard with real-time data visualization, custom report generation, and export functionality. This would significantly improve our workflow efficiency and decision-making processes.</p>',
                'attachments' => 'mobile_app_crash_report.pdf',
                'created_at' => Carbon::now()->subMonths(5)->subDays(20)
            ],
            [
                'name' => 'Catherine Reynolds',
                'email' => 'catherine.reynolds@email.com',
                'account_type' => 'custom',
                'user_id' => null,
                'category' => 'Bug Report',
                'subject' => 'Data Export Function Not Working Properly',
                'status' => 'In Progress',
                'description' => '<p>The data export function is not working correctly. When attempting to export large datasets, the process fails with timeout errors. Small exports work fine, but anything over 1000 records fails consistently.</p>',
                'attachments' => 'data_export_sample.pdf',
                'created_at' => Carbon::now()->subMonths(5)->subDays(12)
            ],
            [
                'name' => 'Nicholas Patterson',
                'email' => 'nicholas.patterson@email.com',
                'account_type' => 'custom',
                'user_id' => null,
                'category' => 'Account Management',
                'subject' => 'Team Member Access Permission Issues',
                'status' => 'Closed',
                'description' => '<p>Several team members are unable to access specific modules despite having appropriate permissions assigned. The permission system seems to have inconsistencies that prevent proper access control management.</p>',
                'attachments' => 'permission_matrix_document.pdf',
                'created_at' => Carbon::now()->subMonths(4)->subDays(25)
            ],
            [
                'name' => 'Stephanie Coleman',
                'email' => 'stephanie.coleman@email.com',
                'account_type' => 'custom',
                'user_id' => null,
                'category' => 'Integration Support',
                'subject' => 'Third-Party API Integration Configuration',
                'status' => 'In Progress',
                'description' => '<p>Need assistance configuring third-party API integration with Salesforce and Slack. The webhook setup is not triggering properly, and data synchronization is failing intermittently with authentication errors.</p>',
                'attachments' => 'api_integration_config.pdf',
                'created_at' => Carbon::now()->subMonths(4)->subDays(18)
            ],
            [
                'name' => 'Christopher Hughes',
                'email' => 'christopher.hughes@email.com',
                'account_type' => 'custom',
                'user_id' => null,
                'category' => 'Performance Issues',
                'subject' => 'System Response Time Degradation',
                'status' => 'On Hold',
                'description' => '<p>Experiencing significant system response time degradation during peak hours. Page load times have increased from 2-3 seconds to 15-20 seconds, affecting user productivity and overall system usability.</p>',
                'attachments' => 'performance_monitoring_report.pdf',
                'created_at' => Carbon::now()->subMonths(4)->subDays(10)
            ],
            [
                'name' => 'Amanda Richardson',
                'email' => 'amanda.richardson@email.com',
                'account_type' => 'custom',
                'user_id' => null,
                'category' => 'Security Concerns',
                'subject' => 'Suspicious Account Activity Detection',
                'status' => 'In Progress',
                'description' => '<p>Detected suspicious login attempts from unknown IP addresses and unusual account activity patterns. Need immediate security review and implementation of additional protection measures for account safety.</p>',
                'attachments' => 'security_audit_findings.pdf',
                'created_at' => Carbon::now()->subMonths(3)->subDays(22)
            ],
            [
                'name' => 'Jonathan Cooper',
                'email' => 'jonathan.cooper@email.com',
                'account_type' => 'custom',
                'user_id' => null,
                'category' => 'Training & Education',
                'subject' => 'Advanced User Training Session Request',
                'status' => 'Closed',
                'description' => '<p>Requesting comprehensive training session for advanced features including workflow automation, custom reporting, and system administration. Our team needs hands-on training to maximize platform utilization and efficiency.</p>',
                'attachments' => 'training_requirements_outline.pdf',
                'created_at' => Carbon::now()->subMonths(3)->subDays(15)
            ],
            [
                'name' => 'Rebecca Morgan',
                'email' => 'rebecca.morgan@email.com',
                'account_type' => 'custom',
                'user_id' => null,
                'category' => 'General Inquiry',
                'subject' => 'Enterprise Plan Migration Information',
                'status' => 'Closed',
                'description' => '<p>Seeking detailed information about migrating from current plan to enterprise plan. Need clarification on data migration process, feature differences, pricing structure, and implementation timeline for smooth transition.</p>',
                'attachments' => 'enterprise_migration_checklist.pdf',
                'created_at' => Carbon::now()->subMonths(3)->subDays(8)
            ]
        ];

        // Backend Flow Tickets (10 records) - 5 have attachments
        $backendTickets = [
            [
                'name' => 'Matthew Stewart',
                'email' => 'matthew.stewart@email.com',
                'account_type' => 'staff',
                'user_id' => !empty($staffUsers) ? $staffUsers[array_rand($staffUsers)] : null,
                'category' => 'Technical Support',
                'subject' => 'Database Connection Pool Optimization',
                'status' => 'In Progress',
                'description' => '<p>Backend database connection pool requires optimization for handling increased concurrent user load. Current configuration is causing connection timeouts and affecting system performance during peak usage periods.</p>',
                'attachments' => 'database_optimization_analysis.pdf',
                'created_at' => Carbon::now()->subMonths(2)->subDays(25)
            ],
            [
                'name' => 'Elizabeth Turner',
                'email' => 'elizabeth.turner@email.com',
                'account_type' => 'client',
                'user_id' => !empty($clientUsers) ? $clientUsers[array_rand($clientUsers)] : null,
                'category' => 'Billing & Payment',
                'subject' => 'Automated Billing System Configuration',
                'status' => 'In Progress',
                'description' => '<p>Need assistance configuring automated billing system for recurring subscriptions. The current setup is not processing prorated charges correctly and requires backend payment gateway integration adjustments.</p>',
                'attachments' => 'billing_system_configuration.pdf',
                'created_at' => Carbon::now()->subMonths(2)->subDays(18)
            ],
            [
                'name' => 'Daniel Phillips',
                'email' => 'daniel.phillips@email.com',
                'account_type' => 'vendor',
                'user_id' => !empty($vendorUsers) ? $vendorUsers[array_rand($vendorUsers)] : null,
                'category' => 'Feature Request',
                'subject' => 'Custom API Endpoint Development',
                'status' => 'On Hold',
                'description' => '<p>Requesting development of custom API endpoints for specialized data processing workflows. Need backend implementation for bulk data operations, custom filtering, and advanced query capabilities.</p>',
                'attachments' => 'custom_api_specifications.pdf',
                'created_at' => Carbon::now()->subMonths(2)->subDays(12)
            ],
            [
                'name' => 'Michelle Campbell',
                'email' => 'michelle.campbell@email.com',
                'account_type' => 'staff',
                'user_id' => !empty($staffUsers) ? $staffUsers[array_rand($staffUsers)] : null,
                'category' => 'Bug Report',
                'subject' => 'Memory Leak in Background Processing',
                'status' => 'In Progress',
                'description' => '<p>Identified memory leak in background processing jobs causing server instability. The issue occurs during large data processing tasks and requires immediate backend code review and optimization.</p>',
                'attachments' => 'memory_leak_diagnostic_report.pdf',
                'created_at' => Carbon::now()->subMonths(2)->subDays(5)
            ],
            [
                'name' => 'Anthony Parker',
                'email' => 'anthony.parker@email.com',
                'account_type' => 'client',
                'user_id' => !empty($clientUsers) ? $clientUsers[array_rand($clientUsers)] : null,
                'category' => 'Account Management',
                'subject' => 'Multi-Tenant Architecture Implementation',
                'status' => 'Closed',
                'description' => '<p>Implementing multi-tenant architecture for enterprise deployment. Need backend configuration for tenant isolation, data segregation, and scalable user management across multiple organizational units.</p>',
                'attachments' => 'multi_tenant_architecture_plan.pdf',
                'created_at' => Carbon::now()->subMonths(1)->subDays(28)
            ],
            [
                'name' => 'Samantha Evans',
                'email' => 'samantha.evans@email.com',
                'account_type' => 'vendor',
                'user_id' => !empty($vendorUsers) ? $vendorUsers[array_rand($vendorUsers)] : null,
                'category' => 'Integration Support',
                'subject' => 'Enterprise SSO Integration Setup',
                'status' => 'In Progress',
                'description' => '<p>Configuring enterprise Single Sign-On integration with Active Directory and LDAP. Backend authentication system requires modification to support SAML protocols and user provisioning automation.</p>',
                'attachments' => '[]',
                'created_at' => Carbon::now()->subMonths(1)->subDays(20)
            ],
            [
                'name' => 'William Roberts',
                'email' => 'william.roberts@email.com',
                'account_type' => 'staff',
                'user_id' => !empty($staffUsers) ? $staffUsers[array_rand($staffUsers)] : null,
                'category' => 'Performance Issues',
                'subject' => 'Query Optimization for Large Datasets',
                'status' => 'On Hold',
                'description' => '<p>Backend database queries are performing poorly with large datasets exceeding 100K records. Need query optimization, indexing strategy review, and caching implementation for improved performance.</p>',
                'attachments' => '[]',
                'created_at' => Carbon::now()->subMonths(1)->subDays(15)
            ],
            [
                'name' => 'Jessica Martinez',
                'email' => 'jessica.martinez@email.com',
                'account_type' => 'client',
                'user_id' => !empty($clientUsers) ? $clientUsers[array_rand($clientUsers)] : null,
                'category' => 'Security Concerns',
                'subject' => 'Backend Security Audit and Hardening',
                'status' => 'In Progress',
                'description' => '<p>Comprehensive backend security audit required including vulnerability assessment, code review, and security hardening implementation. Need to ensure compliance with SOC 2 and GDPR requirements.</p>',
                'attachments' => '[]',
                'created_at' => Carbon::now()->subMonths(1)->subDays(8)
            ],
            [
                'name' => 'Ryan Anderson',
                'email' => 'ryan.anderson@email.com',
                'account_type' => 'vendor',
                'user_id' => !empty($vendorUsers) ? $vendorUsers[array_rand($vendorUsers)] : null,
                'category' => 'Training & Education',
                'subject' => 'Backend System Administration Training',
                'status' => 'Closed',
                'description' => '<p>Requesting comprehensive backend system administration training covering server management, database optimization, monitoring setup, and troubleshooting procedures for technical team members.</p>',
                'attachments' => '[]',
                'created_at' => Carbon::now()->subDays(22)
            ],
            [
                'name' => 'Lauren Thompson',
                'email' => 'lauren.thompson@email.com',
                'account_type' => 'staff',
                'user_id' => !empty($staffUsers) ? $staffUsers[array_rand($staffUsers)] : null,
                'category' => 'General Inquiry',
                'subject' => 'System Architecture Scalability Planning',
                'status' => 'Closed',
                'description' => '<p>Planning system architecture scalability for anticipated user growth. Need guidance on backend infrastructure scaling, load balancing configuration, and distributed system implementation strategies.</p>',
                'attachments' => '[]',
                'created_at' => Carbon::now()->subDays(10)
            ]
        ];

        $allTickets = array_merge($frontendTickets, $backendTickets);
        $ticketCounter = 1;

        foreach ($allTickets as $ticketData) {
            $categoryId = $categories[$ticketData['category']] ?? array_values($categories)[0];
            $ticketCreatedAt = $ticketData['created_at']->copy()->addHours(rand(1, 5))->addMinutes(rand(1, 59))->addSeconds(rand(1, 59));

            // Determine creator_id based on account type and user_id
            $creatorId = $ticketData['user_id'] ?? $userId;
            
            $ticket = Ticket::create([
                'ticket_id' => time() + $ticketCounter,
                'name' => $ticketData['name'],
                'email' => $ticketData['email'],
                'user_id' => $ticketData['user_id'],
                'account_type' => $ticketData['account_type'],
                'category' => $categoryId,
                'subject' => $ticketData['subject'],
                'status' => $ticketData['status'],
                'description' => $ticketData['description'],
                'attachments' => $ticketData['attachments'] === '[]' ? null : json_encode([['name' => $ticketData['attachments'], 'path' => $ticketData['attachments']]]),
                'creator_id' => $creatorId,
                'created_by' => $userId,
                'created_at' => $ticketCreatedAt,
            ]);

            // Add realistic conversations for tickets with appropriate status
            if (in_array($ticketData['status'], ['In Progress', 'Closed'])) {
                $conversationData = $this->getConversationForTicket($ticketData, $ticketCounter);
                $conversationCreatedAt = $ticketCreatedAt->copy()->addHours(rand(1, 3))->addMinutes(rand(5, 45))->addSeconds(rand(10, 59));

                Conversion::create([
                    'ticket_id' => $ticket->id,
                    'description' => $conversationData['description'],
                    'sender' => $userId,
                    'attachments' => null,
                    'creator_id' => $userId,
                    'created_by' => $userId,
                    'created_at' => $conversationCreatedAt,
                ]);
            }

            $ticketCounter++;
        }
    }

    private function getConversationForTicket($ticketData, $counter)
    {
        $conversations = [
            'Technical Support' => [
                'description' => '<p>Thank you for reporting this technical issue. Our development team has identified the root cause and is working on a fix.</p>',
                'attachments' => '[]'
            ],
            'Billing & Payment' => [
                'description' => '<p>We have reviewed your billing inquiry and found the discrepancy. A credit adjustment has been processed and will reflect in your next invoice.</p>',
                'attachments' => '[]'
            ],
            'Feature Request' => [
                'description' => '<p>Your feature request has been reviewed by our product team. We have added it to our development roadmap for the next quarter.</p>',
                'attachments' => '[]'
            ],
            'Bug Report' => [
                'description' => '<p>Thank you for the detailed bug report. We have reproduced the issue and implemented a fix. The update has been deployed to production.</p>',
                'attachments' => '[]'
            ],
            'Account Management' => [
                'description' => '<p>Your account management request has been processed. The permission settings have been updated and team members should now have proper access.</p>',
                'attachments' => '[]'
            ],
            'Integration Support' => [
                'description' => '<p>Our integration team has reviewed your setup and provided the necessary configuration updates. The API integration should now work correctly.</p>',
                'attachments' => '[]'
            ],
            'Performance Issues' => [
                'description' => '<p>We have identified the performance bottleneck and implemented optimization measures. System response times should be significantly improved.</p>',
                'attachments' => '[]'
            ],
            'Security Concerns' => [
                'description' => '<p>We have investigated the security concern and implemented additional protection measures. Your account is now secure with enhanced monitoring.</p>',
                'attachments' => '[]'
            ],
            'Training & Education' => [
                'description' => '<p>Your training session has been scheduled with our expert team. You will receive detailed training materials and hands-on guidance.</p>',
                'attachments' => '[]'
            ],
            'General Inquiry' => [
                'description' => '<p>Thank you for your inquiry. We have provided comprehensive information about your request. Our sales team will follow up with details.</p>',
                'attachments' => '[]'
            ]
        ];

        return $conversations[$ticketData['category']] ?? [
            'description' => '<p>Thank you for contacting us. We have received your request and our team is reviewing it. We will get back to you shortly.</p>',
            'attachments' => '[]'
        ];
    }
}
