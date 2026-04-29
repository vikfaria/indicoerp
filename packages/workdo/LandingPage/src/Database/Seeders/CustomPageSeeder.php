<?php

namespace Workdo\LandingPage\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\LandingPage\Models\CustomPage;

class CustomPageSeeder extends Seeder
{
    public function run()
    {
        $pages = [
            [
                'title' => 'About Us',
                'slug' => 'about-us',
                'content' => '<div class="space-y-8">
                    <div class="text-center">
                        <h2 class="text-3xl font-bold text-gray-900 mb-4">Empowering Business Growth</h2>
                        <p class="text-lg text-gray-600 max-w-3xl mx-auto">ERPGo SaaS is dedicated to providing robust business solutions that help companies streamline their operations and achieve sustainable growth.</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                        <div>
                            <h3 class="text-2xl font-semibold text-gray-900 mb-4">Our Mission</h3>
                            <p class="text-gray-600 leading-relaxed">To democratize access to enterprise-grade business tools, enabling companies of all sizes to manage their operations with precision and ease.</p>
                        </div>
                        <div class="bg-gray-100 rounded-lg p-8">
                            <h3 class="text-2xl font-semibold text-gray-900 mb-4">Our Vision</h3>
                            <p class="text-gray-600 leading-relaxed">To be the global standard for cloud-based business management, where every operation is transparent, connected, and efficient.</p>
                        </div>
                    </div>
                    
                    <div class="rounded-lg p-8" style="background-color: color-mix(in srgb, var(--color-primary) 5%, white);">
                        <h3 class="text-2xl font-semibold text-gray-900 mb-6 text-center">Why Choose ERPGo?</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="text-center">
                                <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background-color: var(--color-primary);">
                                    <span class="text-white font-bold text-xl">1</span>
                                </div>
                                <h4 class="font-semibold text-gray-900 mb-2">Business Efficiency</h4>
                                <p class="text-gray-600 text-sm">Streamline workflows and reduce manual data entry errors.</p>
                            </div>
                            <div class="text-center">
                                <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background-color: var(--color-primary);">
                                    <span class="text-white font-bold text-xl">2</span>
                                </div>
                                <h4 class="font-semibold text-gray-900 mb-2">Enterprise Security</h4>
                                <p class="text-gray-600 text-sm">Your business data is protected with state-of-the-art encryption.</p>
                            </div>
                            <div class="text-center">
                                <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background-color: var(--color-primary);">
                                    <span class="text-white font-bold text-xl">3</span>
                                </div>
                                <h4 class="font-semibold text-gray-900 mb-2">Scalable Platform</h4>
                                <p class="text-gray-600 text-sm">From startup to enterprise, we grow with your business.</p>
                            </div>
                        </div>
                    </div>
                </div>',
                'meta_title' => 'About Us - Learn More About Our Company',
                'meta_description' => 'Discover our story, mission, and vision. Learn why we are the trusted choice for business management solutions.',
                'is_active' => true,
                'is_disabled' => false
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => '<div class="space-y-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Data Privacy & Security</h2>
                        <p class="text-gray-600 mb-4">Last updated: ' . date('F d, Y') . '</p>
                        <p class="text-gray-600 leading-relaxed">At ERPGo SaaS, we take the security of your business data seriously. This policy outlines how we collect, use, and protect your information.</p>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">Information We Collect</h3>
                        <ul class="list-disc list-inside text-gray-600 space-y-2">
                            <li>Personal information you provide when creating an account</li>
                            <li>Usage data and analytics to improve our services</li>
                            <li>Communication preferences and settings</li>
                            <li>Technical information about your device and browser</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">How We Use Your Information</h3>
                        <ul class="list-disc list-inside text-gray-600 space-y-2">
                            <li>To provide and maintain our services</li>
                            <li>To notify you about changes to our services</li>
                            <li>To provide customer support</li>
                            <li>To gather analysis or valuable information to improve our services</li>
                        </ul>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">Contact Us</h3>
                        <p class="text-gray-600">If you have any questions about this Privacy Policy, please contact us at privacy@erpgosaas.com</p>
                    </div>
                </div>',
                'meta_title' => 'Privacy Policy - How We Protect Your Data',
                'meta_description' => 'Learn about our privacy practices and how we collect, use, and protect your personal information.',
                'is_active' => true,
                'is_disabled' => true
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms-of-service',
                'content' => '<div class="space-y-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Terms of Service</h2>
                        <p class="text-gray-600 mb-4">Last updated: ' . date('F d, Y') . '</p>
                        <p class="text-gray-600 leading-relaxed">These Terms of Service govern your use of our website and services. By accessing or using our service, you agree to be bound by these terms.</p>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">Acceptance of Terms</h3>
                        <p class="text-gray-600 leading-relaxed">By accessing and using this website, you accept and agree to be bound by the terms and provision of this agreement.</p>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">Use License</h3>
                        <ul class="list-disc list-inside text-gray-600 space-y-2">
                            <li>Permission is granted to temporarily download one copy of the materials for personal, non-commercial transitory viewing only</li>
                            <li>This is the grant of a license, not a transfer of title</li>
                            <li>Under this license you may not modify or copy the materials</li>
                            <li>Use the materials for any commercial purpose or for any public display</li>
                        </ul>
                    </div>
                    
                    <div class="rounded-lg p-6" style="background-color: color-mix(in srgb, var(--color-primary) 5%, white);">
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">Contact Information</h3>
                        <p class="text-gray-600">If you have any questions about these Terms of Service, please contact us at legal@erpgosaas.com</p>
                    </div>
                </div>',
                'meta_title' => 'Terms of Service - Legal Terms and Conditions',
                'meta_description' => 'Read our terms of service to understand the legal terms and conditions for using our website and services.',
                'is_active' => true,
                'is_disabled' => true
            ],
            [
                'title' => 'FAQ',
                'slug' => 'faq',
                'content' => '<div class="space-y-8">
                    <div class="text-center">
                        <h2 class="text-3xl font-bold text-gray-900 mb-4">Frequently Asked Questions</h2>
                        <p class="text-lg text-gray-600">Find answers to the most common questions about our services.</p>
                    </div>
                    
                    <div class="space-y-6">
                        <div class="bg-white border rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">What is ERPGo SaaS?</h3>
                            <p class="text-gray-600 leading-relaxed">ERPGo SaaS is a comprehensive business management platform that helps companies streamline their operations, manage projects, and improve productivity through integrated tools and modules.</p>
                        </div>
                        
                        <div class="bg-white border rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">How do I get started?</h3>
                            <p class="text-gray-600 leading-relaxed">Getting started is easy! Simply sign up for an account, choose your plan, and follow our setup wizard to configure your workspace according to your business needs.</p>
                        </div>
                        
                        <div class="bg-white border rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">What support options are available?</h3>
                            <p class="text-gray-600 leading-relaxed">We offer 24/7 customer support through multiple channels including live chat, email, and phone support. Premium users also get access to dedicated account managers.</p>
                        </div>
                        
                        <div class="bg-white border rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Can I customize the platform?</h3>
                            <p class="text-gray-600 leading-relaxed">Yes! ERPGo SaaS offers extensive customization options including custom fields, workflows, dashboards, and integrations with third-party tools.</p>
                        </div>
                    </div>
                    
                    <div class="text-center rounded-lg p-8" style="background-color: color-mix(in srgb, var(--color-primary) 5%, white);">
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">Still have questions?</h3>
                        <p class="text-gray-600 mb-4">Our support team is here to help you with any questions or concerns.</p>
                        <button class="px-6 py-3 text-white rounded-md font-medium" style="background-color: var(--color-primary);">Contact Support</button>
                    </div>
                </div>',
                'meta_title' => 'FAQ - Frequently Asked Questions',
                'meta_description' => 'Find answers to common questions about our business management platform and services.',
                'is_active' => true,
                'is_disabled' => false
            ],
            [
                'title' => 'Help Center',
                'slug' => 'help-center',
                'content' => '<div class="space-y-8">
                    <div class="text-center">
                        <h2 class="text-3xl font-bold text-gray-900 mb-4">Help Center</h2>
                        <p class="text-lg text-gray-600">Find guides, tutorials, and resources to help you get the most out of our platform.</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="bg-white border rounded-lg p-6 hover:shadow-lg transition-shadow">
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center mb-4" style="background-color: var(--color-primary);">
                                <span class="text-white font-bold">📚</span>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Getting Started</h3>
                            <p class="text-gray-600 mb-4">Learn the basics and set up your account in minutes.</p>
                            <a href="#" class="font-medium" style="color: var(--color-primary);">View Guides →</a>
                        </div>
                        
                        <div class="bg-white border rounded-lg p-6 hover:shadow-lg transition-shadow">
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center mb-4" style="background-color: var(--color-primary);">
                                <span class="text-white font-bold">🎥</span>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Video Tutorials</h3>
                            <p class="text-gray-600 mb-4">Watch step-by-step video guides for all features.</p>
                            <a href="#" class="font-medium" style="color: var(--color-primary);">Watch Videos →</a>
                        </div>
                        
                        <div class="bg-white border rounded-lg p-6 hover:shadow-lg transition-shadow">
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center mb-4" style="background-color: var(--color-primary);">
                                <span class="text-white font-bold">💬</span>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Community Forum</h3>
                            <p class="text-gray-600 mb-4">Connect with other users and share best practices.</p>
                            <a href="#" class="font-medium" style="color: var(--color-primary);">Join Forum →</a>
                        </div>
                    </div>
                    
                    <div class="text-center rounded-lg p-8" style="background-color: color-mix(in srgb, var(--color-primary) 5%, white);">
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">Need Personal Help?</h3>
                        <p class="text-gray-600 mb-4">Our support team is available 24/7 to assist you with any questions.</p>
                        <button class="px-6 py-3 text-white rounded-md font-medium" style="background-color: var(--color-primary);">Contact Support</button>
                    </div>
                </div>',
                'meta_title' => 'Help Center - Guides and Support Resources',
                'meta_description' => 'Access our comprehensive help center with guides, tutorials, and support resources.',
                'is_active' => true,
                'is_disabled' => false
            ]
        ];

        foreach ($pages as $pageData) {
            CustomPage::updateOrCreate(
                ['slug' => $pageData['slug']],
                $pageData
            );
        }
    }
}