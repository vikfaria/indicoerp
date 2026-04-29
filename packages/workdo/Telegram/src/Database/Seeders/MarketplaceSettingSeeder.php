<?php

namespace Workdo\Telegram\Database\Seeders;

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
                    $screenshots[] = '/packages/workdo/Telegram/src/marketplace/' . $file->getFilename();
                }
            }
        }
        
        sort($screenshots);
        
        MarketplaceSetting::firstOrCreate(['module' => 'Telegram'], [
            'module' => 'Telegram',
            'title' => 'Telegram - Secure Messaging Integration Platform',
            'subtitle' => 'Advanced Telegram bot integration with automated messaging and secure communication channels',
            'config_sections' => [
                'sections' => [
                    'hero' => [
                        'variant' => 'hero1',
                        'title' => 'Telegram - Revolutionize Your Business Communication',
                        'subtitle' => 'Transform your organizational communication with powerful Telegram bot integration featuring automated messaging, secure channels, and real-time notifications. Connect your business processes with Telegram\'s robust messaging platform to enable instant communication, automated alerts, and seamless team collaboration across all departments and projects.',
                        'primary_button_text' => 'Install Telegram Module',
                        'primary_button_link' => '#install',
                        'secondary_button_text' => 'Learn More',
                        'secondary_button_link' => '#learn',
                        'image' => ''
                    ],
                    'modules' => [
                        'variant' => 'modules1',
                        'title' => 'Telegram Module',
                        'subtitle' => 'Enhance your communication workflow with powerful Telegram bot integration and messaging automation'
                    ],
                    'dedication' => [
                        'variant' => 'dedication1',
                        'title' => 'Dedicated Telegram Features',
                        'description' => 'Our Telegram module provides comprehensive messaging and communication capabilities designed for modern business automation and secure team collaboration.',
                        'subSections' => [
                            [
                                'title' => 'Telegram Bot Configuration & Management',
                                'description' => 'Set up and manage powerful Telegram bots with secure token authentication and automated configuration processes. Create custom bots for different business functions with comprehensive settings management, webhook configuration, and secure API integration for reliable messaging automation across your organization.',
                                'keyPoints' => ['Secure bot token management and authentication system', 'Automated bot configuration with webhook integration', 'Custom bot creation for different business functions', 'Comprehensive API integration and security protocols'],
                                'screenshot' => '/packages/workdo/Telegram/src/marketplace/image1.png'
                            ],
                            [
                                'title' => 'Automated Messaging & Notifications',
                                'description' => 'Deploy intelligent automated messaging systems with real-time notifications, scheduled messages, and event-triggered communications. Configure smart notification routing to specific channels and users while maintaining message formatting, media attachments, and delivery confirmation for critical business communications.',
                                'keyPoints' => ['Real-time automated notification system with smart routing', 'Scheduled messaging and event-triggered communications', 'Rich message formatting with media attachment support', 'Delivery confirmation and message status tracking'],
                                'screenshot' => '/packages/workdo/Telegram/src/marketplace/image2.png'
                            ],
                            [
                                'title' => 'Channel Management & Team Communication',
                                'description' => 'Organize team communication through sophisticated channel management with group creation, member administration, and role-based access controls. Enable secure group messaging, broadcast channels, and private communications while maintaining comprehensive message history and administrative oversight for enhanced team collaboration.',
                                'keyPoints' => ['Advanced channel and group management with role-based access', 'Secure group messaging and broadcast channel capabilities', 'Member administration and permission management system', 'Comprehensive message history and administrative oversight'],
                                'screenshot' => '/packages/workdo/Telegram/src/marketplace/image3.png'
                            ]
                        ]
                    ],
                    'screenshots' => [
                        'variant' => 'screenshots1',
                        'title' => 'Telegram Module in Action',
                        'subtitle' => 'See how our comprehensive Telegram integration transforms your business communication workflow',
                        'images' => $screenshots
                    ],
                    'why_choose' => [
                        'variant' => 'whychoose1',
                        'title' => 'Why Choose Telegram Module?',
                        'subtitle' => 'Improve efficiency with comprehensive Telegram messaging integration and automation',
                        'benefits' => [
                            [
                                'title' => 'Automated Bot Management',
                                'description' => 'Automate Telegram bot operations with intelligent messaging and notification systems.',
                                'icon' => 'Play',
                                'color' => 'blue'
                            ],
                            [
                                'title' => 'Message Analytics',
                                'description' => 'Comprehensive messaging reports with delivery tracking and engagement metrics.',
                                'icon' => 'FileText',
                                'color' => 'green'
                            ],
                            [
                                'title' => 'Secure Team Communication',
                                'description' => 'Enhanced team collaboration with encrypted messaging and secure channels.',
                                'icon' => 'Users',
                                'color' => 'purple'
                            ],
                            [
                                'title' => 'Seamless Bot Integration',
                                'description' => 'Easy integration with existing systems through Telegram Bot API.',
                                'icon' => 'GitBranch',
                                'color' => 'red'
                            ],
                            [
                                'title' => 'Message Quality Control',
                                'description' => 'Maintain communication standards with message formatting and delivery confirmation.',
                                'icon' => 'CheckCircle',
                                'color' => 'yellow'
                            ],
                            [
                                'title' => 'Real-time Communication Tracking',
                                'description' => 'Track message delivery, engagement, and communication performance in real-time.',
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