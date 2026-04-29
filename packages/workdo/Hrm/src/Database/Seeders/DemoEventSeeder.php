<?php

namespace Workdo\Hrm\Database\Seeders;

use Workdo\Hrm\Models\Event;
use Workdo\Hrm\Models\EventType;
use Workdo\Hrm\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Workdo\Hrm\Models\Employee;

class DemoEventSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Event::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }

        if (!empty($userId)) {
            $eventTypes = EventType::where('created_by', $userId)->pluck('id')->toArray();
            $users = User::whereIn('id', Employee::where('created_by', $userId)->pluck('user_id'))
                ->where('created_by', $userId)
                ->pluck('id')
                ->toArray();
            $departments = Department::where('created_by', $userId)->pluck('id')->toArray();

            if (empty($eventTypes) || empty($departments)) {
                return;
            }

            $events = [
                ['title' => 'Annual Company Kickoff Meeting', 'description' => 'Strategic planning session for new year goals, team alignment, and organizational objectives with department heads and senior management.', 'location' => 'Main Auditorium', 'start_date' => Carbon::now()->subDays(175)->addHours(9)->addMinutes(15), 'end_date' => Carbon::now()->subDays(175)->addHours(9)->addMinutes(15), 'start_time' => '09:00', 'end_time' => '11:00', 'status' => 'approved', 'color' => '#3b82f6', 'created_at' => Carbon::now()->subDays(175)->addHours(9)->addMinutes(15)],
                ['title' => 'Quarterly Performance Review Session', 'description' => 'Comprehensive performance evaluation meeting covering individual achievements, team goals, and professional development planning for all employees.', 'location' => 'Conference Room A', 'start_date' => Carbon::now()->subDays(170)->addHours(10)->addMinutes(30), 'end_date' => Carbon::now()->subDays(170)->addHours(10)->addMinutes(30), 'start_time' => '14:00', 'end_time' => '16:00', 'status' => 'approved', 'color' => '#10b77f', 'created_at' => Carbon::now()->subDays(170)->addHours(10)->addMinutes(30)],
                ['title' => 'Team Building Adventure Workshop', 'description' => 'Interactive team building activities designed to enhance collaboration, communication skills, and workplace relationships among department members.', 'location' => 'Training Center', 'start_date' => Carbon::now()->subDays(165)->addHours(14)->addMinutes(45), 'end_date' => Carbon::now()->subDays(165)->addHours(14)->addMinutes(45), 'start_time' => '10:00', 'end_time' => '17:00', 'status' => 'pending', 'color' => '#8b5cf6', 'created_at' => Carbon::now()->subDays(165)->addHours(14)->addMinutes(45)],
                ['title' => 'Technology Innovation Training Program', 'description' => 'Comprehensive training session covering new technology tools, software updates, and digital transformation initiatives for enhanced productivity.', 'location' => 'Workshop Room', 'start_date' => Carbon::now()->subDays(160)->addHours(11)->addMinutes(20), 'end_date' => Carbon::now()->subDays(160)->addHours(11)->addMinutes(20), 'start_time' => '09:00', 'end_time' => '12:00', 'status' => 'approved', 'color' => '#f59e0b', 'created_at' => Carbon::now()->subDays(160)->addHours(11)->addMinutes(20)],
                ['title' => 'Client Relationship Review Meeting', 'description' => 'Monthly client feedback analysis, relationship assessment, and strategic planning session for improving customer satisfaction and retention.', 'location' => 'Executive Lounge', 'start_date' => Carbon::now()->subDays(155)->addHours(13)->addMinutes(10), 'end_date' => Carbon::now()->subDays(155)->addHours(13)->addMinutes(10), 'start_time' => '14:00', 'end_time' => '16:00', 'status' => 'pending', 'color' => '#ef4444', 'created_at' => Carbon::now()->subDays(155)->addHours(13)->addMinutes(10)],
                ['title' => 'Workplace Safety Protocol Briefing', 'description' => 'Mandatory safety training covering emergency procedures, workplace hazard identification, and compliance with occupational health standards.', 'location' => 'Cafeteria', 'start_date' => Carbon::now()->subDays(150)->addHours(15)->addMinutes(35), 'end_date' => Carbon::now()->subDays(150)->addHours(15)->addMinutes(35), 'start_time' => '11:00', 'end_time' => '12:00', 'status' => 'approved', 'color' => '#dc2626', 'created_at' => Carbon::now()->subDays(150)->addHours(15)->addMinutes(35)],
                ['title' => 'Monthly Budget Analysis Workshop', 'description' => 'Financial planning session covering budget allocation, expense analysis, and resource optimization strategies for departmental efficiency.', 'location' => 'Board Room', 'start_date' => Carbon::now()->subDays(145)->addHours(8)->addMinutes(50), 'end_date' => Carbon::now()->subDays(145)->addHours(8)->addMinutes(50), 'start_time' => '09:00', 'end_time' => '11:00', 'status' => 'reject', 'color' => '#059669', 'created_at' => Carbon::now()->subDays(145)->addHours(8)->addMinutes(50)],
                ['title' => 'Employee Performance Evaluation Conference', 'description' => 'Comprehensive performance assessment meeting including goal setting, feedback sessions, and career development planning for staff members.', 'location' => 'Meeting Hall', 'start_date' => Carbon::now()->subDays(140)->addHours(12)->addMinutes(25), 'end_date' => Carbon::now()->subDays(140)->addHours(12)->addMinutes(25), 'start_time' => '15:00', 'end_time' => '17:00', 'status' => 'approved', 'color' => '#06b6d4', 'created_at' => Carbon::now()->subDays(140)->addHours(12)->addMinutes(25)],
                ['title' => 'Innovation and Creativity Workshop', 'description' => 'Creative thinking session focused on innovation strategies, problem-solving techniques, and process improvement initiatives across departments.', 'location' => 'Training Center', 'start_date' => Carbon::now()->subDays(135)->addHours(16)->addMinutes(40), 'end_date' => Carbon::now()->subDays(135)->addHours(16)->addMinutes(40), 'start_time' => '10:00', 'end_time' => '15:00', 'status' => 'pending', 'color' => '#7c3aed', 'created_at' => Carbon::now()->subDays(135)->addHours(16)->addMinutes(40)],
                ['title' => 'Company-Wide All-Hands Meeting', 'description' => 'Monthly organizational update covering company performance, strategic initiatives, and important announcements for all employees.', 'location' => 'Main Auditorium', 'start_date' => Carbon::now()->subDays(130)->addHours(9)->addMinutes(55), 'end_date' => Carbon::now()->subDays(130)->addHours(9)->addMinutes(55), 'start_time' => '16:00', 'end_time' => '17:00', 'status' => 'approved', 'color' => '#84cc16', 'created_at' => Carbon::now()->subDays(130)->addHours(9)->addMinutes(55)],
                ['title' => 'Product Development Strategy Session', 'description' => 'Strategic planning meeting for product development initiatives, market analysis, and innovation roadmap planning with cross-functional teams.', 'location' => 'Executive Lounge', 'start_date' => Carbon::now()->subDays(125)->addHours(11)->addMinutes(15), 'end_date' => Carbon::now()->subDays(125)->addHours(11)->addMinutes(15), 'start_time' => '14:00', 'end_time' => '16:00', 'status' => 'approved', 'color' => '#10b77f', 'created_at' => Carbon::now()->subDays(125)->addHours(11)->addMinutes(15)],
                ['title' => 'Customer Feedback Analysis Workshop', 'description' => 'Comprehensive customer feedback review session focusing on service improvement, satisfaction metrics, and customer retention strategies.', 'location' => 'Meeting Hall', 'start_date' => Carbon::now()->subDays(120)->addHours(14)->addMinutes(30), 'end_date' => Carbon::now()->subDays(120)->addHours(14)->addMinutes(30), 'start_time' => '11:00', 'end_time' => '13:00', 'status' => 'pending', 'color' => '#8b5cf6', 'created_at' => Carbon::now()->subDays(120)->addHours(14)->addMinutes(30)],
                ['title' => 'Professional Skills Development Seminar', 'description' => 'Comprehensive skill enhancement workshop covering leadership development, communication skills, and professional growth opportunities for employees.', 'location' => 'Training Center', 'start_date' => Carbon::now()->subDays(115)->addHours(10)->addMinutes(45), 'end_date' => Carbon::now()->subDays(115)->addHours(10)->addMinutes(45), 'start_time' => '09:00', 'end_time' => '17:00', 'status' => 'approved', 'color' => '#f59e0b', 'created_at' => Carbon::now()->subDays(115)->addHours(10)->addMinutes(45)],
                ['title' => 'Project Status Review Conference', 'description' => 'Comprehensive project management meeting covering ongoing initiatives, milestone tracking, and resource allocation across all departments.', 'location' => 'Board Room', 'start_date' => Carbon::now()->subDays(110)->addHours(13)->addMinutes(20), 'end_date' => Carbon::now()->subDays(110)->addHours(13)->addMinutes(20), 'start_time' => '14:00', 'end_time' => '16:00', 'status' => 'reject', 'color' => '#6366f1', 'created_at' => Carbon::now()->subDays(110)->addHours(13)->addMinutes(20)],
                ['title' => 'Employee Health and Wellness Seminar', 'description' => 'Health awareness session covering wellness programs, stress management techniques, and workplace ergonomics for employee wellbeing enhancement.', 'location' => 'Workshop Room', 'start_date' => Carbon::now()->subDays(105)->addHours(15)->addMinutes(10), 'end_date' => Carbon::now()->subDays(105)->addHours(15)->addMinutes(10), 'start_time' => '10:00', 'end_time' => '12:00', 'status' => 'approved', 'color' => '#14b8a6', 'created_at' => Carbon::now()->subDays(105)->addHours(15)->addMinutes(10)],

                // Upcoming Events - Next Month
                ['title' => 'Monthly Team Sync Meeting', 'description' => 'Regular monthly synchronization meeting for all teams to align on goals, share updates, and coordinate upcoming initiatives.', 'location' => 'Main Auditorium', 'start_date' => Carbon::now()->addDays(5), 'end_date' => Carbon::now()->addDays(5), 'start_time' => '09:00', 'end_time' => '11:00', 'status' => 'approved', 'color' => '#3b82f6', 'created_at' => Carbon::now()->subDays(10)],
                ['title' => 'New Product Launch Presentation', 'description' => 'Exciting presentation of our latest product features, market positioning, and launch strategy for the upcoming quarter.', 'location' => 'Executive Lounge', 'start_date' => Carbon::now()->addDays(10), 'end_date' => Carbon::now()->addDays(10), 'start_time' => '14:00', 'end_time' => '16:00', 'status' => 'pending', 'color' => '#10b77f', 'created_at' => Carbon::now()->subDays(8)],
                ['title' => 'Quarterly All-Hands Meeting', 'description' => 'Comprehensive quarterly review covering company performance, strategic updates, and important announcements for all employees.', 'location' => 'Main Auditorium', 'start_date' => Carbon::now()->addDays(15), 'end_date' => Carbon::now()->addDays(15), 'start_time' => '16:00', 'end_time' => '17:30', 'status' => 'approved', 'color' => '#f59e0b', 'created_at' => Carbon::now()->subDays(5)],
                ['title' => 'Skills Development Workshop', 'description' => 'Professional development workshop focusing on emerging technologies, leadership skills, and career advancement opportunities for employees.', 'location' => 'Training Center', 'start_date' => Carbon::now()->addDays(20), 'end_date' => Carbon::now()->addDays(20), 'start_time' => '09:00', 'end_time' => '17:00', 'status' => 'pending', 'color' => '#8b5cf6', 'created_at' => Carbon::now()->subDays(3)],
                ['title' => 'Client Appreciation Event', 'description' => 'Special networking event to appreciate our valued clients, showcase recent achievements, and strengthen business relationships.', 'location' => 'Conference Room A', 'start_date' => Carbon::now()->addDays(25), 'end_date' => Carbon::now()->addDays(25), 'start_time' => '18:00', 'end_time' => '20:00', 'status' => 'approved', 'color' => '#ef4444', 'created_at' => Carbon::now()->subDays(2)],
                ['title' => 'Innovation Brainstorming Session', 'description' => 'Creative brainstorming session to generate innovative ideas, discuss process improvements, and explore new business opportunities.', 'location' => 'Workshop Room', 'start_date' => Carbon::now()->addDays(30), 'end_date' => Carbon::now()->addDays(30), 'start_time' => '10:00', 'end_time' => '15:00', 'status' => 'pending', 'color' => '#7c3aed', 'created_at' => Carbon::now()->subDays(1)],
                ['title' => 'Financial Performance Review Meeting', 'description' => 'Monthly financial analysis covering revenue performance, expense management, and budget optimization strategies for organizational growth.', 'location' => 'Executive Lounge', 'start_date' => Carbon::now()->subDays(100)->addHours(8)->addMinutes(35), 'end_date' => Carbon::now()->subDays(100)->addHours(8)->addMinutes(35), 'start_time' => '15:00', 'end_time' => '17:00', 'status' => 'pending', 'color' => '#059669', 'created_at' => Carbon::now()->subDays(100)->addHours(8)->addMinutes(35)],
                ['title' => 'Team Recognition and Awards Ceremony', 'description' => 'Annual recognition event celebrating outstanding employee achievements, service milestones, and exceptional contributions to organizational success.', 'location' => 'Main Auditorium', 'start_date' => Carbon::now()->subDays(95)->addHours(12)->addMinutes(50), 'end_date' => Carbon::now()->subDays(95)->addHours(12)->addMinutes(50), 'start_time' => '18:00', 'end_time' => '20:00', 'status' => 'approved', 'color' => '#f97316', 'created_at' => Carbon::now()->subDays(95)->addHours(12)->addMinutes(50)],
                ['title' => 'Cybersecurity Awareness Training Program', 'description' => 'Mandatory cybersecurity training covering data protection, phishing prevention, and information security best practices for all employees.', 'location' => 'Conference Room A', 'start_date' => Carbon::now()->subDays(90)->addHours(16)->addMinutes(25), 'end_date' => Carbon::now()->subDays(90)->addHours(16)->addMinutes(25), 'start_time' => '09:00', 'end_time' => '12:00', 'status' => 'approved', 'color' => '#dc2626', 'created_at' => Carbon::now()->subDays(90)->addHours(16)->addMinutes(25)],
                ['title' => 'New Employee Orientation Workshop', 'description' => 'Comprehensive onboarding program for new hires covering company culture, policies, procedures, and integration into organizational structure.', 'location' => 'Training Center', 'start_date' => Carbon::now()->subDays(85)->addHours(9)->addMinutes(40), 'end_date' => Carbon::now()->subDays(85)->addHours(9)->addMinutes(40), 'start_time' => '09:00', 'end_time' => '17:00', 'status' => 'pending', 'color' => '#84cc16', 'created_at' => Carbon::now()->subDays(85)->addHours(9)->addMinutes(40)],
                ['title' => 'Emergency Preparedness Drill Exercise', 'description' => 'Mandatory emergency response training covering evacuation procedures, safety protocols, and crisis management for workplace security.', 'location' => 'Cafeteria', 'start_date' => Carbon::now()->subDays(80)->addHours(11)->addMinutes(55), 'end_date' => Carbon::now()->subDays(80)->addHours(11)->addMinutes(55), 'start_time' => '10:00', 'end_time' => '11:30', 'status' => 'approved', 'color' => '#ef4444', 'created_at' => Carbon::now()->subDays(80)->addHours(11)->addMinutes(55)],
                ['title' => 'Innovation Challenge Competition Launch', 'description' => 'Company-wide innovation contest encouraging creative solutions, process improvements, and technological advancements with prizes for winning ideas.', 'location' => 'Meeting Hall', 'start_date' => Carbon::now()->subDays(75)->addHours(14)->addMinutes(15), 'end_date' => Carbon::now()->subDays(75)->addHours(14)->addMinutes(15), 'start_time' => '14:00', 'end_time' => '16:00', 'status' => 'reject', 'color' => '#7c3aed', 'created_at' => Carbon::now()->subDays(75)->addHours(14)->addMinutes(15)],
                ['title' => 'Quality Management System Training', 'description' => 'ISO quality certification training covering process documentation, compliance procedures, and quality assurance standards across departments.', 'location' => 'Workshop Room', 'start_date' => Carbon::now()->subDays(70)->addHours(10)->addMinutes(30), 'end_date' => Carbon::now()->subDays(70)->addHours(10)->addMinutes(30), 'start_time' => '09:00', 'end_time' => '17:00', 'status' => 'approved', 'color' => '#10b77f', 'created_at' => Carbon::now()->subDays(70)->addHours(10)->addMinutes(30)],
                ['title' => 'Employee Feedback Survey Discussion', 'description' => 'Interactive session discussing employee satisfaction survey results, workplace improvements, and organizational culture enhancement initiatives.', 'location' => 'Board Room', 'start_date' => Carbon::now()->subDays(65)->addHours(13)->addMinutes(45), 'end_date' => Carbon::now()->subDays(65)->addHours(13)->addMinutes(45), 'start_time' => '14:00', 'end_time' => '16:00', 'status' => 'pending', 'color' => '#06b6d4', 'created_at' => Carbon::now()->subDays(65)->addHours(13)->addMinutes(45)],
                ['title' => 'Digital Transformation Strategy Meeting', 'description' => 'Strategic planning session for technology infrastructure upgrade, digital tools implementation, and automation initiatives across operations.', 'location' => 'Executive Lounge', 'start_date' => Carbon::now()->subDays(60)->addHours(15)->addMinutes(20), 'end_date' => Carbon::now()->subDays(60)->addHours(15)->addMinutes(20), 'start_time' => '10:00', 'end_time' => '15:00', 'status' => 'approved', 'color' => '#3b82f6', 'created_at' => Carbon::now()->subDays(60)->addHours(15)->addMinutes(20)],
                ['title' => 'Cross-Department Collaboration Workshop', 'description' => 'Initiative promoting interdepartmental cooperation through joint projects, shared goals, and collaborative problem-solving for organizational efficiency.', 'location' => 'Training Center', 'start_date' => Carbon::now()->subDays(55)->addHours(8)->addMinutes(10), 'end_date' => Carbon::now()->subDays(55)->addHours(8)->addMinutes(10), 'start_time' => '09:00', 'end_time' => '17:00', 'status' => 'pending', 'color' => '#f59e0b', 'created_at' => Carbon::now()->subDays(55)->addHours(8)->addMinutes(10)],
                ['title' => 'Leadership Development Program Session', 'description' => 'Executive leadership training for managers covering team management, strategic thinking, decision-making, and organizational leadership skills.', 'location' => 'Conference Room A', 'start_date' => Carbon::now()->subDays(50)->addHours(12)->addMinutes(35), 'end_date' => Carbon::now()->subDays(50)->addHours(12)->addMinutes(35), 'start_time' => '09:00', 'end_time' => '17:00', 'status' => 'approved', 'color' => '#8b5cf6', 'created_at' => Carbon::now()->subDays(50)->addHours(12)->addMinutes(35)],
                ['title' => 'Environmental Sustainability Initiative Launch', 'description' => 'Green initiative promoting eco-friendly practices including recycling programs, energy conservation, and sustainable business operations.', 'location' => 'Main Auditorium', 'start_date' => Carbon::now()->subDays(45)->addHours(16)->addMinutes(50), 'end_date' => Carbon::now()->subDays(45)->addHours(16)->addMinutes(50), 'start_time' => '14:00', 'end_time' => '16:00', 'status' => 'pending', 'color' => '#14b8a6', 'created_at' => Carbon::now()->subDays(45)->addHours(16)->addMinutes(50)],
                ['title' => 'Customer Service Excellence Training', 'description' => 'Comprehensive customer service training focusing on communication skills, problem resolution, and customer satisfaction improvement strategies.', 'location' => 'Workshop Room', 'start_date' => Carbon::now()->subDays(40)->addHours(9)->addMinutes(25), 'end_date' => Carbon::now()->subDays(40)->addHours(9)->addMinutes(25), 'start_time' => '09:00', 'end_time' => '17:00', 'status' => 'approved', 'color' => '#059669', 'created_at' => Carbon::now()->subDays(40)->addHours(9)->addMinutes(25)],
                ['title' => 'Year-End Performance Bonus Meeting', 'description' => 'Annual performance bonus calculation and distribution meeting based on individual achievements and company performance metrics.', 'location' => 'Board Room', 'start_date' => Carbon::now()->subDays(35)->addHours(11)->addMinutes(40), 'end_date' => Carbon::now()->subDays(35)->addHours(11)->addMinutes(40), 'start_time' => '15:00', 'end_time' => '17:00', 'status' => 'approved', 'color' => '#f97316', 'created_at' => Carbon::now()->subDays(35)->addHours(11)->addMinutes(40)],
                ['title' => 'Future Planning Strategy Conference', 'description' => 'Strategic planning session for next year initiatives, market expansion, and long-term organizational goals with senior management.', 'location' => 'Executive Lounge', 'start_date' => Carbon::now()->subDays(30)->addHours(14)->addMinutes(55), 'end_date' => Carbon::now()->subDays(30)->addHours(14)->addMinutes(55), 'start_time' => '09:00', 'end_time' => '17:00', 'status' => 'pending', 'color' => '#6366f1', 'created_at' => Carbon::now()->subDays(30)->addHours(14)->addMinutes(55)]
            ];

            foreach ($events as $index => $eventData) {
                $approvedBy = null;
                if ($eventData['status'] === 'approved') {
                    $approvedBy = !empty($users) ? $users[$index % count($users)] : $userId;
                }

                $event = Event::updateOrCreate(
                    [
                        'title' => $eventData['title'],
                        'start_date' => $eventData['start_date']->toDateString(),
                        'created_by' => $userId
                    ],
                    [
                        'description' => $eventData['description'],
                        'event_type_id' => $eventTypes[$index % count($eventTypes)],
                        'end_date' => $eventData['end_date']->toDateString(),
                        'start_time' => $eventData['start_time'],
                        'end_time' => $eventData['end_time'],
                        'location' => $eventData['location'],
                        'status' => $eventData['status'],
                        'color' => $eventData['color'],
                        'approved_by' => $approvedBy,
                        'creator_id' => $userId,
                        'created_by' => $userId,
                        'created_at' => $eventData['created_at'],
                        'updated_at' => $eventData['created_at']
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
                $event->departments()->sync($departmentData);
            }
        }
    }
}
