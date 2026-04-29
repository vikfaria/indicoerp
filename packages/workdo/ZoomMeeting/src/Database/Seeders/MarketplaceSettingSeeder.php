<?php

namespace Workdo\ZoomMeeting\Database\Seeders;

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
                    $screenshots[] = '/packages/workdo/ZoomMeeting/src/marketplace/' . $file->getFilename();
                }
            }
        }
        
        sort($screenshots);
        
        MarketplaceSetting::firstOrCreate(['module' => 'ZoomMeeting'], [
            'module' => 'ZoomMeeting',
            'title' => 'Zoom Meeting Module Marketplace',
            'subtitle' => 'Professional video conferencing integration with advanced meeting management and participant controls',
            'config_sections' => [
                'sections' => [
                    'hero' => [
                        'variant' => 'hero1',
                        'title' => 'Zoom Meeting Module for ERPGo SaaS',
                        'subtitle' => 'Create and manage professional video conferences with Zoom API integration, participant management, and advanced meeting controls.',
                        'primary_button_text' => 'Install Zoom Meeting Module',
                        'primary_button_link' => '#install',
                        'secondary_button_text' => 'Learn More',
                        'secondary_button_link' => '#learn',
                        'image' => ''
                    ],
                    'modules' => [
                        'variant' => 'modules1',
                        'title' => 'Zoom Meeting Module',
                        'subtitle' => 'Enhance collaboration with professional video conferencing capabilities'
                    ],
                    'dedication' => [
                        'variant' => 'dedication1',
                        'title' => 'Dedicated Zoom Meeting Features',
                        'description' => 'Our Zoom meeting module provides comprehensive video conferencing capabilities with Zoom API integration, advanced meeting controls, participant management, and seamless workflow integration.',
                        'subSections' => [
                            [
                                'title' => 'Meeting Creation & Management',
                                'description' => 'Comprehensive meeting creation with title, description, scheduling, duration settings, and participant management. Features include automatic meeting ID generation, password protection, host and participant video controls, and waiting room functionality for enhanced security.',
                                'keyPoints' => ['Meeting Scheduling', 'Participant Management', 'Security Controls', 'Duration Settings'],
                                'screenshot' => '/packages/workdo/ZoomMeeting/src/marketplace/image1.png'
                            ],
                            [
                                'title' => 'Advanced Meeting Controls',
                                'description' => 'Professional meeting controls including host video settings, participant video management, waiting room activation, and recording capabilities. Features customizable meeting options, status tracking, and comprehensive meeting configuration for optimal user experience.',
                                'keyPoints' => ['Video Controls', 'Waiting Room', 'Recording Options', 'Status Tracking'],
                                'screenshot' => '/packages/workdo/ZoomMeeting/src/marketplace/image2.png'
                            ],
                            [
                                'title' => 'Zoom API Integration & Settings',
                                'description' => 'Seamless integration with Zoom API for automated meeting creation, join URL generation, and meeting management. Features include API key configuration, webhook support, and comprehensive settings management for enterprise-grade video conferencing.',
                                'keyPoints' => ['Zoom API Integration', 'Automated URL Generation', 'Webhook Support', 'Enterprise Configuration'],
                                'screenshot' => '/packages/workdo/ZoomMeeting/src/marketplace/image3.png'
                            ]
                        ]
                    ],
                    'screenshots' => [
                        'variant' => 'screenshots1',
                        'title' => 'Zoom Meeting Module in Action',
                        'subtitle' => 'See how our video conferencing tools enhance team collaboration',
                        'images' => $screenshots
                    ],
                    'why_choose' => [
                        'variant' => 'whychoose1',
                        'title' => 'Why Choose Zoom Meeting Module?',
                        'subtitle' => 'Improve collaboration with professional video conferencing capabilities',
                        'benefits' => [
                            [
                                'title' => 'Zoom API Integration',
                                'description' => 'Direct integration with Zoom API for seamless meeting creation and management.',
                                'icon' => 'Video',
                                'color' => 'blue'
                            ],
                            [
                                'title' => 'Meeting Security',
                                'description' => 'Advanced security features including waiting rooms and password protection.',
                                'icon' => 'Shield',
                                'color' => 'green'
                            ],
                            [
                                'title' => 'Participant Management',
                                'description' => 'Comprehensive participant controls and meeting access management.',
                                'icon' => 'Users',
                                'color' => 'purple'
                            ],
                            [
                                'title' => 'Recording Support',
                                'description' => 'Built-in recording capabilities for meeting documentation and review.',
                                'icon' => 'Camera',
                                'color' => 'red'
                            ],
                            [
                                'title' => 'Meeting Scheduling',
                                'description' => 'Advanced scheduling with duration controls and time management.',
                                'icon' => 'Calendar',
                                'color' => 'yellow'
                            ],
                            [
                                'title' => 'Status Tracking',
                                'description' => 'Real-time meeting status tracking and management dashboard.',
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