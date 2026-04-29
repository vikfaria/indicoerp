<?php

namespace Workdo\Quotation\Database\Seeders;

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
                    $screenshots[] = '/packages/workdo/Quotation/src/marketplace/' . $file->getFilename();
                }
            }
        }
        
        sort($screenshots);
        
        MarketplaceSetting::firstOrCreate(['module' => 'Quotation'], [
            'module' => 'Quotation',
            'title' => 'Quotation Module Marketplace',
            'subtitle' => 'Comprehensive quotation tools for your applications',
            'config_sections' => [
                'sections' => [
                    'hero' => [
                        'variant' => 'hero1',
                        'title' => 'Quotation Module for ERPGo SaaS',
                        'subtitle' => 'Streamline your quotation workflow with comprehensive tools and automated management.',
                        'primary_button_text' => 'Install Quotation Module',
                        'primary_button_link' => '#install',
                        'secondary_button_text' => 'Learn More',
                        'secondary_button_link' => '#learn',
                        'image' => ''
                    ],
                    'modules' => [
                        'variant' => 'modules1',
                        'title' => 'Quotation Module',
                        'subtitle' => 'Enhance your workflow with powerful quotation tools'
                    ],
                    'dedication' => [
                        'variant' => 'dedication1',
                        'title' => 'Dedicated Quotation Features',
                        'description' => 'Our quotation module provides comprehensive capabilities for modern workflows.',
                        'subSections' => [
                            [
                                'title' => 'Professional Quotation Creation & Management',
                                'description' => 'Create professional sales quotations with detailed line items, product selection, and comprehensive pricing structures for accurate client proposals. Streamline the quotation process with automated calculations, customizable templates, and integrated product catalogs for efficient sales operations.',
                                'keyPoints' => ['Professional quotation templates', 'Integrated product selection', 'Automated pricing calculations', 'Customizable proposal formats'],
                                'screenshot' => '/packages/workdo/Quotation/src/marketplace/image1.png'
                            ],
                            [
                                'title' => 'Advanced Tax Calculations & Financial Management',
                                'description' => 'Handle complex tax calculations and financial computations with multi-level tax structures and accurate pricing breakdowns for transparent client billing. Ensure compliance with automated tax calculations, detailed financial summaries, and comprehensive cost analysis for professional quotation accuracy.',
                                'keyPoints' => ['Multi-level tax calculations', 'Automated financial summaries', 'Transparent pricing breakdowns', 'Compliance-ready tax handling'],
                                'screenshot' => '/packages/workdo/Quotation/src/marketplace/image2.png'
                            ],
                            [
                                'title' => 'Quotation Lifecycle & Conversion Management',
                                'description' => 'Manage complete quotation lifecycle from creation to conversion with status tracking, client approval workflows, and seamless document conversion capabilities. Track quotation performance with acceptance rates, follow-up management, and automated conversion to invoices or contracts for streamlined sales processes.',
                                'keyPoints' => ['Complete lifecycle tracking', 'Client approval workflows', 'Automated document conversion', 'Performance analytics dashboard'],
                                'screenshot' => '/packages/workdo/Quotation/src/marketplace/image3.png'
                            ]
                        ]
                    ],
                    'screenshots' => [
                        'variant' => 'screenshots1',
                        'title' => 'Quotation Module in Action',
                        'subtitle' => 'See how our quotation tools improve your workflow',
                        'images' => $screenshots
                    ],
                    'why_choose' => [
                        'variant' => 'whychoose1',
                        'title' => 'Why Choose Quotation Module?',
                        'subtitle' => 'Improve efficiency with comprehensive quotation management',
                        'benefits' => [
                            [
                                'title' => 'Automated Process',
                                'description' => 'Automate your quotation workflow to save time and reduce errors.',
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