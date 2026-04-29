<?php

namespace Workdo\SupportTicket\Database\Seeders;

use Workdo\SupportTicket\Models\TicketCategory;
use Workdo\SupportTicket\Models\KnowledgeBaseCategory;
use Workdo\SupportTicket\Models\SupportTicketSetting;
use Illuminate\Database\Seeder;

class DemoSystemSetupSeeder extends Seeder
{
    public function run($userId): void
    {

        if (SupportTicketSetting::where('created_by', $userId)->exists()) {
            return;
        }

        // Create 10 Ticket Categories with predefined colors for better UI/UX
        $ticketCategories = [
            ['name' => 'Technical Support', 'color' => '#3B82F6'],
            ['name' => 'Billing & Payment', 'color' => '#10b77f'],
            ['name' => 'Feature Request', 'color' => '#F59E0B'],
            ['name' => 'Bug Report', 'color' => '#EF4444'],
            ['name' => 'Account Management', 'color' => '#06B6D4'],
            ['name' => 'Integration Support', 'color' => '#84CC16'],
            ['name' => 'Performance Issues', 'color' => '#F97316'],
            ['name' => 'Security Concerns', 'color' => '#DC2626'],
            ['name' => 'Training & Education', 'color' => '#7C3AED'],
            ['name' => 'General Inquiry', 'color' => '#6B7280']
        ];

        foreach ($ticketCategories as $category) {
            TicketCategory::create([
                'name' => $category['name'],
                'color' => $category['color'],
                'creator_id' => $userId,
                'created_by' => $userId,
            ]);
        }

        // Create 10 Knowledge Base Categories (interconnected with ticket categories)
        $kbCategories = [
            'Getting Started Guide',
            'Billing & Subscription Management',
            'Feature Documentation',
            'Troubleshooting & Bug Fixes',
            'Account Setup & Configuration',
            'Third-Party Integrations',
            'Performance Optimization',
            'Security Best Practices',
            'User Training Resources',
            'FAQ & Common Questions'
        ];

        foreach ($kbCategories as $categoryName) {
            KnowledgeBaseCategory::create([
                'title' => $categoryName,
                'creator_id' => $userId,
                'created_by' => $userId,
            ]);
        }

        // Create Support Ticket Settings
        $settings = [
            'title_text' => 'Support Center',
            'footer_text' => '© ' . date('Y') . ' Support System. All rights reserved.',
            'privacy_policy' => json_encode([
                'enabled' => true,
                'content' => '<h2>Privacy Policy</h2><p>We are committed to protecting your privacy. This Privacy Policy explains how we collect, use, and safeguard your information when you use our support system.</p><h3>Information We Collect</h3><p>We collect information you provide directly to us, such as when you create a support ticket, contact us, or use our services.</p><h3>How We Use Your Information</h3><p>We use the information we collect to provide, maintain, and improve our services, process transactions, and communicate with you.</p><h3>Information Sharing</h3><p>We do not sell, trade, or otherwise transfer your personal information to third parties without your consent, except as described in this policy.</p>'
            ]),
            'terms_conditions' => json_encode([
                'enabled' => true,
                'content' => '<h2>Terms and Conditions</h2><p>By using our support system, you agree to these terms and conditions.</p><h3>Use of Service</h3><p>You may use our service for lawful purposes only. You agree not to use the service in any way that violates applicable laws or regulations.</p><h3>User Accounts</h3><p>You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.</p><h3>Limitation of Liability</h3><p>We shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of the service.</p>'
            ]),
        ];

        foreach ($settings as $key => $value) {
            SupportTicketSetting::create([
                'key' => $key,
                'value' => $value,
                'created_by' => $userId,
            ]);
        }
    }


}