<?php

namespace Workdo\Hrm\Database\Seeders;

use Workdo\Hrm\Models\Announcement;
use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\AnnouncementCategory;
use Workdo\Hrm\Models\Department;
use Carbon\Carbon;
use App\Models\User;

class DemoAnnouncementSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Announcement::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        if (!empty($userId)) {
            $categories = AnnouncementCategory::where('created_by', $userId)->pluck('id')->toArray();
            $departments = Department::where('created_by', $userId)->pluck('id')->toArray();
            $users = User::whereIn('id', \Workdo\Hrm\Models\Employee::where('created_by', $userId)->pluck('user_id'))
                ->where('created_by', $userId)
                ->pluck('id')
                ->toArray();

            if (empty($categories) || empty($departments)) {
                return;
            }

            $announcements = [
                ['title' => 'Annual Company Holiday Schedule 2025', 'description' => 'Official holiday calendar for 2025 has been finalized including national holidays, company closure dates, and optional floating holidays for employee planning.', 'priority' => 'high', 'status' => 'active', 'start_date' => Carbon::now()->subDays(175)->addHours(9)->addMinutes(15), 'end_date' => Carbon::now()->addDays(190), 'created_at' => Carbon::now()->subDays(175)->addHours(9)->addMinutes(15)],
                ['title' => 'Enhanced Health Insurance Benefits Launch', 'description' => 'New comprehensive health insurance policy with expanded coverage, dental benefits, and wellness programs will be automatically enrolled for all eligible employees.', 'priority' => 'high', 'status' => 'active', 'start_date' => Carbon::now()->subDays(170)->addHours(10)->addMinutes(30), 'end_date' => Carbon::now()->addDays(195), 'created_at' => Carbon::now()->subDays(170)->addHours(10)->addMinutes(30)],
                ['title' => 'Office Infrastructure Renovation Project', 'description' => 'Third floor renovation will commence next week requiring temporary relocation of affected departments to fifth floor with minimal disruption to operations.', 'priority' => 'medium', 'status' => 'active', 'start_date' => Carbon::now()->subDays(165)->addHours(14)->addMinutes(45), 'end_date' => Carbon::now()->addDays(35), 'created_at' => Carbon::now()->subDays(165)->addHours(14)->addMinutes(45)],
                ['title' => 'Quarterly Performance Evaluation Process', 'description' => 'Performance review cycle begins next month requiring self-assessments, supervisor meetings, and goal setting sessions for all employees across departments.', 'priority' => 'medium', 'status' => 'draft', 'start_date' => Carbon::now()->subDays(160)->addHours(11)->addMinutes(20), 'end_date' => Carbon::now()->addDays(50), 'created_at' => Carbon::now()->subDays(160)->addHours(11)->addMinutes(20)],
                ['title' => 'Enhanced Security Protocol Implementation', 'description' => 'New security measures including mandatory ID card display, updated access procedures, and visitor management system will be enforced across premises.', 'priority' => 'high', 'status' => 'active', 'start_date' => Carbon::now()->subDays(155)->addHours(13)->addMinutes(10), 'end_date' => Carbon::now()->addDays(185), 'created_at' => Carbon::now()->subDays(155)->addHours(13)->addMinutes(10)],
                ['title' => 'Annual Team Building Adventure Event', 'description' => 'Join our exciting team building activities at city park featuring outdoor games, catered lunch, networking sessions, and prize competitions for departments.', 'priority' => 'low', 'status' => 'active', 'start_date' => Carbon::now()->subDays(150)->addHours(15)->addMinutes(35), 'end_date' => Carbon::now()->addDays(25), 'created_at' => Carbon::now()->subDays(150)->addHours(15)->addMinutes(35)],
                ['title' => 'Professional Development Training Program', 'description' => 'Comprehensive skill enhancement workshops covering leadership, technical skills, and career advancement opportunities available for all employee levels and departments.', 'priority' => 'medium', 'status' => 'active', 'start_date' => Carbon::now()->subDays(145)->addHours(8)->addMinutes(50), 'end_date' => Carbon::now()->addDays(60), 'created_at' => Carbon::now()->subDays(145)->addHours(8)->addMinutes(50)],
                ['title' => 'Remote Work Policy Update', 'description' => 'Updated remote work guidelines including hybrid schedules, productivity expectations, communication protocols, and equipment provision for eligible positions.', 'priority' => 'medium', 'status' => 'draft', 'start_date' => Carbon::now()->subDays(140)->addHours(12)->addMinutes(25), 'end_date' => Carbon::now()->addDays(120), 'created_at' => Carbon::now()->subDays(140)->addHours(12)->addMinutes(25)],
                ['title' => 'Employee Wellness Initiative Launch', 'description' => 'New wellness program featuring fitness memberships, mental health support, nutrition counseling, and stress management workshops for employee wellbeing enhancement.', 'priority' => 'medium', 'status' => 'active', 'start_date' => Carbon::now()->subDays(135)->addHours(16)->addMinutes(40), 'end_date' => Carbon::now()->addDays(90), 'created_at' => Carbon::now()->subDays(135)->addHours(16)->addMinutes(40)],
                ['title' => 'IT System Maintenance Schedule', 'description' => 'Planned system maintenance and upgrades will occur during weekend hours with minimal impact on daily operations and enhanced security features.', 'priority' => 'high', 'status' => 'active', 'start_date' => Carbon::now()->subDays(130)->addHours(9)->addMinutes(55), 'end_date' => Carbon::now()->addDays(15), 'created_at' => Carbon::now()->subDays(130)->addHours(9)->addMinutes(55)],
                ['title' => 'Diversity and Inclusion Workshop Series', 'description' => 'Mandatory diversity training sessions promoting inclusive workplace culture, unconscious bias awareness, and respectful communication practices for all staff members.', 'priority' => 'high', 'status' => 'active', 'start_date' => Carbon::now()->subDays(125)->addHours(11)->addMinutes(15), 'end_date' => Carbon::now()->addDays(45), 'created_at' => Carbon::now()->subDays(125)->addHours(11)->addMinutes(15)],
                ['title' => 'Environmental Sustainability Campaign', 'description' => 'Green initiative promoting eco-friendly practices including recycling programs, energy conservation, paperless operations, and sustainable commuting options for employees.', 'priority' => 'low', 'status' => 'draft', 'start_date' => Carbon::now()->subDays(120)->addHours(14)->addMinutes(30), 'end_date' => Carbon::now()->addDays(180), 'created_at' => Carbon::now()->subDays(120)->addHours(14)->addMinutes(30)],
                ['title' => 'Customer Service Excellence Training', 'description' => 'Comprehensive customer service training program focusing on communication skills, problem resolution, and customer satisfaction improvement for client-facing departments.', 'priority' => 'medium', 'status' => 'active', 'start_date' => Carbon::now()->subDays(115)->addHours(10)->addMinutes(45), 'end_date' => Carbon::now()->addDays(30), 'created_at' => Carbon::now()->subDays(115)->addHours(10)->addMinutes(45)],
                ['title' => 'Annual Budget Planning Meeting', 'description' => 'Department heads and managers are required to attend budget planning sessions for next fiscal year including resource allocation and strategic planning.', 'priority' => 'high', 'status' => 'active', 'start_date' => Carbon::now()->subDays(110)->addHours(13)->addMinutes(20), 'end_date' => Carbon::now()->addDays(20), 'created_at' => Carbon::now()->subDays(110)->addHours(13)->addMinutes(20)],
                ['title' => 'Employee Recognition Awards Ceremony', 'description' => 'Annual awards ceremony celebrating outstanding employee achievements, service milestones, and exceptional contributions to company success across all departments.', 'priority' => 'medium', 'status' => 'inactive', 'start_date' => Carbon::now()->subDays(105)->addHours(15)->addMinutes(10), 'end_date' => Carbon::now()->subDays(95), 'created_at' => Carbon::now()->subDays(105)->addHours(15)->addMinutes(10)],
                ['title' => 'Cybersecurity Awareness Training', 'description' => 'Mandatory cybersecurity training covering phishing prevention, password security, data protection protocols, and safe internet practices for all employees.', 'priority' => 'high', 'status' => 'active', 'start_date' => Carbon::now()->subDays(100)->addHours(8)->addMinutes(35), 'end_date' => Carbon::now()->addDays(40), 'created_at' => Carbon::now()->subDays(100)->addHours(8)->addMinutes(35)],
                ['title' => 'New Employee Orientation Program', 'description' => 'Comprehensive onboarding program for new hires including company culture introduction, policy briefings, department tours, and mentor assignment processes.', 'priority' => 'medium', 'status' => 'active', 'start_date' => Carbon::now()->subDays(95)->addHours(12)->addMinutes(50), 'end_date' => Carbon::now()->addDays(365), 'created_at' => Carbon::now()->subDays(95)->addHours(12)->addMinutes(50)],
                ['title' => 'Flexible Working Hours Policy', 'description' => 'New flexible schedule options allowing core hours flexibility, compressed workweeks, and alternative arrangements to improve work-life balance for employees.', 'priority' => 'medium', 'status' => 'draft', 'start_date' => Carbon::now()->subDays(90)->addHours(16)->addMinutes(25), 'end_date' => Carbon::now()->addDays(90), 'created_at' => Carbon::now()->subDays(90)->addHours(16)->addMinutes(25)],
                ['title' => 'Emergency Evacuation Drill Exercise', 'description' => 'Mandatory emergency preparedness drill testing evacuation procedures, assembly points, and safety protocols to ensure employee safety and compliance.', 'priority' => 'high', 'status' => 'active', 'start_date' => Carbon::now()->subDays(85)->addHours(9)->addMinutes(40), 'end_date' => Carbon::now()->addDays(10), 'created_at' => Carbon::now()->subDays(85)->addHours(9)->addMinutes(40)],
                ['title' => 'Innovation Challenge Competition', 'description' => 'Company-wide innovation contest encouraging creative solutions, process improvements, and technological advancements with prizes for winning ideas and implementations.', 'priority' => 'low', 'status' => 'active', 'start_date' => Carbon::now()->subDays(80)->addHours(11)->addMinutes(55), 'end_date' => Carbon::now()->addDays(75), 'created_at' => Carbon::now()->subDays(80)->addHours(11)->addMinutes(55)],
                ['title' => 'Mental Health Awareness Week', 'description' => 'Dedicated week promoting mental health awareness featuring workshops, counseling sessions, stress management techniques, and wellness resources for employee support.', 'priority' => 'medium', 'status' => 'inactive', 'start_date' => Carbon::now()->subDays(75)->addHours(14)->addMinutes(15), 'end_date' => Carbon::now()->subDays(68), 'created_at' => Carbon::now()->subDays(75)->addHours(14)->addMinutes(15)],
                ['title' => 'Quality Management System Certification', 'description' => 'Company pursuing ISO quality certification requiring employee training, process documentation, and compliance procedures across all operational departments.', 'priority' => 'high', 'status' => 'active', 'start_date' => Carbon::now()->subDays(70)->addHours(10)->addMinutes(30), 'end_date' => Carbon::now()->addDays(120), 'created_at' => Carbon::now()->subDays(70)->addHours(10)->addMinutes(30)],
                ['title' => 'Employee Feedback Survey Campaign', 'description' => 'Anonymous employee satisfaction survey collecting feedback on workplace culture, management effectiveness, benefits, and suggestions for organizational improvements.', 'priority' => 'medium', 'status' => 'active', 'start_date' => Carbon::now()->subDays(65)->addHours(13)->addMinutes(45), 'end_date' => Carbon::now()->addDays(30), 'created_at' => Carbon::now()->subDays(65)->addHours(13)->addMinutes(45)],
                ['title' => 'Technology Upgrade Implementation', 'description' => 'Major technology infrastructure upgrade including new software systems, hardware replacements, and digital transformation initiatives across all departments.', 'priority' => 'high', 'status' => 'draft', 'start_date' => Carbon::now()->subDays(60)->addHours(15)->addMinutes(20), 'end_date' => Carbon::now()->addDays(150), 'created_at' => Carbon::now()->subDays(60)->addHours(15)->addMinutes(20)],
                ['title' => 'Cross-Department Collaboration Project', 'description' => 'Initiative promoting interdepartmental cooperation through joint projects, shared goals, and collaborative problem-solving to enhance organizational efficiency.', 'priority' => 'medium', 'status' => 'active', 'start_date' => Carbon::now()->subDays(55)->addHours(8)->addMinutes(10), 'end_date' => Carbon::now()->addDays(85), 'created_at' => Carbon::now()->subDays(55)->addHours(8)->addMinutes(10)],
                ['title' => 'Workplace Safety Inspection Schedule', 'description' => 'Regular safety inspections ensuring compliance with occupational health standards, equipment maintenance, and hazard identification across all work areas.', 'priority' => 'high', 'status' => 'active', 'start_date' => Carbon::now()->subDays(50)->addHours(12)->addMinutes(35), 'end_date' => Carbon::now()->addDays(365), 'created_at' => Carbon::now()->subDays(50)->addHours(12)->addMinutes(35)],
                ['title' => 'Leadership Development Program', 'description' => 'Executive leadership training program for managers and supervisors focusing on team management, strategic thinking, and organizational leadership skills.', 'priority' => 'medium', 'status' => 'inactive', 'start_date' => Carbon::now()->subDays(45)->addHours(16)->addMinutes(50), 'end_date' => Carbon::now()->subDays(15), 'created_at' => Carbon::now()->subDays(45)->addHours(16)->addMinutes(50)],
                ['title' => 'Company Social Responsibility Initiative', 'description' => 'Community outreach program encouraging employee volunteerism, charitable contributions, and social impact projects supporting local community development.', 'priority' => 'low', 'status' => 'active', 'start_date' => Carbon::now()->subDays(40)->addHours(9)->addMinutes(25), 'end_date' => Carbon::now()->addDays(200), 'created_at' => Carbon::now()->subDays(40)->addHours(9)->addMinutes(25)],
                ['title' => 'Digital Communication Platform Launch', 'description' => 'New internal communication system featuring instant messaging, video conferencing, file sharing, and collaboration tools for enhanced workplace connectivity.', 'priority' => 'medium', 'status' => 'draft', 'start_date' => Carbon::now()->subDays(35)->addHours(11)->addMinutes(40), 'end_date' => Carbon::now()->addDays(60), 'created_at' => Carbon::now()->subDays(35)->addHours(11)->addMinutes(40)],
                ['title' => 'Year-End Performance Bonus Distribution', 'description' => 'Annual performance bonus calculation and distribution based on individual achievements, department goals, and company performance metrics for eligible employees.', 'priority' => 'high', 'status' => 'active', 'start_date' => Carbon::now()->subDays(30)->addHours(14)->addMinutes(55), 'end_date' => Carbon::now()->addDays(45), 'created_at' => Carbon::now()->subDays(30)->addHours(14)->addMinutes(55)]
            ];

            foreach ($announcements as $index => $announcementData) {
                $approvedBy = null;
                if ($announcementData['status'] === 'active') {
                    $approvedBy = !empty($users) ? $users[$index % count($users)] : $userId;
                }

                $announcement = Announcement::updateOrCreate(
                    [
                        'title' => $announcementData['title'],
                        'created_by' => $userId
                    ],
                    [
                        'description' => $announcementData['description'],
                        'start_date' => $announcementData['start_date']->toDateString(),
                        'end_date' => $announcementData['end_date']->toDateString(),
                        'priority' => $announcementData['priority'],
                        'status' => $announcementData['status'],
                        'announcement_category_id' => $categories[$index % count($categories)],
                        'creator_id' => $userId,
                        'created_by' => $userId,
                        'approved_by' => $approvedBy,
                        'created_at' => $announcementData['created_at'],
                        'updated_at' => $announcementData['created_at']
                    ]
                );

                $selectedDepartments = array_rand(array_flip($departments), min(rand(1, 3), count($departments)));
                if (!is_array($selectedDepartments)) {
                    $selectedDepartments = [$selectedDepartments];
                }

                $departmentData = [];
                foreach ($selectedDepartments as $departmentId) {
                    $departmentData[$departmentId] = [
                        'creator_id' => $userId,
                        'created_by' => $userId,
                    ];
                }
                $announcement->departments()->sync($departmentData);
            }
        }
    }
}
