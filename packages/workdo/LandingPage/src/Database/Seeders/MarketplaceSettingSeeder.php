<?php

namespace Workdo\LandingPage\Database\Seeders;

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
                    $screenshots[] = '/packages/workdo/LandingPage/src/marketplace/' . $file->getFilename();
                }
            }
        }
        
        // Sort screenshots to ensure consistent order
        sort($screenshots);
        
        MarketplaceSetting::firstOrCreate(['module' => 'LandingPage'], [
            'module' => 'LandingPage',
            'title' => 'Landing Page Builder Marketplace',
            'subtitle' => 'Create stunning landing pages with our powerful builder',
            'config_sections' => [
                'sections' => [
                    'hero' => [
                        'variant' => 'hero1',
                        'title' => 'Landing Page Builder for ERPGo SaaS',
                        'subtitle' => 'Create beautiful, responsive landing pages with our drag-and-drop builder and professional templates.',
                        'primary_button_text' => 'Install Builder',
                        'primary_button_link' => '#install',
                        'secondary_button_text' => 'View Demo',
                        'secondary_button_link' => '#demo',
                        'image' => ''
                    ],
                    'modules' => [
                        'variant' => 'modules1',
                        'card_variant' => 'card1',
                        'title' => 'Landing Page Module',
                        'subtitle' => 'Build professional landing pages with ease using our comprehensive page builder'
                    ],
                    'dedication' => [
                        'variant' => 'dedication1',
                        'title' => 'Dedicated Landing Page Features',
                        'description' => 'Our landing page builder provides all the tools you need to create high-converting pages.',
                        'subSections' => [
                            [
                                'title' => 'Drag & Drop Builder',
                                'description' => 'Create pages visually with our intuitive drag-and-drop interface.',
                                'keyPoints' => ['Visual Editor', 'Pre-built Blocks', 'Real-time Preview', 'Mobile Responsive'],
                                'screenshot' => '/packages/workdo/LandingPage/src/marketplace/image1.png'
                            ],
                            [
                                'title' => 'Professional Templates',
                                'description' => 'Choose from dozens of professionally designed templates for any industry.',
                                'keyPoints' => ['Industry Templates', 'Customizable Designs', 'Modern Layouts', 'SEO Optimized'],
                                'screenshot' => '/packages/workdo/LandingPage/src/marketplace/image2.png'
                            ],
                            [
                                'title' => 'Advanced Analytics',
                                'description' => 'Track visitor behavior and conversion rates with built-in analytics.',
                                'keyPoints' => ['Conversion Tracking', 'Visitor Analytics', 'A/B Testing', 'Performance Reports'],
                                'screenshot' => '/packages/workdo/LandingPage/src/marketplace/image3.png'
                            ]
                        ]
                    ],
                    'screenshots' => [
                        'variant' => 'screenshots1',
                        'title' => 'Landing Page Builder in Action',
                        'subtitle' => 'See how our builder helps create amazing pages',
                        'images' => $screenshots
                    ],
                    'why_choose' => [
                        'variant' => 'whychoose1',
                        'title' => 'Why Choose Landing Page Builder?',
                        'subtitle' => 'Create high-converting pages with professional tools',
                        'benefits' => [
                            [
                                'title' => 'Easy to Use',
                                'description' => 'No coding required - create professional pages with simple drag and drop.',
                                'icon' => 'MousePointer',
                                'color' => 'blue'
                            ],
                            [
                                'title' => 'Mobile Responsive',
                                'description' => 'All pages automatically adapt to any screen size and device.',
                                'icon' => 'Smartphone',
                                'color' => 'green'
                            ],
                            [
                                'title' => 'SEO Optimized',
                                'description' => 'Built-in SEO tools help your pages rank higher in search results.',
                                'icon' => 'Search',
                                'color' => 'purple'
                            ],
                            [
                                'title' => 'Fast Loading',
                                'description' => 'Optimized code ensures your pages load quickly for better user experience.',
                                'icon' => 'Zap',
                                'color' => 'red'
                            ],
                            [
                                'title' => 'Analytics Included',
                                'description' => 'Track performance and optimize your pages with detailed analytics.',
                                'icon' => 'BarChart',
                                'color' => 'yellow'
                            ],
                            [
                                'title' => 'Regular Updates',
                                'description' => 'Get new features, templates, and improvements with regular updates.',
                                'icon' => 'RefreshCw',
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