<?php

namespace Workdo\Contract\Database\Seeders;

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
                    $screenshots[] = '/packages/workdo/Contract/src/marketplace/' . $file->getFilename();
                }
            }
        }
        
        sort($screenshots);
        
        MarketplaceSetting::firstOrCreate(['module' => 'Contract'], [
            'module' => 'Contract',
            'title' => 'Contract Module Marketplace',
            'subtitle' => 'Comprehensive contract tools for your applications',
            'config_sections' => [
                'sections' => [
                    'hero' => [
                        'variant' => 'hero1',
                        'title' => 'Contract Module for ERPGo SaaS',
                        'subtitle' => 'Streamline your contract workflow with comprehensive tools and automated management.',
                        'primary_button_text' => 'Install Contract Module',
                        'primary_button_link' => '#install',
                        'secondary_button_text' => 'Learn More',
                        'secondary_button_link' => '#learn',
                        'image' => ''
                    ],
                    'modules' => [
                        'variant' => 'modules1',
                        'title' => 'Contract Module',
                        'subtitle' => 'Enhance your workflow with powerful contract tools'
                    ],
                    'dedication' => [
                        'variant' => 'dedication1',
                        'title' => 'Dedicated Contract Features',
                        'description' => 'Our contract module provides comprehensive capabilities for modern workflows.',
                        'subSections' => [
                            [
                                'title' => 'Contract Creation & Management',
                                'description' => 'Comprehensive contract creation system with advanced management capabilities for complete lifecycle tracking. Streamlined contract organization ensures efficient workflow management and easy access to all contract information.',
                                'keyPoints' => ['Create and manage contracts', 'Contract type categorization', 'Value and date tracking', 'Status management system'],
                                'screenshot' => '/packages/workdo/Contract/src/marketplace/image1.png'
                            ],
                            [
                                'title' => 'Digital Signatures & Authentication',
                                'description' => 'Advanced digital signature system with secure authentication and verification capabilities for legal compliance. Electronic signature collection ensures document integrity and provides audit trails for complete transparency.',
                                'keyPoints' => ['Digital signature collection', 'Secure authentication system', 'Signature verification process', 'Legal compliance features'],
                                'screenshot' => '/packages/workdo/Contract/src/marketplace/image2.png'
                            ],
                            [
                                'title' => 'Contract Renewals & Lifecycle',
                                'description' => 'Automated contract renewal management with comprehensive lifecycle tracking and notification systems. Advanced renewal workflows ensure timely contract updates and prevent expiration-related issues.',
                                'keyPoints' => ['Automated renewal tracking', 'Lifecycle management system', 'Renewal notification alerts', 'Contract expiration monitoring'],
                                'screenshot' => '/packages/workdo/Contract/src/marketplace/image3.png'
                            ],
                             
                        ]
                    ],
                    'screenshots' => [
                        'variant' => 'screenshots1',
                        'title' => 'Contract Module in Action',
                        'subtitle' => 'See how our contract tools improve your workflow',
                        'images' => $screenshots
                    ],
                    'why_choose' => [
                        'variant' => 'whychoose1',
                        'title' => 'Why Choose Contract Module?',
                        'subtitle' => 'Improve efficiency with comprehensive contract management',
                        'benefits' => [
                            [
                                'title' => 'Automated Process',
                                'description' => 'Automate your contract workflow to save time and reduce errors.',
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