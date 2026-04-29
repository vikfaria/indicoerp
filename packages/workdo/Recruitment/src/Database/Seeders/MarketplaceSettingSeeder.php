<?php

namespace Workdo\Recruitment\Database\Seeders;

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
                    $screenshots[] = '/packages/workdo/Recruitment/src/marketplace/' . $file->getFilename();
                }
            }
        }
        
        sort($screenshots);
        
        MarketplaceSetting::firstOrCreate(['module' => 'Recruitment'], [
            'module' => 'Recruitment',
            'title' => 'Recruitment Module Marketplace',
            'subtitle' => 'Complete talent acquisition and hiring management solution for modern organizations',
            'config_sections' => [
                'sections' => [
                    'hero' => [
                        'variant' => 'hero1',
                        'title' => 'Recruitment Module for ERPGo SaaS',
                        'subtitle' => 'Transform your hiring process with advanced job posting, candidate tracking, and interview management capabilities for efficient talent acquisition and enhanced recruitment outcomes.',
                        'primary_button_text' => 'Install Recruitment Module',
                        'primary_button_link' => '#install',
                        'secondary_button_text' => 'Learn More',
                        'secondary_button_link' => '#learn',
                        'image' => ''
                    ],
                    'modules' => [
                        'variant' => 'modules1',
                        'title' => 'Recruitment Module',
                        'subtitle' => 'Enhance your hiring process with powerful candidate management and interview tools'
                    ],
                    'dedication' => [
                        'variant' => 'dedication1',
                        'title' => 'Dedicated Recruitment Features',
                        'description' => 'Our recruitment module provides comprehensive capabilities for modern talent acquisition and hiring processes with integrated candidate management systems.',
                        'subSections' => [
                            [
                                'title' => 'Advanced Job Management & Posting',
                                'description' => 'Create and manage job postings with detailed requirements, skill assessments, and automated candidate screening processes for enhanced recruitment efficiency. Publish jobs across multiple platforms and track application performance with comprehensive analytics and reporting tools to optimize your hiring strategy.',
                                'keyPoints' => ['Multi-platform job posting system', 'Automated candidate screening workflows', 'Skill assessment integration tools', 'Application performance analytics dashboard'],
                                'screenshot' => '/packages/workdo/Recruitment/src/marketplace/image1.png'
                            ],
                            [
                                'title' => 'Comprehensive Candidate Tracking System',
                                'description' => 'Track candidates through every stage of the hiring pipeline with customizable workflows and automated status updates for streamlined recruitment processes. Manage candidate profiles, resumes, interview feedback, and communication history in a centralized dashboard with advanced search and filtering capabilities.',
                                'keyPoints' => ['Pipeline stage management system', 'Candidate profile tracking dashboard', 'Interview feedback management system', 'Communication history timeline logs'],
                                'screenshot' => '/packages/workdo/Recruitment/src/marketplace/image2.png'
                            ],
                            [
                                'title' => 'Interview & Assessment Management',
                                'description' => 'Schedule and conduct interviews with integrated calendar management, assessment tools, and collaborative evaluation systems for comprehensive candidate assessment. Generate detailed interview reports and maintain comprehensive candidate evaluation records for informed hiring decisions and improved recruitment outcomes.',
                                'keyPoints' => ['Interview scheduling calendar system', 'Assessment tool integration platform', 'Collaborative evaluation management system', 'Detailed interview reporting analytics'],
                                'screenshot' => '/packages/workdo/Recruitment/src/marketplace/image3.png'
                            ]
                        ]
                    ],
                    'screenshots' => [
                        'variant' => 'screenshots1',
                        'title' => 'Recruitment Module in Action',
                        'subtitle' => 'See how our hiring tools streamline your talent acquisition process',
                        'images' => $screenshots
                    ],
                    'why_choose' => [
                        'variant' => 'whychoose1',
                        'title' => 'Why Choose Recruitment Module?',
                        'subtitle' => 'Improve efficiency with comprehensive talent acquisition and hiring management',
                        'benefits' => [
                            [
                                'title' => 'Automated Hiring Pipeline',
                                'description' => 'Automate candidate screening, interview scheduling, and status updates to accelerate your hiring process.',
                                'icon' => 'Play',
                                'color' => 'blue'
                            ],
                            [
                                'title' => 'Detailed Hiring Analytics',
                                'description' => 'Get comprehensive reports on recruitment metrics, candidate sources, and hiring performance data.',
                                'icon' => 'FileText',
                                'color' => 'green'
                            ],
                            [
                                'title' => 'HR Team Collaboration',
                                'description' => 'Enable seamless collaboration between recruiters, hiring managers, and interview panels.',
                                'icon' => 'Users',
                                'color' => 'purple'
                            ],
                            [
                                'title' => 'HR System Integration',
                                'description' => 'Integrate effortlessly with existing HR systems, job boards, and assessment platforms.',
                                'icon' => 'GitBranch',
                                'color' => 'red'
                            ],
                            [
                                'title' => 'Quality Hiring Process',
                                'description' => 'Maintain consistent hiring standards with structured evaluation criteria and assessment tools.',
                                'icon' => 'CheckCircle',
                                'color' => 'yellow'
                            ],
                            [
                                'title' => 'Recruitment Performance Monitoring',
                                'description' => 'Monitor recruitment KPIs, time-to-hire metrics, and candidate satisfaction scores.',
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