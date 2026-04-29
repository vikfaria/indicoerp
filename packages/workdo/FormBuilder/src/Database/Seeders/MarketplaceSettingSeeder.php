<?php

namespace Workdo\FormBuilder\Database\Seeders;

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
                    $screenshots[] = '/packages/workdo/FormBuilder/src/marketplace/' . $file->getFilename();
                }
            }
        }
        
        sort($screenshots);
        
        MarketplaceSetting::firstOrCreate(['module' => 'FormBuilder'], [
            'module' => 'FormBuilder',
            'title' => 'FormBuilder Module Marketplace',
            'subtitle' => 'Comprehensive formbuilder tools for your applications',
            'config_sections' => [
                'sections' => [
                    'hero' => [
                        'variant' => 'hero1',
                        'title' => 'FormBuilder Module for ERPGo SaaS',
                        'subtitle' => 'Streamline your formbuilder workflow with comprehensive tools and automated management.',
                        'primary_button_text' => 'Install FormBuilder Module',
                        'primary_button_link' => '#install',
                        'secondary_button_text' => 'Learn More',
                        'secondary_button_link' => '#learn',
                        'image' => ''
                    ],
                    'modules' => [
                        'variant' => 'modules1',
                        'title' => 'FormBuilder Module',
                        'subtitle' => 'Enhance your workflow with powerful formbuilder tools'
                    ],
                    'dedication' => [
                        'variant' => 'dedication1',
                        'title' => 'Dedicated FormBuilder Features',
                        'description' => 'Our formbuilder module provides comprehensive capabilities for modern workflows.',
                        'subSections' => [
                            [
                                'title' => 'Feature 1',
                                'description' => 'Description of first key feature for formbuilder management.',
                                'keyPoints' => ['Point 1', 'Point 2', 'Point 3', 'Point 4'],
                                'screenshot' => '/packages/workdo/FormBuilder/src/marketplace/image1.png'
                            ],
                            [
                                'title' => 'Feature 2',
                                'description' => 'Description of second key feature for formbuilder organization.',
                                'keyPoints' => ['Point 1', 'Point 2', 'Point 3', 'Point 4'],
                                'screenshot' => '/packages/workdo/FormBuilder/src/marketplace/image2.png'
                            ],
                            [
                                'title' => 'Feature 3',
                                'description' => 'Description of third key feature for formbuilder tracking.',
                                'keyPoints' => ['Point 1', 'Point 2', 'Point 3', 'Point 4'],
                                'screenshot' => '/packages/workdo/FormBuilder/src/marketplace/image3.png'
                            ]
                        ]
                    ],
                    'screenshots' => [
                        'variant' => 'screenshots1',
                        'title' => 'FormBuilder Module in Action',
                        'subtitle' => 'See how our formbuilder tools improve your workflow',
                        'images' => $screenshots
                    ],
                    'why_choose' => [
                        'variant' => 'whychoose1',
                        'title' => 'Why Choose FormBuilder Module?',
                        'subtitle' => 'Improve efficiency with comprehensive formbuilder management',
                        'benefits' => [
                            [
                                'title' => 'Automated Process',
                                'description' => 'Automate your formbuilder workflow to save time and reduce errors.',
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