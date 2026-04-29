<?php

namespace Workdo\Taskly\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Workdo\Taskly\Models\Project;
use Workdo\Taskly\Models\ProjectFile;

class DemoProjectSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Project::where('created_by', $userId)->exists()) {
            return;
        }

        if (!empty($userId)) {

            $projects = [
                // --- Older Finished Projects (within last 6 months) ---
                ['name' => 'Inventory Management System', 'description' => 'Automated inventory tracking system with barcode scanning and reporting.', 'budget' => 30000, 'start_date' => now()->subDays(170), 'end_date' => now()->subDays(120), 'status' => 'Finished'],
                ['name' => 'Digital Marketing Campaign Tool', 'description' => 'Automated marketing tool for social media campaigns and analytics.', 'budget' => 32000, 'start_date' => now()->subDays(160), 'end_date' => now()->subDays(110), 'status' => 'Finished'],
                ['name' => 'CRM System Integration', 'description' => 'Integration of existing CRM with third-party services and API development.', 'budget' => 35000, 'start_date' => now()->subDays(150), 'end_date' => now()->subDays(100), 'status' => 'Finished'],
                ['name' => 'Project Portfolio Dashboard', 'description' => 'Dashboard for monitoring KPIs and project status in real-time.', 'budget' => 27000, 'start_date' => now()->subDays(145), 'end_date' => now()->subDays(95), 'status' => 'Finished'],
                ['name' => 'Fleet Management System', 'description' => 'Vehicle tracking and maintenance scheduling system.', 'budget' => 34000, 'start_date' => now()->subDays(140), 'end_date' => now()->subDays(90), 'status' => 'Finished'],

                // --- Ongoing Projects ---
                ['name' => 'HR & Payroll Management System', 'description' => 'System to manage employee records, payroll, and attendance with automation.', 'budget' => 38000, 'start_date' => now()->subDays(130), 'end_date' => now()->addDays(20), 'status' => 'Ongoing'],
                ['name' => 'E-commerce Platform Development', 'description' => 'Complete e-commerce solution with payment integration and inventory management.', 'budget' => 75000, 'start_date' => now()->subDays(120), 'end_date' => now()->addDays(30), 'status' => 'Ongoing'],
                ['name' => 'Learning Management System', 'description' => 'Online learning platform with course management, quizzes, and progress tracking.', 'budget' => 55000, 'start_date' => now()->subDays(110), 'end_date' => now()->addDays(40), 'status' => 'Ongoing'],
                ['name' => 'Mobile App for Healthcare', 'description' => 'Cross-platform mobile application for patient management and telemedicine.', 'budget' => 50000, 'start_date' => now()->subDays(100), 'end_date' => now()->addDays(50), 'status' => 'Ongoing'],
                ['name' => 'Food Delivery Application', 'description' => 'End-to-end mobile app for ordering, tracking, and delivering food.', 'budget' => 48000, 'start_date' => now()->subDays(90), 'end_date' => now()->addDays(60), 'status' => 'Ongoing'],

                // --- More Recent Ongoing ---
                ['name' => 'Data Analytics Dashboard', 'description' => 'Real-time analytics dashboard with advanced reporting capabilities.', 'budget' => 40000, 'start_date' => now()->subDays(80), 'end_date' => now()->addDays(45), 'status' => 'Ongoing'],
                ['name' => 'AI Chatbot Development', 'description' => 'Intelligent chatbot with natural language processing for customer support.', 'budget' => 45000, 'start_date' => now()->subDays(70), 'end_date' => now()->addDays(55), 'status' => 'Ongoing'],
                ['name' => 'Online Ticket Booking System', 'description' => 'Web-based system for booking movie and event tickets with seat selection.', 'budget' => 42000, 'start_date' => now()->subDays(65), 'end_date' => now()->addDays(40), 'status' => 'Ongoing'],
                ['name' => 'Security Audit & Compliance', 'description' => 'Comprehensive security assessment and compliance implementation.', 'budget' => 25000, 'start_date' => now()->subDays(55), 'end_date' => now()->addDays(30), 'status' => 'Ongoing'],
                ['name' => 'Customer Feedback Portal', 'description' => 'Web portal for collecting, analyzing, and reporting customer feedback.', 'budget' => 28000, 'start_date' => now()->subDays(50), 'end_date' => now()->addDays(35), 'status' => 'Ongoing'],

                // --- Future / On Hold ---
                ['name' => 'Smart IoT Home Automation', 'description' => 'IoT-based smart home system for controlling devices remotely.', 'budget' => 65000, 'start_date' => now()->subDays(40), 'end_date' => now()->addDays(80), 'status' => 'Onhold'],
                ['name' => 'Cloud Migration Project', 'description' => 'Migration of legacy systems to cloud infrastructure with security enhancements.', 'budget' => 60000, 'start_date' => now()->subDays(30), 'end_date' => now()->addDays(90), 'status' => 'Onhold'],
                ['name' => 'Warehouse Automation System', 'description' => 'Robotics-driven warehouse operations and order fulfillment system.', 'budget' => 70000, 'start_date' => now()->subDays(25), 'end_date' => now()->addDays(75), 'status' => 'Onhold'],
                ['name' => 'Virtual Event Platform', 'description' => 'Platform for hosting online events with live streaming and networking features.', 'budget' => 46000, 'start_date' => now()->subDays(20), 'end_date' => now()->addDays(70), 'status' => 'Onhold'],
                ['name' => 'Blockchain Payment Gateway', 'description' => 'Secure blockchain-based gateway for digital transactions.', 'budget' => 80000, 'start_date' => now()->subDays(10), 'end_date' => now()->addDays(90), 'status' => 'Onhold'],
            ];


            foreach ($projects as $projectData) {
                $project = Project::create(array_merge($projectData, [
                    'creator_id' => $userId,
                    'created_by' => $userId,
                ]));

                // Add team members to project
                $teamMemberIds = User::where('created_by', $userId)->emp()->pluck('id')->toArray();
                if (!empty($teamMemberIds)) {
                    $project->teamMembers()->sync(collect($teamMemberIds)->random(min(rand(2, 3), count($teamMemberIds))));
                }

                // Add clients to project
                $clientIds = User::where('created_by', $userId)->where('type', 'client')->pluck('id')->toArray();
                if (!empty($clientIds)) {
                    $project->clients()->sync(collect($clientIds)->random(min(rand(1, 2), count($clientIds))));
                }

                // Add project files
                $files = [
                    ['file_name' => 'project-requirements.pdf', 'file_path' => 'dummy.pdf'],
                    ['file_name' => 'technical-specifications.docx', 'file_path' => 'dummy.pdf'],
                    ['file_name' => 'design-mockups.zip', 'file_path' => 'dummy.pdf'],
                    ['file_name' => 'database-schema.sql', 'file_path' => 'dummy.pdf'],
                ];

                $selectedFiles = collect($files)->random(rand(3, 4));
                foreach ($selectedFiles as $fileData) {
                    ProjectFile::create([
                        'project_id' => $project->id,
                        'file_name' => $fileData['file_name'],
                        'file_path' => $fileData['file_path']
                    ]);
                }
            }
        }
    }
}
