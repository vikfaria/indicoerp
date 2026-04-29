<?php

namespace Workdo\Timesheet\Database\Seeders;

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
                    $screenshots[] = '/packages/workdo/Timesheet/src/marketplace/' . $file->getFilename();
                }
            }
        }
        
        sort($screenshots);
        
        MarketplaceSetting::firstOrCreate(['module' => 'Timesheet'], [
            'module' => 'Timesheet',
            'title' => 'Timesheet Module Marketplace',
            'subtitle' => 'Professional time tracking and management system for accurate project billing',
            'config_sections' => [
                'sections' => [
                    'hero' => [
                        'variant' => 'hero1',
                        'title' => 'Timesheet Module for ERPGo SaaS',
                        'subtitle' => 'Track time efficiently with multiple entry methods including manual entry, project-based tracking, and clock in/out functionality.',
                        'primary_button_text' => 'Install Timesheet Module',
                        'primary_button_link' => '#install',
                        'secondary_button_text' => 'Learn More',
                        'secondary_button_link' => '#learn',
                        'image' => ''
                    ],
                    'modules' => [
                        'variant' => 'modules1',
                        'title' => 'Timesheet Module',
                        'subtitle' => 'Enhance productivity with comprehensive time tracking capabilities'
                    ],
                    'dedication' => [
                        'variant' => 'dedication1',
                        'title' => 'Dedicated Timesheet Features',
                        'description' => 'Our timesheet module provides comprehensive time tracking capabilities with multiple entry methods, advanced filtering, and seamless integration with project management systems.',
                        'subSections' => [
                            [
                                'title' => 'Multiple Time Entry Methods',
                                'description' => 'Flexible time tracking with support for manual entry, project-based tracking, and automated clock in/out functionality. Users can choose the most suitable method based on their work style and project requirements with seamless switching between different tracking modes.',
                                'keyPoints' => ['Manual Time Entry', 'Project-Based Tracking', 'Clock In/Out System', 'Flexible Entry Methods'],
                                'screenshot' => '/packages/workdo/Timesheet/src/marketplace/image1.png'
                            ],
                            [
                                'title' => 'Advanced Filtering & Search System',
                                'description' => 'Comprehensive filtering capabilities allowing users to search and filter timesheets by type, date, user, project, and task. Features include advanced search functionality, date range filtering, and user-specific views for efficient timesheet management.',
                                'keyPoints' => ['Multi-Criteria Filtering', 'Date Range Selection', 'User-Specific Views', 'Advanced Search'],
                                'screenshot' => '/packages/workdo/Timesheet/src/marketplace/image2.png'
                            ],
                            [
                                'title' => 'List & Grid View Management',
                                'description' => 'Dual viewing modes with detailed list view for comprehensive data display and card-based grid view for visual timesheet management. Both views support sorting, filtering, and bulk operations with responsive design for all device types.',
                                'keyPoints' => ['Dual View Modes', 'Responsive Design', 'Sorting & Filtering', 'Bulk Operations'],
                                'screenshot' => '/packages/workdo/Timesheet/src/marketplace/image3.png'
                            ]
                        ]
                    ],
                    'screenshots' => [
                        'variant' => 'screenshots1',
                        'title' => 'Timesheet Module in Action',
                        'subtitle' => 'See how our time tracking tools improve productivity and accuracy',
                        'images' => $screenshots
                    ],
                    'why_choose' => [
                        'variant' => 'whychoose1',
                        'title' => 'Why Choose Timesheet Module?',
                        'subtitle' => 'Improve efficiency with comprehensive time tracking management',
                        'benefits' => [
                            [
                                'title' => 'Multiple Entry Types',
                                'description' => 'Support for manual, project-based, and clock in/out time tracking methods.',
                                'icon' => 'Clock',
                                'color' => 'blue'
                            ],
                            [
                                'title' => 'Project Integration',
                                'description' => 'Seamless integration with Taskly project management for accurate billing.',
                                'icon' => 'GitBranch',
                                'color' => 'green'
                            ],
                            [
                                'title' => 'User Management',
                                'description' => 'Track time for multiple users with role-based access controls.',
                                'icon' => 'Users',
                                'color' => 'purple'
                            ],
                            [
                                'title' => 'Advanced Filtering',
                                'description' => 'Filter timesheets by type, date, user, project, and task for easy management.',
                                'icon' => 'Filter',
                                'color' => 'red'
                            ],
                            [
                                'title' => 'Dual View Modes',
                                'description' => 'Switch between detailed list view and visual grid view for better organization.',
                                'icon' => 'Layout',
                                'color' => 'yellow'
                            ],
                            [
                                'title' => 'Time Accuracy',
                                'description' => 'Precise time tracking with hours and minutes for accurate project billing.',
                                'icon' => 'Timer',
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