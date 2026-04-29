<?php

namespace Workdo\Hrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\AnnouncementCategory;
use Carbon\Carbon;

class DemoAnnouncementCategorySeeder extends Seeder
{
    public function run($userId): void
    {
        if (AnnouncementCategory::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        if (!empty($userId)) {
            $categories = [
                [
                    'announcement_category' => 'Executive Communications',
                    'created_at' => Carbon::now()->subDays(180)->addHours(9)->addMinutes(0),
                    'updated_at' => Carbon::now()->subDays(180)->addHours(9)->addMinutes(0),
                ],
                [
                    'announcement_category' => 'Human Resources Updates',
                    'created_at' => Carbon::now()->subDays(178)->addHours(10)->addMinutes(30),
                    'updated_at' => Carbon::now()->subDays(178)->addHours(10)->addMinutes(30),
                ],
                [
                    'announcement_category' => 'Policy & Procedure Changes',
                    'created_at' => Carbon::now()->subDays(175)->addHours(11)->addMinutes(15),
                    'updated_at' => Carbon::now()->subDays(175)->addHours(11)->addMinutes(15),
                ],
                [
                    'announcement_category' => 'Company Events & Celebrations',
                    'created_at' => Carbon::now()->subDays(172)->addHours(14)->addMinutes(45),
                    'updated_at' => Carbon::now()->subDays(172)->addHours(14)->addMinutes(45),
                ],
                [
                    'announcement_category' => 'Training & Development Programs',
                    'created_at' => Carbon::now()->subDays(170)->addHours(13)->addMinutes(20),
                    'updated_at' => Carbon::now()->subDays(170)->addHours(13)->addMinutes(20),
                ],
                [
                    'announcement_category' => 'Benefits & Compensation Updates',
                    'created_at' => Carbon::now()->subDays(168)->addHours(15)->addMinutes(10),
                    'updated_at' => Carbon::now()->subDays(168)->addHours(15)->addMinutes(10),
                ],
                [
                    'announcement_category' => 'Safety & Security Alerts',
                    'created_at' => Carbon::now()->subDays(165)->addHours(12)->addMinutes(0),
                    'updated_at' => Carbon::now()->subDays(165)->addHours(12)->addMinutes(0),
                ],
                [
                    'announcement_category' => 'Technology & IT Updates',
                    'created_at' => Carbon::now()->subDays(162)->addHours(16)->addMinutes(30),
                    'updated_at' => Carbon::now()->subDays(162)->addHours(16)->addMinutes(30),
                ],
                [
                    'announcement_category' => 'Finance & Budget Notices',
                    'created_at' => Carbon::now()->subDays(160)->addHours(10)->addMinutes(45),
                    'updated_at' => Carbon::now()->subDays(160)->addHours(10)->addMinutes(45),
                ],
                [
                    'announcement_category' => 'Operations & Process Updates',
                    'created_at' => Carbon::now()->subDays(158)->addHours(14)->addMinutes(15),
                    'updated_at' => Carbon::now()->subDays(158)->addHours(14)->addMinutes(15),
                ],
                [
                    'announcement_category' => 'Employee Recognition & Awards',
                    'created_at' => Carbon::now()->subDays(155)->addHours(8)->addMinutes(30),
                    'updated_at' => Carbon::now()->subDays(155)->addHours(8)->addMinutes(30),
                ],
                [
                    'announcement_category' => 'Health & Wellness Programs',
                    'created_at' => Carbon::now()->subDays(152)->addHours(9)->addMinutes(0),
                    'updated_at' => Carbon::now()->subDays(152)->addHours(9)->addMinutes(0),
                ],
                [
                    'announcement_category' => 'Organizational Changes',
                    'created_at' => Carbon::now()->subDays(150)->addHours(11)->addMinutes(20),
                    'updated_at' => Carbon::now()->subDays(150)->addHours(11)->addMinutes(20),
                ],
                [
                    'announcement_category' => 'Project & Initiative Updates',
                    'created_at' => Carbon::now()->subDays(148)->addHours(13)->addMinutes(45),
                    'updated_at' => Carbon::now()->subDays(148)->addHours(13)->addMinutes(45),
                ],
                [
                    'announcement_category' => 'Customer & Client Relations',
                    'created_at' => Carbon::now()->subDays(145)->addHours(15)->addMinutes(25),
                    'updated_at' => Carbon::now()->subDays(145)->addHours(15)->addMinutes(25),
                ],
                [
                    'announcement_category' => 'Compliance & Regulatory Updates',
                    'created_at' => Carbon::now()->subDays(142)->addHours(12)->addMinutes(10),
                    'updated_at' => Carbon::now()->subDays(142)->addHours(12)->addMinutes(10),
                ],
                [
                    'announcement_category' => 'Facility & Infrastructure News',
                    'created_at' => Carbon::now()->subDays(140)->addHours(14)->addMinutes(35),
                    'updated_at' => Carbon::now()->subDays(140)->addHours(14)->addMinutes(35),
                ],
                [
                    'announcement_category' => 'Quality & Performance Metrics',
                    'created_at' => Carbon::now()->subDays(138)->addHours(16)->addMinutes(0),
                    'updated_at' => Carbon::now()->subDays(138)->addHours(16)->addMinutes(0),
                ],
                [
                    'announcement_category' => 'Environmental & Sustainability',
                    'created_at' => Carbon::now()->subDays(135)->addHours(10)->addMinutes(50),
                    'updated_at' => Carbon::now()->subDays(135)->addHours(10)->addMinutes(50),
                ],
                [
                    'announcement_category' => 'Partnership & Collaboration News',
                    'created_at' => Carbon::now()->subDays(132)->addHours(13)->addMinutes(15),
                    'updated_at' => Carbon::now()->subDays(132)->addHours(13)->addMinutes(15),
                ],
                [
                    'announcement_category' => 'Innovation & Research Updates',
                    'created_at' => Carbon::now()->subDays(130)->addHours(11)->addMinutes(40),
                    'updated_at' => Carbon::now()->subDays(130)->addHours(11)->addMinutes(40),
                ],
                [
                    'announcement_category' => 'Market & Industry Insights',
                    'created_at' => Carbon::now()->subDays(128)->addHours(15)->addMinutes(5),
                    'updated_at' => Carbon::now()->subDays(128)->addHours(15)->addMinutes(5),
                ],
                [
                    'announcement_category' => 'Emergency & Crisis Communications',
                    'created_at' => Carbon::now()->subDays(125)->addHours(9)->addMinutes(25),
                    'updated_at' => Carbon::now()->subDays(125)->addHours(9)->addMinutes(25),
                ],
                [
                    'announcement_category' => 'Diversity & Inclusion Initiatives',
                    'created_at' => Carbon::now()->subDays(122)->addHours(12)->addMinutes(55),
                    'updated_at' => Carbon::now()->subDays(122)->addHours(12)->addMinutes(55),
                ],
                [
                    'announcement_category' => 'Remote Work & Flexibility Updates',
                    'created_at' => Carbon::now()->subDays(120)->addHours(14)->addMinutes(20),
                    'updated_at' => Carbon::now()->subDays(120)->addHours(14)->addMinutes(20),
                ],
                [
                    'announcement_category' => 'Performance Review & Feedback',
                    'created_at' => Carbon::now()->subDays(118)->addHours(16)->addMinutes(45),
                    'updated_at' => Carbon::now()->subDays(118)->addHours(16)->addMinutes(45),
                ],
                [
                    'announcement_category' => 'Career Development Opportunities',
                    'created_at' => Carbon::now()->subDays(115)->addHours(8)->addMinutes(10),
                    'updated_at' => Carbon::now()->subDays(115)->addHours(8)->addMinutes(10),
                ],
                [
                    'announcement_category' => 'Social & Community Engagement',
                    'created_at' => Carbon::now()->subDays(112)->addHours(10)->addMinutes(35),
                    'updated_at' => Carbon::now()->subDays(112)->addHours(10)->addMinutes(35),
                ],
                [
                    'announcement_category' => 'Vendor & Supplier Communications',
                    'created_at' => Carbon::now()->subDays(110)->addHours(13)->addMinutes(0),
                    'updated_at' => Carbon::now()->subDays(110)->addHours(13)->addMinutes(0),
                ],
                [
                    'announcement_category' => 'General Company Information',
                    'created_at' => Carbon::now()->subDays(108)->addHours(15)->addMinutes(30),
                    'updated_at' => Carbon::now()->subDays(108)->addHours(15)->addMinutes(30),
                ],
            ];

            foreach ($categories as $category) {
                AnnouncementCategory::updateOrCreate(
                    [
                        'announcement_category' => $category['announcement_category'],
                        'created_by' => $userId
                    ],
                    [
                        'creator_id' => $userId,
                        'created_at' => $category['created_at'],
                        'updated_at' => $category['updated_at'],
                    ]
                );
            }
        }
    }
}