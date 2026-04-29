<?php

namespace Workdo\Performance\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\LandingPage\Models\MarketplaceSetting;
use Illuminate\Support\Facades\File;

class MarketplaceSettingSeeder extends Seeder
{
    public function run(): void
     {
        // Get all available screenshots from marketplace directory
        $marketplaceDir = __DIR__ . '/../../marketplace';
        $screenshots    = [];

        if (File::exists($marketplaceDir)) {
            $files = File::files($marketplaceDir);
            foreach ($files as $file) {
                if (in_array($file->getExtension(), ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                    $screenshots[] = '/packages/workdo/Performance/src/marketplace/' . $file->getFilename();
                }
            }
        }

        sort($screenshots);

        MarketplaceSetting::firstOrCreate(['module' => 'Performance'], [
            'module'          => 'Performance',
            'title'           => 'Performance Management System',
            'subtitle'        => 'Complete employee performance tracking and goal management solution',
            'config_sections' => [
                'sections'           => [
                    'hero'        => [
                        'variant'               => 'hero1',
                        'title'                 => 'Performance Management System for ERPGo SaaS',
                        'subtitle'              => 'Transform your organization with comprehensive employee performance tracking, goal setting, and review management.',
                        'primary_button_text'   => 'Install Performance Management',
                        'primary_button_link'   => '#install',
                        'secondary_button_text' => 'Learn More',
                        'secondary_button_link' => '#learn',
                        'image'                 => ''
                    ],
                    'modules'     => [
                        'variant'  => 'modules1',
                        'title'    => 'Performance Management System',
                        'subtitle' => 'Streamline employee performance with integrated goal setting and review tools'
                    ],
                    'dedication'  => [
                        'variant'     => 'dedication1',
                        'title'       => 'Comprehensive Performance Management Features',
                        'description' => 'Our performance management system delivers end-to-end employee performance solutions for modern organizations.',
                        'subSections' => [
                            [
                                'title'       => 'Goal Setting & Performance Tracking',
                                'description' => 'Comprehensive employee goal management with performance indicators and progress tracking. Set SMART goals, monitor achievements, and align individual objectives with organizational targets. Track performance metrics and provide real-time feedback to drive employee development and success.',
                                'keyPoints'   => ['SMART goal setting framework', 'Performance indicator tracking', 'Progress monitoring dashboard', 'Achievement milestone tracking'],
                                'screenshot'  => '/packages/workdo/Performance/src/marketplace/image1.png'
                            ],
                            [
                                'title'       => 'Review Cycles & Employee Evaluations',
                                'description' => 'Structured performance review system with customizable review cycles and comprehensive employee evaluations. Streamline the review process with automated scheduling, standardized evaluation forms, and performance rating systems. Enable 360-degree feedback and continuous performance improvement.',
                                'keyPoints'   => ['Automated review scheduling', 'Customizable evaluation forms', '360-degree feedback system', 'Performance rating management'],
                                'screenshot'  => '/packages/workdo/Performance/src/marketplace/image2.png'
                            ],
                            [
                                'title'       => 'Performance Analytics & Reporting',
                                'description' => 'Advanced performance analytics with comprehensive reporting and insights. Monitor team performance trends, identify top performers, and track goal completion rates. Generate detailed performance reports for informed decision-making and strategic planning.',
                                'keyPoints'   => ['Performance trend analysis', 'Goal completion tracking', 'Team performance insights', 'Comprehensive reporting dashboard'],
                                'screenshot'  => '/packages/workdo/Performance/src/marketplace/image3.png'
                            ]
                        ]
                    ],
                    'screenshots' => [
                        'variant'  => 'screenshots1',
                        'title'    => 'Performance Management System in Action',
                        'subtitle' => 'Experience seamless performance tracking with our comprehensive management platform',
                        'images'   => $screenshots
                    ],
                    'why_choose'  => [
                        'variant'  => 'whychoose1',
                        'title'    => 'Why Choose Our Performance Management System?',
                        'subtitle' => 'Transform employee performance with integrated goal setting and review excellence',
                        'benefits' => [
                            [
                                'title'       => 'Goal-Driven Performance',
                                'description' => 'Enhance employee productivity with structured goal setting, progress tracking, and achievement recognition.',
                                'icon'        => 'Target',
                                'color'       => 'blue'
                            ],
                            [
                                'title'       => 'Comprehensive Reviews',
                                'description' => 'Structured performance evaluations with customizable review cycles and standardized assessment criteria.',
                                'icon'        => 'FileText',
                                'color'       => 'green'
                            ],
                            [
                                'title'       => 'Team Performance Insights',
                                'description' => 'Monitor team performance trends and identify development opportunities for optimal organizational growth.',
                                'icon'        => 'Users',
                                'color'       => 'purple'
                            ],
                            [
                                'title'       => 'Integrated Performance System',
                                'description' => 'Unified platform connecting goals, reviews, indicators, and performance analytics in one solution.',
                                'icon'        => 'GitBranch',
                                'color'       => 'red'
                            ],
                            [
                                'title'       => 'Performance Excellence',
                                'description' => 'Maintain high performance standards with systematic tracking and continuous improvement processes.',
                                'icon'        => 'CheckCircle',
                                'color'       => 'yellow'
                            ],
                            [
                                'title'       => 'Performance Analytics',
                                'description' => 'Monitor performance metrics, goal completion rates, and team productivity with detailed reporting.',
                                'icon'        => 'Activity',
                                'color'       => 'indigo'
                            ]
                        ]
                    ]
                ],
                'section_visibility' => [
                    'header'      => true,
                    'hero'        => true,
                    'modules'     => true,
                    'dedication'  => true,
                    'screenshots' => true,
                    'why_choose'  => true,
                    'cta'         => true,
                    'footer'      => true
                ],
                'section_order'      => ['header', 'hero', 'modules', 'dedication', 'screenshots', 'why_choose', 'cta', 'footer']
            ]
        ]);
    }
}