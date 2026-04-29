<?php

namespace Workdo\GoogleCaptcha\Database\Seeders;

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
                    $screenshots[] = '/packages/workdo/GoogleCaptcha/src/marketplace/' . $file->getFilename();
                }
            }
        }
        
        sort($screenshots);
        
        MarketplaceSetting::firstOrCreate(['module' => 'GoogleCaptcha'], [
            'module' => 'GoogleCaptcha',
            'title' => 'Google reCAPTCHA Module',
            'subtitle' => 'Protect your forms with Google reCAPTCHA security',
            'config_sections' => [
                'sections' => [
                    'hero' => [
                        'variant' => 'hero1',
                        'title' => 'Google reCAPTCHA Module for ERPGo SaaS',
                        'subtitle' => 'Secure your forms and prevent spam with Google reCAPTCHA v2 and v3 integration.',
                        'primary_button_text' => 'Install reCAPTCHA Module',
                        'primary_button_link' => '#install',
                        'secondary_button_text' => 'Learn More',
                        'secondary_button_link' => '#learn',
                        'image' => ''
                    ],
                    'modules' => [
                        'variant' => 'modules1',
                        'title' => 'Google reCAPTCHA Module',
                        'subtitle' => 'Advanced form protection with Google reCAPTCHA'
                    ],
                    'dedication' => [
                        'variant' => 'dedication1',
                        'title' => 'Comprehensive reCAPTCHA Protection',
                        'description' => 'Our reCAPTCHA module provides robust security features to protect your forms from spam and abuse.',
                        'subSections' => [
                            [
                                'title' => 'reCAPTCHA v2 & v3 Support',
                                'description' => 'Comprehensive support for both reCAPTCHA v2 checkbox verification and v3 invisible protection with advanced bot detection capabilities. Flexible version selection allows you to choose between user-interactive challenges or seamless background verification based on your security requirements.',
                                'keyPoints' => ['Dual version support', 'Checkbox and invisible modes', 'Advanced bot detection', 'Flexible security options'],
                                'screenshot' => '/packages/workdo/GoogleCaptcha/src/marketplace/image1.png'
                            ],
                            [
                                'title' => 'Easy Configuration & Management',
                                'description' => 'Intuitive admin interface with simple setup process and comprehensive configuration options for seamless reCAPTCHA integration. Built-in setup guide with direct links to Google console ensures quick deployment and optimal security configuration.',
                                'keyPoints' => ['Intuitive admin interface', 'Built-in setup guide', 'Real-time configuration', 'Google console integration'],
                                'screenshot' => '/packages/workdo/GoogleCaptcha/src/marketplace/image2.png'
                            ]
                        ]
                    ],
                    'screenshots' => [
                        'variant' => 'screenshots1',
                        'title' => 'reCAPTCHA Module in Action',
                        'subtitle' => 'See how Google reCAPTCHA protects your forms',
                        'images' => $screenshots
                    ],
                    'why_choose' => [
                        'variant' => 'whychoose1',
                        'title' => 'Why Choose Google reCAPTCHA Module?',
                        'subtitle' => 'Enhance security with industry-standard bot protection',
                        'benefits' => [
                            [
                                'title' => 'Spam Protection',
                                'description' => 'Block automated spam and malicious bot attacks on your forms.',
                                'icon' => 'Shield',
                                'color' => 'blue'
                            ],
                            [
                                'title' => 'Multiple Versions',
                                'description' => 'Support for both reCAPTCHA v2 and v3 with easy switching.',
                                'icon' => 'Settings',
                                'color' => 'green'
                            ],
                            [
                                'title' => 'User Experience',
                                'description' => 'Maintain smooth user experience while ensuring security.',
                                'icon' => 'Users',
                                'color' => 'purple'
                            ],
                            [
                                'title' => 'Easy Integration',
                                'description' => 'Seamlessly integrate with existing forms and workflows.',
                                'icon' => 'GitBranch',
                                'color' => 'red'
                            ],
                            [
                                'title' => 'Google Powered',
                                'description' => 'Leverage Google\'s advanced machine learning for bot detection.',
                                'icon' => 'Zap',
                                'color' => 'yellow'
                            ],
                            [
                                'title' => 'Analytics & Monitoring',
                                'description' => 'Track verification success rates and security metrics.',
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