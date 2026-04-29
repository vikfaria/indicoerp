<?php

namespace Workdo\Slack\Database\Seeders;

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
                    $screenshots[] = '/packages/workdo/Slack/src/marketplace/' . $file->getFilename();
                }
            }
        }
        
        // Sort screenshots to ensure consistent order
        sort($screenshots);
        MarketplaceSetting::firstOrCreate(['module' => 'Slack'], [
            'module' => 'Slack',
            'title' => 'Slack - Complete Team Communication Integration',
            'subtitle' => 'Seamless Slack integration with automated notifications and real-time team collaboration',
            'config_sections' => [
                'sections' => [
                    'hero' => [
                        'variant' => 'hero1',
                        'title' => 'Slack - Transform Your Team Communication Experience',
                        'subtitle' => 'Revolutionize your workplace collaboration with comprehensive Slack integration featuring automated notifications, real-time messaging, and seamless workflow synchronization. Connect your entire organization through intelligent communication channels that keep everyone informed, engaged, and productive across all business processes.',
                        'primary_button_text' => 'Install Slack',
                        'primary_button_link' => '#install',
                        'secondary_button_text' => 'Learn More',
                        'secondary_button_link' => '#learn',
                        'image' => ''
                    ],
                    'modules' => [
                        'variant' => 'modules1',
                        'card_variant' => 'card1',
                        'title' => 'Slack Communication Module',
                        'subtitle' => 'Transform your team collaboration with powerful Slack integration and automated communication features'
                    ],
                    'dedication' => [
                        'variant' => 'dedication1',
                        'title' => 'Dedicated Slack Features',
                        'description' => 'Our Slack integration provides comprehensive communication and collaboration tools designed specifically for modern business workflows and team productivity.',
                        'subSections' => [
                            [
                                'title' => 'Automated Notifications & Alerts',
                                'description' => 'Receive instant, intelligent notifications for all system activities directly in your Slack channels with customizable filtering and smart routing. Configure automated alerts for critical events, project updates, and team activities to ensure everyone stays informed without overwhelming communication channels.',
                                'keyPoints' => ['Real-time automated notification system with smart filtering', 'Customizable alert channels and routing preferences', 'Intelligent notification prioritization and scheduling', 'Mobile-ready instant messaging and push notifications'],
                                'screenshot' => '/packages/workdo/Slack/src/marketplace/image1.png'
                            ],
                            [
                                'title' => 'Advanced Channel Management & Organization',
                                'description' => 'Organize team communication through sophisticated channel management with project-specific channels, department groups, and automated channel creation. Maintain structured communication flows with role-based access controls and intelligent message threading for enhanced collaboration efficiency.',
                                'keyPoints' => ['Automated project and department channel creation', 'Role-based access controls and permission management', 'Intelligent message threading and conversation organization', 'Advanced channel archiving and content management'],
                                'screenshot' => '/packages/workdo/Slack/src/marketplace/image2.png'
                            ],
                            [
                                'title' => 'Seamless File Sharing & Collaboration',
                                'description' => 'Enable effortless file sharing and document collaboration between your system and Slack with automatic synchronization and version control. Share documents, images, and project files instantly while maintaining security protocols and access permissions across all communication channels.',
                                'keyPoints' => ['Automatic file synchronization with version control', 'Secure document sharing with access permission management', 'Real-time file preview and collaborative editing features', 'Comprehensive file history and backup management'],
                                'screenshot' => '/packages/workdo/Slack/src/marketplace/image3.png'
                            ]
                        ]
                    ],
                    'screenshots' => [
                        'variant' => 'screenshots1',
                        'title' => 'Slack in Action',
                        'subtitle' => 'See how our comprehensive Slack integration transforms your team communication workflow',
                        'images' => $screenshots
                    ],
                    'why_choose' => [
                        'variant' => 'whychoose1',
                        'title' => 'Why Choose Slack Integration?',
                        'subtitle' => 'Enhance your team productivity with comprehensive Slack communication integration',
                        'benefits' => [
                            [
                                'title' => 'Instant Integration Setup',
                                'description' => 'Connect your system with Slack instantly using automated setup and configuration.',
                                'icon' => 'Zap',
                                'color' => 'blue'
                            ],
                            [
                                'title' => 'Real-time Synchronization',
                                'description' => 'All system activities are instantly synchronized with Slack channels and notifications.',
                                'icon' => 'RefreshCw',
                                'color' => 'green'
                            ],
                            [
                                'title' => 'Enhanced Team Collaboration',
                                'description' => 'Boost team productivity with integrated messaging, file sharing, and communication tools.',
                                'icon' => 'Users',
                                'color' => 'purple'
                            ],
                            [
                                'title' => 'Enterprise Security',
                                'description' => 'Enterprise-grade security with encrypted communications and access controls.',
                                'icon' => 'Shield',
                                'color' => 'red'
                            ],
                            [
                                'title' => 'Automated Workflows',
                                'description' => 'Streamline communication with automated notifications and intelligent routing.',
                                'icon' => 'Headphones',
                                'color' => 'yellow'
                            ],
                            [
                                'title' => 'Comprehensive Integration',
                                'description' => 'Complete Slack integration with advanced features and continuous updates.',
                                'icon' => 'Award',
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