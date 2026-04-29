<?php

namespace Workdo\SupportTicket\Database\Seeders;

use Workdo\SupportTicket\Models\KnowledgeBase;
use Workdo\SupportTicket\Models\KnowledgeBaseCategory;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DemoKnowledgeBaseSeeder extends Seeder
{
    public function run($userId): void
    {
        if (KnowledgeBase::where('created_by', $userId)->exists()) {
            return;
        }

        $categories = KnowledgeBaseCategory::where('created_by', $userId)->pluck('title')->toArray();
        
        $articles = [
            [
                'title' => 'Creating Your First Support Ticket',
                'category' => 'Getting Started Guide',
                'description' => '<h2>Welcome to Support Ticket System</h2><p>This comprehensive guide will walk you through creating your first support ticket. Learn how to provide detailed information, select appropriate categories, and track your ticket status effectively.</p><h3>Step 1: Access Ticket Creation</h3><p>Navigate to the support portal and click "Create New Ticket" to begin the process.</p><h3>Step 2: Fill Required Information</h3><p>Complete all mandatory fields including subject, description, and category selection for faster resolution.</p>',
                'created_at' => Carbon::now()->subMonths(6)->subDays(3)
            ],
            [
                'title' => 'Understanding Ticket Categories and Priority Levels',
                'category' => 'Getting Started Guide',
                'description' => '<h2>Ticket Classification System</h2><p>Learn about different ticket categories including Technical Support, Billing, Feature Requests, and Bug Reports. Understanding priority levels helps ensure your issues receive appropriate attention and response times.</p><h3>Category Types</h3><p>Each category has specific workflows and assigned support specialists for efficient handling.</p><h3>Priority Guidelines</h3><p>Critical issues affecting system functionality receive immediate attention, while general inquiries follow standard response times.</p>',
                'created_at' => Carbon::now()->subMonths(5)->subDays(28)
            ],
            [
                'title' => 'Managing Subscription Plans and Billing Cycles',
                'category' => 'Billing & Subscription Management',
                'description' => '<h2>Subscription Management Dashboard</h2><p>Access your billing dashboard to manage subscription plans, view payment history, and update billing information. Learn about upgrade options, prorated billing, and cancellation policies for complete control over your account.</p><h3>Plan Upgrades</h3><p>Upgrade your subscription anytime with immediate access to new features and increased limits.</p><h3>Payment Methods</h3><p>Securely manage multiple payment methods including credit cards, PayPal, and bank transfers for enterprise accounts.</p>',
                'created_at' => Carbon::now()->subMonths(5)->subDays(20)
            ],
            [
                'title' => 'Invoice Management and Payment Processing',
                'category' => 'Billing & Subscription Management',
                'description' => '<h2>Invoice and Payment System</h2><p>Comprehensive guide to viewing invoices, understanding billing details, and managing payment processing. Learn about automatic billing, payment failures, and dispute resolution procedures for seamless financial management.</p><h3>Invoice Access</h3><p>All invoices are available in PDF format with detailed breakdowns of charges and applicable taxes.</p><h3>Payment Troubleshooting</h3><p>Resolve common payment issues including declined cards, expired payment methods, and billing address mismatches.</p>',
                'created_at' => Carbon::now()->subMonths(5)->subDays(12)
            ],
            [
                'title' => 'Submitting Feature Requests and Enhancement Ideas',
                'category' => 'Feature Documentation',
                'description' => '<h2>Feature Request Process</h2><p>Learn how to submit detailed feature requests that help our product team understand your needs. Include use cases, business impact, and technical requirements to increase the likelihood of implementation in future releases.</p><h3>Request Guidelines</h3><p>Provide clear descriptions, mockups if available, and explain how the feature would benefit your workflow.</p><h3>Tracking Progress</h3><p>Monitor the status of your feature requests through our product roadmap and receive updates on development progress.</p>',
                'created_at' => Carbon::now()->subMonths(5)->subDays(5)
            ],
            [
                'title' => 'Feature Voting and Community Feedback System',
                'category' => 'Feature Documentation',
                'description' => '<h2>Community-Driven Development</h2><p>Participate in our feature voting system to influence product development priorities. Vote on existing requests, comment with additional insights, and collaborate with other users to refine feature specifications.</p><h3>Voting Process</h3><p>Each user can vote on multiple features, with votes weighted based on subscription tier and usage patterns.</p><h3>Community Discussion</h3><p>Engage in constructive discussions about feature implementations and share real-world use cases with the community.</p>',
                'created_at' => Carbon::now()->subMonths(4)->subDays(25)
            ],
            [
                'title' => 'Effective Bug Reporting and Issue Documentation',
                'category' => 'Troubleshooting & Bug Fixes',
                'description' => '<h2>Bug Reporting Best Practices</h2><p>Master the art of effective bug reporting to help our development team quickly identify and resolve issues. Learn what information to include, how to reproduce problems, and how to provide useful screenshots and logs.</p><h3>Essential Information</h3><p>Include browser details, operating system, steps to reproduce, expected vs actual behavior, and any error messages.</p><h3>Reproduction Steps</h3><p>Provide clear, numbered steps that allow our team to consistently reproduce the issue in our testing environment.</p>',
                'created_at' => Carbon::now()->subMonths(4)->subDays(18)
            ],
            [
                'title' => 'Common Browser Issues and Quick Fixes',
                'category' => 'Troubleshooting & Bug Fixes',
                'description' => '<h2>Browser Compatibility Solutions</h2><p>Resolve common browser-related issues including cache problems, JavaScript errors, and compatibility issues. Learn about supported browsers, recommended settings, and troubleshooting steps for optimal performance across different platforms.</p><h3>Cache Management</h3><p>Clear browser cache and cookies regularly to prevent display issues and ensure you see the latest updates.</p><h3>Browser Settings</h3><p>Enable JavaScript, allow cookies, and disable ad blockers for full functionality of all platform features.</p>',
                'created_at' => Carbon::now()->subMonths(4)->subDays(10)
            ],
            [
                'title' => 'Account Setup and Profile Configuration',
                'category' => 'Account Setup & Configuration',
                'description' => '<h2>Complete Account Configuration</h2><p>Set up your account for optimal performance with proper profile configuration, security settings, and preference management. Learn about two-factor authentication, notification settings, and team member management for comprehensive account control.</p><h3>Security Configuration</h3><p>Enable two-factor authentication and set up strong passwords to protect your account from unauthorized access.</p><h3>Profile Customization</h3><p>Configure your profile with contact information, preferences, and notification settings to personalize your experience.</p>',
                'created_at' => Carbon::now()->subMonths(4)->subDays(2)
            ],
            [
                'title' => 'Team Management and User Permissions',
                'category' => 'Account Setup & Configuration',
                'description' => '<h2>Team Collaboration Setup</h2><p>Manage team members effectively with proper role assignments, permission controls, and collaboration settings. Learn about different user roles, access levels, and how to maintain security while enabling team productivity.</p><h3>Role Management</h3><p>Assign appropriate roles including Admin, Manager, Agent, and Viewer with specific permissions for each team member.</p><h3>Access Control</h3><p>Configure granular permissions to control what team members can view, edit, and manage within your account.</p>',
                'created_at' => Carbon::now()->subMonths(3)->subDays(22)
            ],
            [
                'title' => 'API Integration and Webhook Configuration',
                'category' => 'Third-Party Integrations',
                'description' => '<h2>API Integration Guide</h2><p>Integrate your applications with our comprehensive REST API and webhook system. Learn about authentication methods, rate limiting, endpoint documentation, and best practices for reliable integrations with your existing workflow tools.</p><h3>Authentication Setup</h3><p>Generate API keys and configure OAuth authentication for secure access to your account data and functionality.</p><h3>Webhook Configuration</h3><p>Set up webhooks to receive real-time notifications about ticket updates, status changes, and other important events.</p>',
                'created_at' => Carbon::now()->subMonths(3)->subDays(15)
            ],
            [
                'title' => 'Slack and Microsoft Teams Integration',
                'category' => 'Third-Party Integrations',
                'description' => '<h2>Communication Platform Integration</h2><p>Connect your support system with Slack and Microsoft Teams for seamless communication and notification management. Configure channels, set up automated notifications, and enable team collaboration directly from your chat platforms.</p><h3>Slack Setup</h3><p>Install our Slack app and configure channels for different ticket categories and priority levels.</p><h3>Teams Integration</h3><p>Connect Microsoft Teams to receive notifications and manage tickets directly from your team collaboration workspace.</p>',
                'created_at' => Carbon::now()->subMonths(3)->subDays(8)
            ],
            [
                'title' => 'System Performance Monitoring and Optimization',
                'category' => 'Performance Optimization',
                'description' => '<h2>Performance Monitoring Dashboard</h2><p>Monitor system performance with comprehensive analytics including response times, ticket resolution metrics, and agent productivity statistics. Learn how to identify bottlenecks, optimize workflows, and improve overall support efficiency.</p><h3>Key Metrics</h3><p>Track important KPIs including first response time, resolution time, customer satisfaction scores, and ticket volume trends.</p><h3>Optimization Strategies</h3><p>Implement best practices for faster ticket resolution, automated workflows, and improved customer experience.</p>',
                'created_at' => Carbon::now()->subMonths(2)->subDays(25)
            ],
            [
                'title' => 'Database Optimization and Scaling Strategies',
                'category' => 'Performance Optimization',
                'description' => '<h2>Enterprise Performance Scaling</h2><p>Optimize database performance for high-volume support operations with proper indexing, query optimization, and scaling strategies. Learn about load balancing, caching mechanisms, and performance tuning for enterprise-level deployments.</p><h3>Database Tuning</h3><p>Implement proper indexing strategies and query optimization techniques for faster data retrieval and processing.</p><h3>Scaling Solutions</h3><p>Configure load balancing and horizontal scaling to handle increased ticket volumes and user concurrency.</p>',
                'created_at' => Carbon::now()->subMonths(2)->subDays(18)
            ],
            [
                'title' => 'Data Security and Compliance Framework',
                'category' => 'Security Best Practices',
                'description' => '<h2>Comprehensive Security Implementation</h2><p>Implement robust security measures including data encryption, access controls, and compliance frameworks. Learn about GDPR compliance, SOC 2 requirements, and industry-specific security standards for protecting sensitive customer information.</p><h3>Encryption Standards</h3><p>All data is encrypted using AES-256 encryption both in transit and at rest for maximum security protection.</p><h3>Compliance Requirements</h3><p>Meet industry standards including GDPR, HIPAA, and SOC 2 with built-in compliance tools and audit trails.</p>',
                'created_at' => Carbon::now()->subMonths(2)->subDays(10)
            ],
            [
                'title' => 'Access Control and Authentication Systems',
                'category' => 'Security Best Practices',
                'description' => '<h2>Advanced Authentication Setup</h2><p>Configure multi-factor authentication, single sign-on integration, and role-based access controls for enhanced security. Learn about session management, password policies, and security monitoring to protect your support system from unauthorized access.</p><h3>Multi-Factor Authentication</h3><p>Enable MFA using authenticator apps, SMS verification, or hardware tokens for additional security layers.</p><h3>SSO Integration</h3><p>Connect with enterprise identity providers including Active Directory, Okta, and Auth0 for centralized authentication.</p>',
                'created_at' => Carbon::now()->subMonths(2)->subDays(3)
            ],
            [
                'title' => 'Comprehensive Training Program and Certification',
                'category' => 'User Training Resources',
                'description' => '<h2>Professional Development Program</h2><p>Access our comprehensive training program including video tutorials, interactive workshops, and certification courses. Develop expertise in support best practices, system administration, and advanced features to maximize your team productivity and effectiveness.</p><h3>Certification Tracks</h3><p>Complete certification programs for different roles including Support Agent, System Administrator, and Team Manager.</p><h3>Continuous Learning</h3><p>Access regularly updated training materials, webinars, and best practice guides to stay current with new features.</p>',
                'created_at' => Carbon::now()->subMonths(1)->subDays(22)
            ],
            [
                'title' => 'Advanced Workflow Automation and Customization',
                'category' => 'User Training Resources',
                'description' => '<h2>Workflow Automation Mastery</h2><p>Master advanced workflow automation including custom triggers, conditional logic, and automated responses. Learn how to create sophisticated routing rules, escalation procedures, and integration workflows that streamline your support operations.</p><h3>Automation Rules</h3><p>Create complex automation rules based on ticket content, customer data, and historical patterns for intelligent routing.</p><h3>Custom Workflows</h3><p>Design custom workflows that match your specific business processes and support methodologies.</p>',
                'created_at' => Carbon::now()->subMonths(1)->subDays(15)
            ],
            [
                'title' => 'Knowledge Base Management and Content Strategy',
                'category' => 'FAQ & Common Questions',
                'description' => '<h2>Content Management Excellence</h2><p>Develop and maintain an effective knowledge base with strategic content planning, SEO optimization, and user experience design. Learn about article organization, search functionality, and analytics to create self-service resources that reduce ticket volume.</p><h3>Content Strategy</h3><p>Plan and organize knowledge base content based on common customer questions and support ticket patterns.</p><h3>Search Optimization</h3><p>Implement effective search functionality and content tagging to help users quickly find relevant information.</p>',
                'created_at' => Carbon::now()->subDays(20)
            ],
            [
                'title' => 'Customer Self-Service Portal and FAQ Optimization',
                'category' => 'FAQ & Common Questions',
                'description' => '<h2>Self-Service Excellence</h2><p>Optimize your customer self-service portal with intuitive navigation, comprehensive FAQ sections, and interactive troubleshooting guides. Learn how to reduce support ticket volume by empowering customers to find solutions independently through well-designed self-service resources.</p><h3>Portal Design</h3><p>Create user-friendly interfaces that guide customers to relevant information quickly and efficiently.</p><h3>FAQ Optimization</h3><p>Regularly update FAQ content based on support ticket trends and customer feedback for maximum effectiveness.</p>',
                'created_at' => Carbon::now()->subDays(8)
            ]
        ];

        foreach ($articles as $articleData) {
            $categoryName = in_array($articleData['category'], $categories) ? $articleData['category'] : ($categories[0] ?? 'General');
            
            KnowledgeBase::create([
                'title' => $articleData['title'],
                'category' => $categoryName,
                'description' => $articleData['description'],
                'creator_id' => $userId,
                'created_by' => $userId,
                'created_at' => $articleData['created_at'],
            ]);
        }
    }
}