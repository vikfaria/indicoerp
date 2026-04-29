<?php

namespace Workdo\Taskly\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Taskly\Models\Project;
use Workdo\Taskly\Models\ProjectBug;
use Workdo\Taskly\Models\BugStage;
use Workdo\Taskly\Models\BugComment;

class DemoProjectBugSeeder extends Seeder
{
    public function run($userId): void
    {
        if (ProjectBug::where('created_by', $userId)->exists()) {
            return;
        }
        
        if (!empty($userId)) {
            $bugTemplates = [
                'System Development' => [
                    ['title' => 'Database Connection Pool Exhaustion', 'priority' => 'High', 'description' => 'Connection pool getting exhausted during high load, causing application timeouts.'],
                    ['title' => 'Memory Leak in Background Jobs', 'priority' => 'Medium', 'description' => 'Background job processes consuming excessive memory over time.'],
                    ['title' => 'API Response Caching Issues', 'priority' => 'Medium', 'description' => 'Cached API responses not invalidating properly, showing stale data.'],
                    ['title' => 'User Session Timeout Bug', 'priority' => 'High', 'description' => 'User sessions expiring unexpectedly during active usage.'],
                    ['title' => 'Data Migration Integrity Issues', 'priority' => 'High', 'description' => 'Data corruption during system migration process.']
                ],
                'Web Development' => [
                    ['title' => 'Mobile Responsive Layout Issues', 'priority' => 'Medium', 'description' => 'Layout breaking on mobile devices, elements overlapping incorrectly.'],
                    ['title' => 'Cross-browser Compatibility Problems', 'priority' => 'Medium', 'description' => 'Features not working consistently across different browsers.'],
                    ['title' => 'Form Validation Error Messages', 'priority' => 'Low', 'description' => 'Error messages not displaying properly for form validation failures.'],
                    ['title' => 'Image Loading Performance Issues', 'priority' => 'Medium', 'description' => 'Large images causing slow page load times and poor user experience.'],
                    ['title' => 'JavaScript Memory Leaks', 'priority' => 'Medium', 'description' => 'Browser memory consumption increasing over time due to JS memory leaks.']
                ],
                'Mobile Development' => [
                    ['title' => 'App Crash on iOS 15+', 'priority' => 'High', 'description' => 'Application crashing on iOS 15 and newer versions during startup.'],
                    ['title' => 'Push Notification Delivery Issues', 'priority' => 'Medium', 'description' => 'Push notifications not being delivered consistently to all devices.'],
                    ['title' => 'Battery Drain from Background Sync', 'priority' => 'Medium', 'description' => 'Background data synchronization causing excessive battery consumption.'],
                    ['title' => 'Touch Gesture Recognition Problems', 'priority' => 'Low', 'description' => 'Touch gestures not being recognized properly on certain screen areas.'],
                    ['title' => 'Camera Permission Handling Bug', 'priority' => 'Medium', 'description' => 'App not properly requesting or handling camera permissions.']
                ],
                'Marketing Tech' => [
                    ['title' => 'Campaign Analytics Data Mismatch', 'priority' => 'High', 'description' => 'Analytics showing incorrect campaign performance metrics.'],
                    ['title' => 'Social Media API Rate Limiting', 'priority' => 'Medium', 'description' => 'Hitting API rate limits causing campaign posting failures.'],
                    ['title' => 'Email Template Rendering Issues', 'priority' => 'Low', 'description' => 'Email templates not rendering correctly in certain email clients.'],
                    ['title' => 'Audience Segmentation Logic Error', 'priority' => 'Medium', 'description' => 'Target audience segmentation producing incorrect user groups.']
                ],
                'Integration' => [
                    ['title' => 'Third-party API Authentication Failure', 'priority' => 'High', 'description' => 'Authentication tokens expiring unexpectedly with external services.'],
                    ['title' => 'Data Format Mismatch Between Systems', 'priority' => 'Medium', 'description' => 'Data format inconsistencies causing integration failures.'],
                    ['title' => 'Webhook Delivery Timeout Issues', 'priority' => 'Medium', 'description' => 'Webhook notifications timing out during high traffic periods.'],
                    ['title' => 'API Rate Limit Exceeded', 'priority' => 'Low', 'description' => 'Exceeding API call limits causing temporary service disruptions.']
                ],
                'Analytics' => [
                    ['title' => 'Real-time Data Processing Lag', 'priority' => 'High', 'description' => 'Dashboard showing delayed data due to processing bottlenecks.'],
                    ['title' => 'Chart Rendering Performance Issues', 'priority' => 'Medium', 'description' => 'Large datasets causing slow chart rendering and browser freezing.'],
                    ['title' => 'Data Export Functionality Bug', 'priority' => 'Low', 'description' => 'CSV/Excel export generating corrupted or incomplete files.'],
                    ['title' => 'Filter Combination Logic Error', 'priority' => 'Medium', 'description' => 'Multiple filter combinations producing incorrect results.']
                ],
                'Education Tech' => [
                    ['title' => 'Video Streaming Buffering Issues', 'priority' => 'High', 'description' => 'Course videos buffering excessively on slower internet connections.'],
                    ['title' => 'Quiz Timer Synchronization Bug', 'priority' => 'Medium', 'description' => 'Quiz timers not synchronizing properly across different devices.'],
                    ['title' => 'Progress Tracking Accuracy Issues', 'priority' => 'Medium', 'description' => 'Student progress not being tracked accurately for completed modules.'],
                    ['title' => 'Assignment Submission Upload Failure', 'priority' => 'High', 'description' => 'Large file uploads failing during assignment submissions.']
                ],
                'AI/ML' => [
                    ['title' => 'Model Inference Timeout', 'priority' => 'High', 'description' => 'AI model taking too long to process requests, causing timeouts.'],
                    ['title' => 'Training Data Bias Detection', 'priority' => 'Medium', 'description' => 'Model showing biased responses due to training data issues.'],
                    ['title' => 'Natural Language Processing Accuracy', 'priority' => 'Medium', 'description' => 'NLP model misunderstanding user intents in specific contexts.'],
                    ['title' => 'Model Version Deployment Issues', 'priority' => 'Low', 'description' => 'New model versions not deploying correctly to production environment.']
                ],
                'Security' => [
                    ['title' => 'SQL Injection Vulnerability', 'priority' => 'High', 'description' => 'Potential SQL injection points discovered in user input validation.'],
                    ['title' => 'Cross-Site Scripting (XSS) Risk', 'priority' => 'High', 'description' => 'XSS vulnerabilities found in user-generated content areas.'],
                    ['title' => 'Weak Password Policy Enforcement', 'priority' => 'Medium', 'description' => 'Password policy not being enforced consistently across all modules.'],
                    ['title' => 'Session Management Vulnerabilities', 'priority' => 'Medium', 'description' => 'User sessions not being invalidated properly on logout.']
                ],
                'IoT' => [
                    ['title' => 'Device Connectivity Intermittent Issues', 'priority' => 'High', 'description' => 'IoT devices losing connection randomly and not reconnecting automatically.'],
                    ['title' => 'Sensor Data Accuracy Problems', 'priority' => 'Medium', 'description' => 'Temperature and humidity sensors providing inconsistent readings.'],
                    ['title' => 'Firmware Update Deployment Failure', 'priority' => 'Medium', 'description' => 'Over-the-air firmware updates failing on certain device models.'],
                    ['title' => 'Power Management Optimization Bug', 'priority' => 'Low', 'description' => 'Devices not entering sleep mode properly, draining battery faster.']
                ],
                'Infrastructure' => [
                    ['title' => 'Load Balancer Configuration Error', 'priority' => 'High', 'description' => 'Traffic not being distributed evenly across server instances.'],
                    ['title' => 'Auto-scaling Policy Malfunction', 'priority' => 'Medium', 'description' => 'Server instances not scaling up during peak traffic periods.'],
                    ['title' => 'Database Backup Verification Failure', 'priority' => 'Medium', 'description' => 'Automated backup verification process reporting false failures.'],
                    ['title' => 'SSL Certificate Renewal Issues', 'priority' => 'Low', 'description' => 'Automated SSL certificate renewal process not working correctly.']
                ],
                'Automation' => [
                    ['title' => 'Robotic Arm Calibration Drift', 'priority' => 'High', 'description' => 'Robotic arms losing calibration over time, affecting precision.'],
                    ['title' => 'Conveyor Belt Speed Synchronization', 'priority' => 'Medium', 'description' => 'Multiple conveyor belts not maintaining synchronized speeds.'],
                    ['title' => 'Barcode Scanner Recognition Failure', 'priority' => 'Medium', 'description' => 'Barcode scanners failing to read damaged or poorly printed codes.'],
                    ['title' => 'Inventory Count Discrepancy', 'priority' => 'Low', 'description' => 'Automated inventory counting showing discrepancies with manual counts.']
                ],
                'FinTech' => [
                    ['title' => 'Transaction Processing Delay', 'priority' => 'High', 'description' => 'Blockchain transactions taking longer than expected to process.'],
                    ['title' => 'Smart Contract Gas Optimization', 'priority' => 'Medium', 'description' => 'Smart contracts consuming excessive gas fees for simple operations.'],
                    ['title' => 'Wallet Integration Compatibility', 'priority' => 'Medium', 'description' => 'Payment gateway not compatible with certain cryptocurrency wallets.'],
                    ['title' => 'KYC Verification Process Bug', 'priority' => 'Low', 'description' => 'Know Your Customer verification process rejecting valid documents.']
                ],
                'default' => [
                    ['title' => 'Login Authentication Failure', 'priority' => 'High', 'description' => 'Users unable to login with correct credentials, authentication system malfunction.'],
                    ['title' => 'Data Synchronization Issues', 'priority' => 'Medium', 'description' => 'Data not synchronizing properly between different system components.'],
                    ['title' => 'Performance Degradation', 'priority' => 'Medium', 'description' => 'System performance degrading over time, requiring regular restarts.'],
                    ['title' => 'UI Component Rendering Bug', 'priority' => 'Low', 'description' => 'UI components not rendering correctly under certain conditions.']
                ]
            ];

            $projects = Project::where('created_by', $userId)->get();
            $stages = BugStage::where('created_by', $userId)->get();

            if ($projects->isEmpty() || $stages->isEmpty()) return;

            foreach ($projects as $project) {
                // Only create bugs for ongoing and finished projects
                if (!in_array($project->status, ['Ongoing', 'Finished'])) continue;

                $projectType = $project->type ?? 'default';
                $bugs = $bugTemplates[$projectType] ?? $bugTemplates['default'];

                // Fewer bugs for finished projects, more for ongoing
                $numBugs = $project->status === 'Finished' ? rand(2, 4) : rand(3, 5);
                $selectedBugs = collect($bugs)->random(min($numBugs, count($bugs)));

                $userIds = $project->teamMembers()->pluck('user_id')->toArray();
                if (empty($userIds)) continue;

                foreach ($selectedBugs as $bugData) {
                    // Determine bug stage based on project status
                    $stage = $this->getBugStage($stages, $project->status);

                    $bug = ProjectBug::create(array_merge($bugData, [
                        'project_id' => $project->id,
                        'stage_id' => $stage->id,
                        'assigned_to' => json_encode(collect($userIds)->random(min(rand(1, 2), count($userIds)))->values()->all()),
                        'creator_id' => $userId,
                        'created_by' => $userId,
                    ]));

                    // Add contextual comments
                    $this->createBugComments($bug, $stage, $userIds, $project->status);
                }
            }
        }
    }

    private function getBugStage($stages, $projectStatus)
    {
        if ($projectStatus === 'Finished') {
            // Finished projects: 70% resolved, 20% in progress, 10% new
            $rand = rand(1, 100);
            if ($rand <= 70) return $stages->where('name', 'Resolved')->first() ?? $stages->last();
            if ($rand <= 90) return $stages->where('name', 'In Progress')->first() ?? $stages->skip(1)->first();
            return $stages->first();
        }

        // Ongoing projects: 40% new, 35% in progress, 25% resolved
        $rand = rand(1, 100);
        if ($rand <= 40) return $stages->first();
        if ($rand <= 75) return $stages->skip(1)->first() ?? $stages->first();
        return $stages->last();
    }

    private function createBugComments($bug, $stage, $userIds, $projectStatus)
    {
        $commentTemplates = [
            'new' => [
                'Bug reported and needs investigation.',
                'Reproducing the issue to understand root cause.',
                'Assigned to development team for analysis.',
                'Priority set based on impact assessment.',
                'Initial triage completed, awaiting further details.',
                'Additional logs and screenshots requested from reporter.'
            ],
            'in_progress' => [
                'Working on a fix for this issue.',
                'Root cause identified, implementing solution.',
                'Testing potential fix in development environment.',
                'Coordinating with team members on resolution.',
                'Code changes under peer review before deployment.',
                'Temporary workaround applied while final fix is prepared.'
            ],
            'resolved' => [
                'Bug fixed and deployed to production.',
                'Issue resolved, monitoring for any recurrence.',
                'Fix verified and working as expected.',
                'Closed after successful testing and validation.',
                'Resolution documented for future reference.',
                'User confirmation received, issue considered closed.'
            ]
        ];

        $stageKey = str_contains(strtolower($stage->name), 'resolved') ? 'resolved' :
                   (str_contains(strtolower($stage->name), 'progress') ? 'in_progress' : 'new');

        $comments = $commentTemplates[$stageKey];
        $numComments = rand(1, 2);

        for ($i = 0; $i < $numComments; $i++) {
            BugComment::create([
                'bug_id' => $bug->id,
                'user_id' => collect($userIds)->random(),
                'comment' => $comments[array_rand($comments)],
                'created_at' => now()->subDays(rand(1, 30)),
            ]);
        }
    }
}
