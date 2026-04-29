<?php

namespace Workdo\Hrm\Database\Seeders;

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
                    $screenshots[] = '/packages/workdo/Hrm/src/marketplace/' . $file->getFilename();
                }
            }
        }
        
        sort($screenshots);
        
        MarketplaceSetting::firstOrCreate(['module' => 'Hrm'], [
            'module' => 'Hrm',
            'title' => 'Hrm Module Marketplace',
            'subtitle' => 'Comprehensive hrm tools for your applications',
            'config_sections' => [
                'sections' => [
                    'hero' => [
                        'variant' => 'hero1',
                        'title' => 'Hrm Module for ERPGo SaaS',
                        'subtitle' => 'Streamline your hrm workflow with comprehensive tools and automated management.',
                        'primary_button_text' => 'Install Hrm Module',
                        'primary_button_link' => '#install',
                        'secondary_button_text' => 'Learn More',
                        'secondary_button_link' => '#learn',
                        'image' => ''
                    ],
                    'modules' => [
                        'variant' => 'modules1',
                        'title' => 'Hrm Module',
                        'subtitle' => 'Enhance your workflow with powerful hrm tools'
                    ],
                    'dedication' => [
                        'variant' => 'dedication1',
                        'title' => 'Dedicated Hrm Features',
                        'description' => 'Our hrm module provides comprehensive capabilities for modern workflows.',
                        'subSections' => [
                            [
                                'title' => 'Feature 1',
                                'description' => 'Description of first key feature for hrm management.',
                                'keyPoints' => ['Point 1', 'Point 2', 'Point 3', 'Point 4'],
                                'screenshot' => '/packages/workdo/Hrm/src/marketplace/image1.png'
                            ],
                            [
                                'title' => 'Feature 2',
                                'description' => 'Description of second key feature for hrm organization.',
                                'keyPoints' => ['Point 1', 'Point 2', 'Point 3', 'Point 4'],
                                'screenshot' => '/packages/workdo/Hrm/src/marketplace/image2.png'
                            ],
                            [
                                'title' => 'Feature 3',
                                'description' => 'Description of third key feature for hrm tracking.',
                                'keyPoints' => ['Point 1', 'Point 2', 'Point 3', 'Point 4'],
                                'screenshot' => '/packages/workdo/Hrm/src/marketplace/image3.png'
                            ]
                        ]
                    ],
                    'screenshots' => [
                        'variant' => 'screenshots1',
                        'title' => 'Hrm Module in Action',
                        'subtitle' => 'See how our hrm tools improve your workflow',
                        'images' => $screenshots
                    ],
                    'why_choose' => [
                        'variant' => 'whychoose1',
                        'title' => 'Why Choose Hrm Module?',
                        'subtitle' => 'Improve efficiency with comprehensive hrm management',
                        'benefits' => [
                            [
                                'title' => 'Automated Process',
                                'description' => 'Automate your hrm workflow to save time and reduce errors.',
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