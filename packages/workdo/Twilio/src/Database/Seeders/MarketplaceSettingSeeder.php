<?php

namespace Workdo\Twilio\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\LandingPage\Models\MarketplaceSetting;
use Illuminate\Support\Facades\File;

class MarketplaceSettingSeeder extends Seeder
{
    public function run()
    {
        // Get all available screenshots from marketplace directory
        $marketplaceDir = __DIR__ . '/../../marketplace';
        $screenshots    = [];

        if (File::exists($marketplaceDir)) {
            $files = File::files($marketplaceDir);
            foreach ($files as $file) {
                if (in_array($file->getExtension(), ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                    $screenshots[] = '/packages/workdo/Twilio/src/marketplace/' . $file->getFilename();
                }
            }
        }

        sort($screenshots);

        MarketplaceSetting::firstOrCreate(['module' => 'Twilio'], [
            'module'          => 'Twilio',
            'title'           => 'Twilio - Complete Communication API Platform',
            'subtitle'        => 'Enterprise Twilio API integration with SMS, voice calls, and multi-channel communication',
            'config_sections' => [
                'sections'           => [
                    'hero'        => [
                        'variant'               => 'hero1',
                        'title'                 => 'Twilio - Revolutionize Multi-Channel Communication',
                        'subtitle'              => 'Empower your business with comprehensive Twilio API integration featuring SMS messaging, voice calls, video conferencing, and automated communication workflows. Connect your applications with Twilio\'s powerful communication platform to deliver personalized customer experiences, automated notifications, and seamless multi-channel engagement across voice, text, and video communications.',
                        'primary_button_text'   => 'Install Twilio Module',
                        'primary_button_link'   => '#install',
                        'secondary_button_text' => 'Learn More',
                        'secondary_button_link' => '#learn',
                        'image'                 => ''
                    ],
                    'modules'     => [
                        'variant'  => 'modules1',
                        'title'    => 'Twilio Module',
                        'subtitle' => 'Enhance your communication workflow with powerful Twilio API integration and multi-channel messaging'
                    ],
                    'dedication'  => [
                        'variant'     => 'dedication1',
                        'title'       => 'Dedicated Twilio Features',
                        'description' => 'Our Twilio module provides comprehensive communication capabilities designed for enterprise multi-channel messaging and automated customer engagement workflows.',
                        'subSections' => [
                            [
                                'title'       => 'Twilio API Configuration & Authentication',
                                'description' => 'Set up and configure enterprise-grade Twilio API integration with secure credential management, phone number provisioning, and comprehensive service configuration. Establish verified communication channels with Twilio Account SID, Auth Token management, and automated phone number verification for reliable SMS, voice, and video communication services.',
                                'keyPoints'   => ['Secure Twilio API credential management and authentication', 'Automated phone number provisioning and verification system', 'Comprehensive service configuration for SMS, voice, and video', 'Enterprise-grade security protocols and compliance management'],
                                'screenshot'  => '/packages/workdo/Twilio/src/marketplace/image1.png'
                            ],
                            [
                                'title'       => 'Multi-Channel Messaging & Voice Services',
                                'description' => 'Deploy intelligent multi-channel communication with SMS messaging, voice calls, video conferencing, and automated notification systems. Create personalized customer journeys with automated responses, scheduled campaigns, and event-triggered communications while maintaining delivery tracking and engagement analytics across all communication channels.',
                                'keyPoints'   => ['Multi-channel SMS, voice, and video communication capabilities', 'Automated messaging campaigns with personalized customer journeys', 'Real-time delivery tracking and engagement analytics', 'Event-triggered notifications and scheduled communication workflows'],
                                'screenshot'  => '/packages/workdo/Twilio/src/marketplace/image2.png'
                            ],
                            [
                                'title'       => 'Advanced Communication Analytics & Management',
                                'description' => 'Monitor and manage comprehensive communication performance with detailed analytics covering message delivery rates, call quality metrics, and customer engagement insights. Generate comprehensive reports with cost analysis, usage statistics, and communication effectiveness metrics for optimized customer outreach and support strategies.',
                                'keyPoints'   => ['Comprehensive communication analytics and performance metrics', 'Cost analysis and usage statistics for budget optimization', 'Customer engagement insights and communication effectiveness tracking', 'Advanced reporting dashboard with real-time monitoring capabilities'],
                                'screenshot'  => '/packages/workdo/Twilio/src/marketplace/image3.png'
                            ]
                        ]
                    ],
                    'screenshots' => [
                        'variant'  => 'screenshots1',
                        'title'    => 'Twilio Module in Action',
                        'subtitle' => 'See how our comprehensive Twilio API integration transforms your multi-channel communication strategy',
                        'images'   => $screenshots
                    ],
                    'why_choose'  => [
                        'variant'  => 'whychoose1',
                        'title'    => 'Why Choose Twilio Module?',
                        'subtitle' => 'Improve efficiency with comprehensive Twilio API integration and multi-channel communication',
                        'benefits' => [
                            [
                                'title'       => 'Enterprise API Integration',
                                'description' => 'Official Twilio API integration with enterprise-grade security and reliability.',
                                'icon'        => 'Play',
                                'color'       => 'blue'
                            ],
                            [
                                'title'       => 'Communication Analytics',
                                'description' => 'Comprehensive analytics with delivery rates, cost analysis, and engagement metrics.',
                                'icon'        => 'FileText',
                                'color'       => 'green'
                            ],
                            [
                                'title'       => 'Multi-Channel Support',
                                'description' => 'Unified communication platform supporting SMS, voice, video, and messaging.',
                                'icon'        => 'Users',
                                'color'       => 'purple'
                            ],
                            [
                                'title'       => 'Seamless System Integration',
                                'description' => 'Easy integration with existing CRM, support, and business management systems.',
                                'icon'        => 'GitBranch',
                                'color'       => 'red'
                            ],
                            [
                                'title'       => 'Communication Quality Assurance',
                                'description' => 'Maintain communication standards with delivery confirmation and quality monitoring.',
                                'icon'        => 'CheckCircle',
                                'color'       => 'yellow'
                            ],
                            [
                                'title'       => 'Real-time Performance Monitoring',
                                'description' => 'Track communication performance, costs, and customer engagement in real-time.',
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
