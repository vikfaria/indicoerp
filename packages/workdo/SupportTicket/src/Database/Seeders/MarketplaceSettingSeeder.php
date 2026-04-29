<?php

namespace Workdo\SupportTicket\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\LandingPage\Models\MarketplaceSetting;
use Illuminate\Support\Facades\File;

class MarketplaceSettingSeeder extends Seeder
{
    public function run()
    {
        // Get all available screenshots from marketplace directory
        $marketplaceDir = __DIR__ . '/../../marketplace';
        $screenshots = [];
        
        if (File::exists($marketplaceDir)) {
            $files = File::files($marketplaceDir);
            foreach ($files as $file) {
                if (in_array($file->getExtension(), ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                    $screenshots[] = '/packages/workdo/SupportTicket/src/marketplace/' . $file->getFilename();
                }
            }
        }
        
        sort($screenshots);
        
        MarketplaceSetting::firstOrCreate(['module' => 'SupportTicket'], [
            'module' => 'SupportTicket',
            'title' => 'SupportTicket Module Marketplace',
            'subtitle' => 'Comprehensive supportticket tools for your applications',
            'config_sections' => [
                'sections' => [
                    'hero' => [
                        'variant' => 'hero1',
                        'title' => 'SupportTicket Module for ERPGo SaaS',
                        'subtitle' => 'Streamline your supportticket workflow with comprehensive tools and automated management.',
                        'primary_button_text' => 'Install SupportTicket Module',
                        'primary_button_link' => '#install',
                        'secondary_button_text' => 'Learn More',
                        'secondary_button_link' => '#learn',
                        'image' => ''
                    ],
                    'modules' => [
                        'variant' => 'modules1',
                        'title' => 'SupportTicket Module',
                        'subtitle' => 'Enhance your workflow with powerful supportticket tools'
                    ],
                    'dedication' => [
                        'variant' => 'dedication1',
                        'title' => 'Dedicated SupportTicket Features',
                        'description' => 'Our supportticket module provides comprehensive capabilities for modern workflows.',
                        'subSections' => [
                            [
                                'title' => 'Comprehensive Ticket Management System',
                                'description' => 'Create, track, and manage customer support tickets with advanced categorization and priority handling for efficient issue resolution. Streamline customer communication with automated email notifications, conversation tracking, and multi-role access for staff, clients, and vendors.',
                                'keyPoints' => ['Advanced ticket creation and tracking', 'Automated email notifications', 'Multi-role access management', 'Priority and category handling'],
                                'screenshot' => '/packages/workdo/SupportTicket/src/marketplace/image1.png'
                            ],
                            [
                                'title' => 'Knowledge Base & FAQ Management',
                                'description' => 'Build comprehensive knowledge base with categorized articles and FAQ system to reduce support workload and empower customer self-service. Import existing content, organize information effectively, and provide customers with instant access to solutions and documentation.',
                                'keyPoints' => ['Categorized knowledge base articles', 'FAQ management system', 'Bulk content import functionality', 'Customer self-service portal'],
                                'screenshot' => '/packages/workdo/SupportTicket/src/marketplace/image2.png'
                            ],
                            [
                                'title' => 'Customizable Support Portal & Branding',
                                'description' => 'Deploy professional customer-facing support portal with customizable branding, custom pages, and flexible field configuration. Create branded experience with custom pages for privacy policy and terms, while maintaining consistent company identity throughout the support process.',
                                'keyPoints' => ['Customizable brand settings', 'Professional support portal', 'Custom page creation', 'Flexible field configuration'],
                                'screenshot' => '/packages/workdo/SupportTicket/src/marketplace/image3.png'
                            ]
                        ]
                    ],
                    'screenshots' => [
                        'variant' => 'screenshots1',
                        'title' => 'SupportTicket Module in Action',
                        'subtitle' => 'See how our supportticket tools improve your workflow',
                        'images' => $screenshots
                    ],
                    'why_choose' => [
                        'variant' => 'whychoose1',
                        'title' => 'Why Choose SupportTicket Module?',
                        'subtitle' => 'Improve efficiency with comprehensive supportticket management',
                        'benefits' => [
                            [
                                'title' => 'Automated Process',
                                'description' => 'Automate your supportticket workflow to save time and reduce errors.',
                                'icon' => 'Play',
                                'color' => 'blue'
                            ],
                            [
                                'title' => 'Comprehensive Reports',
                                'description' => 'Get detailed reports with metrics and performance data.',
                                'icon' => 'FileText',
                                'color' => 'green'
                            ],
                            [
                                'title' => 'Team Collaboration',
                                'description' => 'Share results and collaborate effectively with your team.',
                                'icon' => 'Users',
                                'color' => 'purple'
                            ],
                            [
                                'title' => 'Easy Integration',
                                'description' => 'Seamlessly integrate with your existing workflow.',
                                'icon' => 'GitBranch',
                                'color' => 'red'
                            ],
                            [
                                'title' => 'Quality Management',
                                'description' => 'Maintain high quality with comprehensive management tools.',
                                'icon' => 'CheckCircle',
                                'color' => 'yellow'
                            ],
                            [
                                'title' => 'Performance Tracking',
                                'description' => 'Track performance and identify improvements early.',
                                'icon' => 'Activity',
                                'color' => 'indigo'
                            ]
                        ]
                    ]
                ],
                'section_visibility' => [
                    'header' => true,
                    'hero' => true,
                    'modules' => true,
                    'dedication' => true,
                    'screenshots' => true,
                    'why_choose' => true,
                    'cta' => true,
                    'footer' => true
                ],
                'section_order' => ['header', 'hero', 'modules', 'dedication', 'screenshots', 'why_choose', 'cta', 'footer']
            ]
        ]);
    }
}