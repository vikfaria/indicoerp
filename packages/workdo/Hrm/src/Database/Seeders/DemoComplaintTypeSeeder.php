<?php

namespace Workdo\Hrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\ComplaintType;
use Carbon\Carbon;

class DemoComplaintTypeSeeder extends Seeder
{
    public function run($userId): void
    {
        if (ComplaintType::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        if (!empty($userId)) {
            $complaintTypes = [
                [
                    'complaint_type' => 'Workplace Harassment',
                    'created_at' => Carbon::now()->subDays(180)->addHours(9)->addMinutes(0),
                    'updated_at' => Carbon::now()->subDays(180)->addHours(9)->addMinutes(0),
                ],
                [
                    'complaint_type' => 'Discrimination & Bias',
                    'created_at' => Carbon::now()->subDays(178)->addHours(10)->addMinutes(30),
                    'updated_at' => Carbon::now()->subDays(178)->addHours(10)->addMinutes(30),
                ],
                [
                    'complaint_type' => 'Workplace Safety Violations',
                    'created_at' => Carbon::now()->subDays(175)->addHours(11)->addMinutes(15),
                    'updated_at' => Carbon::now()->subDays(175)->addHours(11)->addMinutes(15),
                ],
                [
                    'complaint_type' => 'Policy & Procedure Violations',
                    'created_at' => Carbon::now()->subDays(172)->addHours(14)->addMinutes(45),
                    'updated_at' => Carbon::now()->subDays(172)->addHours(14)->addMinutes(45),
                ],
                [
                    'complaint_type' => 'Unfair Treatment & Favoritism',
                    'created_at' => Carbon::now()->subDays(170)->addHours(13)->addMinutes(20),
                    'updated_at' => Carbon::now()->subDays(170)->addHours(13)->addMinutes(20),
                ],
                [
                    'complaint_type' => 'Workplace Environment Issues',
                    'created_at' => Carbon::now()->subDays(168)->addHours(15)->addMinutes(10),
                    'updated_at' => Carbon::now()->subDays(168)->addHours(15)->addMinutes(10),
                ],
                [
                    'complaint_type' => 'Compensation & Benefits Disputes',
                    'created_at' => Carbon::now()->subDays(165)->addHours(12)->addMinutes(0),
                    'updated_at' => Carbon::now()->subDays(165)->addHours(12)->addMinutes(0),
                ],
                [
                    'complaint_type' => 'Performance Management Issues',
                    'created_at' => Carbon::now()->subDays(162)->addHours(16)->addMinutes(30),
                    'updated_at' => Carbon::now()->subDays(162)->addHours(16)->addMinutes(30),
                ],
                [
                    'complaint_type' => 'Communication & Interpersonal Conflicts',
                    'created_at' => Carbon::now()->subDays(160)->addHours(10)->addMinutes(45),
                    'updated_at' => Carbon::now()->subDays(160)->addHours(10)->addMinutes(45),
                ],
                [
                    'complaint_type' => 'Work Schedule & Time Management',
                    'created_at' => Carbon::now()->subDays(158)->addHours(14)->addMinutes(15),
                    'updated_at' => Carbon::now()->subDays(158)->addHours(14)->addMinutes(15),
                ],
                [
                    'complaint_type' => 'Technology & System Issues',
                    'created_at' => Carbon::now()->subDays(155)->addHours(8)->addMinutes(30),
                    'updated_at' => Carbon::now()->subDays(155)->addHours(8)->addMinutes(30),
                ],
                [
                    'complaint_type' => 'Training & Development Concerns',
                    'created_at' => Carbon::now()->subDays(152)->addHours(9)->addMinutes(0),
                    'updated_at' => Carbon::now()->subDays(152)->addHours(9)->addMinutes(0),
                ],
                [
                    'complaint_type' => 'Resource & Equipment Shortages',
                    'created_at' => Carbon::now()->subDays(150)->addHours(11)->addMinutes(20),
                    'updated_at' => Carbon::now()->subDays(150)->addHours(11)->addMinutes(20),
                ],
                [
                    'complaint_type' => 'Ethical & Compliance Violations',
                    'created_at' => Carbon::now()->subDays(148)->addHours(13)->addMinutes(45),
                    'updated_at' => Carbon::now()->subDays(148)->addHours(13)->addMinutes(45),
                ],
                [
                    'complaint_type' => 'Workplace Bullying & Intimidation',
                    'created_at' => Carbon::now()->subDays(145)->addHours(15)->addMinutes(25),
                    'updated_at' => Carbon::now()->subDays(145)->addHours(15)->addMinutes(25),
                ],
                [
                    'complaint_type' => 'Privacy & Confidentiality Breaches',
                    'created_at' => Carbon::now()->subDays(142)->addHours(12)->addMinutes(10),
                    'updated_at' => Carbon::now()->subDays(142)->addHours(12)->addMinutes(10),
                ],
                [
                    'complaint_type' => 'Leave & Attendance Disputes',
                    'created_at' => Carbon::now()->subDays(140)->addHours(14)->addMinutes(35),
                    'updated_at' => Carbon::now()->subDays(140)->addHours(14)->addMinutes(35),
                ],
                [
                    'complaint_type' => 'Facility & Infrastructure Problems',
                    'created_at' => Carbon::now()->subDays(138)->addHours(16)->addMinutes(0),
                    'updated_at' => Carbon::now()->subDays(138)->addHours(16)->addMinutes(0),
                ],
                [
                    'complaint_type' => 'Health & Wellness Concerns',
                    'created_at' => Carbon::now()->subDays(135)->addHours(10)->addMinutes(50),
                    'updated_at' => Carbon::now()->subDays(135)->addHours(10)->addMinutes(50),
                ],
                [
                    'complaint_type' => 'Career Development & Promotion Issues',
                    'created_at' => Carbon::now()->subDays(132)->addHours(13)->addMinutes(15),
                    'updated_at' => Carbon::now()->subDays(132)->addHours(13)->addMinutes(15),
                ],
                [
                    'complaint_type' => 'Vendor & External Relations',
                    'created_at' => Carbon::now()->subDays(130)->addHours(11)->addMinutes(40),
                    'updated_at' => Carbon::now()->subDays(130)->addHours(11)->addMinutes(40),
                ],
                [
                    'complaint_type' => 'Quality & Process Improvement',
                    'created_at' => Carbon::now()->subDays(128)->addHours(15)->addMinutes(5),
                    'updated_at' => Carbon::now()->subDays(128)->addHours(15)->addMinutes(5),
                ],
                [
                    'complaint_type' => 'Customer Service & Relations',
                    'created_at' => Carbon::now()->subDays(125)->addHours(9)->addMinutes(25),
                    'updated_at' => Carbon::now()->subDays(125)->addHours(9)->addMinutes(25),
                ],
                [
                    'complaint_type' => 'Financial & Budget Concerns',
                    'created_at' => Carbon::now()->subDays(122)->addHours(12)->addMinutes(55),
                    'updated_at' => Carbon::now()->subDays(122)->addHours(12)->addMinutes(55),
                ],
                [
                    'complaint_type' => 'Environmental & Sustainability Issues',
                    'created_at' => Carbon::now()->subDays(120)->addHours(14)->addMinutes(20),
                    'updated_at' => Carbon::now()->subDays(120)->addHours(14)->addMinutes(20),
                ],
                [
                    'complaint_type' => 'Security & Access Control',
                    'created_at' => Carbon::now()->subDays(118)->addHours(16)->addMinutes(45),
                    'updated_at' => Carbon::now()->subDays(118)->addHours(16)->addMinutes(45),
                ],
                [
                    'complaint_type' => 'Remote Work & Flexibility Issues',
                    'created_at' => Carbon::now()->subDays(115)->addHours(8)->addMinutes(10),
                    'updated_at' => Carbon::now()->subDays(115)->addHours(8)->addMinutes(10),
                ],
                [
                    'complaint_type' => 'Diversity & Inclusion Concerns',
                    'created_at' => Carbon::now()->subDays(112)->addHours(10)->addMinutes(35),
                    'updated_at' => Carbon::now()->subDays(112)->addHours(10)->addMinutes(35),
                ],
                [
                    'complaint_type' => 'Legal & Regulatory Compliance',
                    'created_at' => Carbon::now()->subDays(110)->addHours(13)->addMinutes(0),
                    'updated_at' => Carbon::now()->subDays(110)->addHours(13)->addMinutes(0),
                ],
                [
                    'complaint_type' => 'General Administrative Issues',
                    'created_at' => Carbon::now()->subDays(108)->addHours(15)->addMinutes(30),
                    'updated_at' => Carbon::now()->subDays(108)->addHours(15)->addMinutes(30),
                ],
            ];

            foreach ($complaintTypes as $complaintType) {
                ComplaintType::updateOrCreate(
                    [
                        'complaint_type' => $complaintType['complaint_type'],
                        'created_by' => $userId
                    ],
                    [
                        'creator_id' => $userId,
                        'created_at' => $complaintType['created_at'],
                        'updated_at' => $complaintType['updated_at'],
                    ]
                );
            }
        }
    }
}