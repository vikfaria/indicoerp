<?php

namespace Workdo\Webhook\Database\Seeders;

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
                    $screenshots[] = '/packages/workdo/Webhook/src/marketplace/' . $file->getFilename();
                }
            }
        }
        
        sort($screenshots);
        
        MarketplaceSetting::firstOrCreate(['module' => 'Webhook'], [
            'module' => 'Webhook',
            'title' => 'Webhook Module Marketplace',
            'subtitle' => 'Comprehensive webhook tools for your applications',
            'config_sections' => [
                'sections' => [
                    'hero' => [
                        'variant' => 'hero1',
                        'title' => 'Webhook Module for ERPGo SaaS',
                        'subtitle' => 'Streamline your webhook workflow with comprehensive tools and automated management.',
                        'primary_button_text' => 'Install Webhook Module',
                        'primary_button_link' => '#install',
                        'secondary_button_text' => 'Learn More',
                        'secondary_button_link' => '#learn',
                        'image' => ''
                    ],
                    'modules' => [
                        'variant' => 'modules1',
                        'title' => 'Webhook Module',
                        'subtitle' => 'Enhance your workflow with powerful webhook tools'
                    ],
                    'dedication' => [
                        'variant' => 'dedication1',
                        'title' => 'Dedicated Webhook Features',
                        'description' => 'Our webhook module provides comprehensive capabilities for modern workflows.',
                        'subSections' => [
                            [
                                'title' => 'Real-time Event Notifications',
                                'description' => 'Automatically send HTTP requests to external systems when events occur across 30+ modules in your WorkDo system. This powerful integration ensures your external applications stay synchronized with real-time data updates.',
                                'keyPoints' => ['Event-driven automation', 'Multi-module support', 'Real-time synchronization', 'External system integration'],
                                'screenshot' => '/packages/workdo/Webhook/src/marketplace/image1.png'
                            ],
                            [
                                'title' => 'Advanced Webhook Management',
                                'description' => 'Configure and manage webhooks with flexible settings including GET/POST methods, custom URLs, and active/inactive status controls. Permission-based access ensures secure webhook administration across different user types.',
                                'keyPoints' => ['Flexible HTTP methods', 'Custom URL configuration', 'Status management', 'Permission-based access'],
                                'screenshot' => '/packages/workdo/Webhook/src/marketplace/image2.png'
                            ],
                            [
                                'title' => 'Smart Data Extraction',
                                'description' => 'Utilize 100+ specialized data extractors that format event data for each module type with structured JSON payloads. Each webhook delivers contextual information with timestamps and module-specific data formatting.',
                                'keyPoints' => ['100+ data extractors', 'Structured JSON payloads', 'Module-specific formatting', 'Contextual data delivery'],
                                'screenshot' => '/packages/workdo/Webhook/src/marketplace/image3.png'
                            ]
                        ]
                    ],
                    'screenshots' => [
                        'variant' => 'screenshots1',
                        'title' => 'Webhook Module in Action',
                        'subtitle' => 'See how our webhook tools improve your workflow',
                        'images' => $screenshots
                    ],
                    'why_choose' => [
                        'variant' => 'whychoose1',
                        'title' => 'Why Choose Webhook Module?',
                        'subtitle' => 'Improve efficiency with comprehensive webhook management',
                        'benefits' => [
                            [
                                'title' => 'Automated Process',
                                'description' => 'Automate your webhook workflow to save time and reduce errors.',
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