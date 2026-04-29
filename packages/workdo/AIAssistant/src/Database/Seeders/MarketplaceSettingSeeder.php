<?php

namespace Workdo\AIAssistant\Database\Seeders;

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
                    $screenshots[] = '/packages/workdo/AIAssistant/src/marketplace/' . $file->getFilename();
                }
            }
        }

        sort($screenshots);

        MarketplaceSetting::firstOrCreate(['module' => 'AIAssistant'], [
            'module'          => 'AIAssistant',
            'title'           => 'AI Assistant Content Generator',
            'subtitle'        => 'Intelligent content generation integrated across all platform modules',
            'config_sections' => [
                'sections'           => [
                    'hero'        => [
                        'variant'               => 'hero1',
                        'title'                 => 'AI Assistant for ERPGo SaaS',
                        'subtitle'              => 'Transform content creation across your entire platform with intelligent AI-powered generation integrated seamlessly into every form and module.',
                        'primary_button_text'   => 'Install AI Assistant Module',
                        'primary_button_link'   => '#install',
                        'secondary_button_text' => 'Learn More',
                        'secondary_button_link' => '#learn',
                        'image'                 => ''
                    ],
                    'modules'     => [
                        'variant'  => 'modules1',
                        'title'    => 'AI Assistant Module',
                        'subtitle' => 'Revolutionize content creation with intelligent AI integration across all platform modules'
                    ],
                    'dedication'  => [
                        'variant'     => 'dedication1',
                        'title'       => 'Dedicated AI Assistant Features',
                        'description' => 'Our AI Assistant module provides intelligent content generation capabilities integrated across 16+ modules with multi-provider AI support and advanced customization options.',
                        'subSections' => [
                            [
                                'title'       => 'Universal AI Integration',
                                'description' => 'Seamlessly integrated AI content generation across 16+ modules including HRM, Sales, CRM, Support, and more with contextual intelligence for relevant content creation.',
                                'keyPoints'   => ['Integration across 16+ platform modules', 'Contextual AI understanding for each module', 'One-click content generation in any form', 'Seamless workflow integration without disruption'],
                                'screenshot'  => '/packages/workdo/AIAssistant/src/marketplace/image1.png'
                            ],
                            [
                                'title'       => 'Multi-Provider AI Support',
                                'description' => 'Advanced AI capabilities with support for multiple providers including OpenAI, Claude, Gemini, and Anthropic with dynamic model selection and secure API management.',
                                'keyPoints'   => ['Multiple AI provider support (OpenAI, Claude, Gemini)', 'Dynamic model selection based on provider', 'Secure API key management with encryption', 'Company-side configuration and control'],
                                'screenshot'  => '/packages/workdo/AIAssistant/src/marketplace/image2.png'
                            ],
                            [
                                'title'       => 'Advanced Generation Controls',
                                'description' => 'Comprehensive customization options including 15+ language support, creativity levels, batch generation, and character length control for precise content creation.',
                                'keyPoints'   => ['15+ language support with localization', 'AI creativity control (Low, Medium, High)', 'Batch generation with multiple result options', 'Character length control and real-time preview'],
                                'screenshot'  => '/packages/workdo/AIAssistant/src/marketplace/image3.png'
                            ]
                        ]
                    ],
                    'screenshots' => [
                        'variant'  => 'screenshots1',
                        'title'    => 'AI Assistant Module in Action',
                        'subtitle' => 'See how intelligent content generation transforms productivity across all platform modules',
                        'images'   => $screenshots
                    ],
                    'why_choose'  => [
                        'variant'  => 'whychoose1',
                        'title'    => 'Why Choose AI Assistant Module?',
                        'subtitle' => 'Transform content creation with intelligent AI-powered generation across your entire platform',
                        'benefits' => [
                            [
                                'title'       => 'Automated Process',
                                'description' => 'Automate your aiassistant workflow to save time and reduce errors.',
                                'icon'        => 'Play',
                                'color'       => 'blue'
                            ],
                            [
                                'title'       => 'Comprehensive Reports',
                                'description' => 'Get detailed reports with metrics and performance data.',
                                'icon'        => 'FileText',
                                'color'       => 'green'
                            ],
                            [
                                'title'       => 'Team Collaboration',
                                'description' => 'Share results and collaborate effectively with your team.',
                                'icon'        => 'Users',
                                'color'       => 'purple'
                            ],
                            [
                                'title'       => 'Easy Integration',
                                'description' => 'Seamlessly integrate with your existing workflow.',
                                'icon'        => 'GitBranch',
                                'color'       => 'red'
                            ],
                            [
                                'title'       => 'Quality Management',
                                'description' => 'Maintain high quality with comprehensive management tools.',
                                'icon'        => 'CheckCircle',
                                'color'       => 'yellow'
                            ],
                            [
                                'title'       => 'Performance Tracking',
                                'description' => 'Track performance and identify improvements early.',
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
