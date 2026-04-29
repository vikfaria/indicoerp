<?php

namespace Workdo\Training\Database\Seeders;

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
                    $screenshots[] = '/packages/workdo/Training/src/marketplace/' . $file->getFilename();
                }
            }
        }
        
        sort($screenshots);
        
        MarketplaceSetting::firstOrCreate(['module' => 'Training'], [
            'module' => 'Training',
            'title' => 'Training Module Marketplace',
            'subtitle' => 'Comprehensive training tools for your applications',
            'config_sections' => [
                'sections' => [
                    'hero' => [
                        'variant' => 'hero1',
                        'title' => 'Training Module for ERPGo SaaS',
                        'subtitle' => 'Streamline your training workflow with comprehensive tools and automated management.',
                        'primary_button_text' => 'Install Training Module',
                        'primary_button_link' => '#install',
                        'secondary_button_text' => 'Learn More',
                        'secondary_button_link' => '#learn',
                        'image' => ''
                    ],
                    'modules' => [
                        'variant' => 'modules1',
                        'title' => 'Training Module',
                        'subtitle' => 'Enhance your workflow with powerful training tools'
                    ],
                    'dedication' => [
                        'variant' => 'dedication1',
                        'title' => 'Dedicated Training Features',
                        'description' => 'Our training module provides comprehensive capabilities for modern workflows.',
                        'subSections' => [
                            [
                                'title' => 'Training Management System',
                                'description' => 'Comprehensive training management platform that streamlines the entire training lifecycle from planning to completion. Advanced scheduling and resource allocation ensures optimal training delivery and participant engagement.',
                                'keyPoints' => ['Create and manage training programs', 'Schedule training sessions efficiently', 'Track participant progress', 'Generate detailed training reports'],
                                'screenshot' => '/packages/workdo/Training/src/marketplace/image1.png'
                            ],
                            [
                                'title' => 'Trainer & Participant Portal',
                                'description' => 'Dedicated portals for trainers and participants with role-based access and personalized dashboards. Interactive features enable seamless communication and real-time feedback collection throughout the training process.',
                                'keyPoints' => ['Trainer profile management', 'Participant enrollment system', 'Interactive feedback collection', 'Real-time communication tools'],
                                'screenshot' => '/packages/workdo/Training/src/marketplace/image2.png'
                            ],
                            [
                                'title' => 'Analytics & Performance Tracking',
                                'description' => 'Advanced analytics dashboard providing comprehensive insights into training effectiveness and participant performance. Data-driven reporting helps optimize training programs and measure ROI on training investments.',
                                'keyPoints' => ['Performance analytics dashboard', 'Training effectiveness metrics', 'Completion rate tracking', 'ROI measurement tools'],
                                'screenshot' => '/packages/workdo/Training/src/marketplace/image3.png'
                            ]
                        ]
                    ],
                    'screenshots' => [
                        'variant' => 'screenshots1',
                        'title' => 'Training Module in Action',
                        'subtitle' => 'See how our training tools improve your workflow',
                        'images' => $screenshots
                    ],
                    'why_choose' => [
                        'variant' => 'whychoose1',
                        'title' => 'Why Choose Training Module?',
                        'subtitle' => 'Improve efficiency with comprehensive training management',
                        'benefits' => [
                            [
                                'title' => 'Automated Process',
                                'description' => 'Automate your training workflow to save time and reduce errors.',
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