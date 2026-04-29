<?php

namespace Workdo\Taskly\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Taskly\Models\Project;
use Workdo\Taskly\Models\ProjectTask;
use Workdo\Taskly\Models\TaskStage;
use Workdo\Taskly\Models\ProjectMilestone;
use Carbon\Carbon;

class DemoProjectTaskSeeder extends Seeder
{
    public function run($userId): void
    {
        if (ProjectTask::where('created_by', $userId)->exists()) {
            return;
        }
        
        if (!empty($userId)) {
            $projects = Project::where('created_by', $userId)->get();
            $stages = TaskStage::where('created_by', $userId)->orderBy('order')->get();

            if ($projects->isEmpty() || $stages->isEmpty()) return;

            foreach ($projects as $project) {
                $milestones = ProjectMilestone::where('project_id', $project->id)->orderBy('start_date')->get();
                $teamMembers = $project->teamMembers()->pluck('user_id')->toArray();

                if (empty($teamMembers)) continue;

                // Create tasks for each milestone
                foreach ($milestones as $milestone) {
                    $tasks = $this->getTasksForMilestone($milestone->title);
                    $numTasks = rand(1, 3); // 1-3 tasks per milestone
                    $selectedTasks = collect($tasks)->random(min($numTasks, count($tasks)));

                    foreach ($selectedTasks as $index => $taskData) {
                        $milestoneStart = Carbon::parse($milestone->start_date);
                        $milestoneEnd = Carbon::parse($milestone->end_date);
                        $milestoneDuration = $milestoneStart->diffInDays($milestoneEnd);

                        // Distribute tasks within milestone timeline
                        $taskStartOffset = ($index / count($selectedTasks)) * $milestoneDuration;
                        $taskStart = $milestoneStart->copy()->addDays($taskStartOffset);
                        $taskDuration = rand(3, min(10, $milestoneDuration));
                        $taskEnd = $taskStart->copy()->addDays($taskDuration);

                        // Ensure task doesn't exceed milestone end
                        if ($taskEnd->gt($milestoneEnd)) {
                            $taskEnd = $milestoneEnd->copy();
                        }

                        // Determine stage based on milestone status and timing
                        $stage = $this->getTaskStage($stages, $milestone, $taskStart);

                        $task = ProjectTask::create(array_merge($taskData, [
                            'project_id' => $project->id,
                            'milestone_id' => $milestone->id,
                            'stage_id' => $stage->id,
                            'assigned_to' => json_encode(collect($teamMembers)->random(min(rand(1, 2), count($teamMembers)))->values()->all()),
                            'duration' => $taskStart->format('Y-m-d') . ' - ' . $taskEnd->format('Y-m-d'),
                            'creator_id' => $userId,
                            'created_by' => $userId,
                        ]));

                        // Add subtasks and comments
                        $this->createSubtasks($task, $milestone->title, $taskStart, $userId);
                        $this->createComments($task, $stage, $teamMembers, $taskStart, $userId);
                    }
                }
            }
        }
    }

    private function getTasksForMilestone($milestoneTitle)
    {
        // Tasks are now created for each milestone with relevant content
        $taskMap = [
            'Project Initiation & Planning' => [
                ['title' => 'Project Charter Creation', 'priority' => 'High', 'description' => 'Define project scope, objectives, and success criteria.'],
                ['title' => 'Stakeholder Identification', 'priority' => 'High', 'description' => 'Identify and analyze all project stakeholders.'],
                ['title' => 'Resource Planning', 'priority' => 'Medium', 'description' => 'Plan human and technical resources needed.'],
                ['title' => 'Risk Assessment', 'priority' => 'Medium', 'description' => 'Identify potential risks and mitigation strategies.']
            ],
            'Requirements Gathering' => [
                ['title' => 'Business Requirements Analysis', 'priority' => 'High', 'description' => 'Gather and document detailed business requirements.'],
                ['title' => 'User Story Creation', 'priority' => 'High', 'description' => 'Create user stories and acceptance criteria.'],
                ['title' => 'Requirements Validation', 'priority' => 'Medium', 'description' => 'Validate requirements with stakeholders.'],
                ['title' => 'Requirements Documentation', 'priority' => 'Medium', 'description' => 'Create comprehensive requirements documentation.']
            ],
            'System Architecture Design' => [
                ['title' => 'Technical Architecture Planning', 'priority' => 'High', 'description' => 'Design overall system architecture and components.'],
                ['title' => 'Database Design', 'priority' => 'High', 'description' => 'Design database schema and relationships.'],
                ['title' => 'API Design', 'priority' => 'Medium', 'description' => 'Design RESTful API endpoints and specifications.'],
                ['title' => 'Integration Specifications', 'priority' => 'Medium', 'description' => 'Define third-party integration requirements.']
            ],
            'UI/UX Design & Prototyping' => [
                ['title' => 'User Interface Mockups', 'priority' => 'High', 'description' => 'Create detailed UI mockups and designs.'],
                ['title' => 'Wireframe Creation', 'priority' => 'Medium', 'description' => 'Develop wireframes for all major screens.'],
                ['title' => 'Interactive Prototypes', 'priority' => 'Medium', 'description' => 'Build clickable prototypes for user testing.'],
                ['title' => 'Design System Creation', 'priority' => 'Low', 'description' => 'Establish design system and style guide.']
            ],
            'Backend Development Phase 1' => [
                ['title' => 'Core API Development', 'priority' => 'High', 'description' => 'Develop core API endpoints and business logic.'],
                ['title' => 'Database Implementation', 'priority' => 'High', 'description' => 'Implement database schema and migrations.'],
                ['title' => 'Authentication System', 'priority' => 'High', 'description' => 'Implement user authentication and authorization.'],
                ['title' => 'Data Validation', 'priority' => 'Medium', 'description' => 'Add input validation and sanitization.']
            ],
            'Frontend Development Phase 1' => [
                ['title' => 'Component Development', 'priority' => 'High', 'description' => 'Build reusable UI components.'],
                ['title' => 'User Interface Implementation', 'priority' => 'High', 'description' => 'Implement user interfaces based on designs.'],
                ['title' => 'Responsive Design', 'priority' => 'Medium', 'description' => 'Ensure responsive design across devices.'],
                ['title' => 'Frontend Routing', 'priority' => 'Medium', 'description' => 'Implement client-side routing and navigation.']
            ],
            'Integration & API Development' => [
                ['title' => 'Third-party Integration', 'priority' => 'High', 'description' => 'Integrate with external services and APIs.'],
                ['title' => 'API Endpoint Creation', 'priority' => 'High', 'description' => 'Create additional API endpoints as needed.'],
                ['title' => 'Data Synchronization', 'priority' => 'Medium', 'description' => 'Implement data sync between systems.'],
                ['title' => 'Integration Testing', 'priority' => 'Medium', 'description' => 'Test all integrations thoroughly.']
            ],
            'Security Implementation' => [
                ['title' => 'Security Audit', 'priority' => 'High', 'description' => 'Conduct comprehensive security assessment.'],
                ['title' => 'Vulnerability Assessment', 'priority' => 'High', 'description' => 'Identify and fix security vulnerabilities.'],
                ['title' => 'Encryption Implementation', 'priority' => 'Medium', 'description' => 'Implement data encryption and secure storage.'],
                ['title' => 'Access Control Setup', 'priority' => 'Medium', 'description' => 'Configure role-based access controls.']
            ],
            'Performance Optimization' => [
                ['title' => 'Code Optimization', 'priority' => 'Medium', 'description' => 'Optimize code for better performance.'],
                ['title' => 'Database Tuning', 'priority' => 'High', 'description' => 'Optimize database queries and indexes.'],
                ['title' => 'Caching Implementation', 'priority' => 'Medium', 'description' => 'Implement caching strategies.'],
                ['title' => 'Load Testing', 'priority' => 'Medium', 'description' => 'Conduct performance and load testing.']
            ],
            'Quality Assurance Testing' => [
                ['title' => 'Unit Testing', 'priority' => 'High', 'description' => 'Write and execute unit tests.'],
                ['title' => 'Integration Testing', 'priority' => 'High', 'description' => 'Test system integration points.'],
                ['title' => 'User Acceptance Testing', 'priority' => 'High', 'description' => 'Conduct UAT with stakeholders.'],
                ['title' => 'Bug Fixing', 'priority' => 'Medium', 'description' => 'Fix identified bugs and issues.']
            ],
            'User Training & Documentation' => [
                ['title' => 'User Manual Creation', 'priority' => 'Medium', 'description' => 'Create comprehensive user documentation.'],
                ['title' => 'Training Material Development', 'priority' => 'Medium', 'description' => 'Develop training materials and guides.'],
                ['title' => 'Knowledge Transfer Sessions', 'priority' => 'Low', 'description' => 'Conduct training sessions for users.'],
                ['title' => 'Technical Documentation', 'priority' => 'Low', 'description' => 'Create technical documentation for maintenance.']
            ],
            'Production Deployment' => [
                ['title' => 'Environment Setup', 'priority' => 'High', 'description' => 'Set up production environment.'],
                ['title' => 'Deployment Pipeline', 'priority' => 'High', 'description' => 'Configure automated deployment pipeline.'],
                ['title' => 'Go-live Activities', 'priority' => 'High', 'description' => 'Execute production deployment.'],
                ['title' => 'Monitoring Setup', 'priority' => 'Medium', 'description' => 'Set up system monitoring and alerts.']
            ],
            'Post-Launch Monitoring' => [
                ['title' => 'System Monitoring', 'priority' => 'High', 'description' => 'Monitor system performance and health.'],
                ['title' => 'Performance Tracking', 'priority' => 'Medium', 'description' => 'Track key performance metrics.'],
                ['title' => 'Bug Fixes', 'priority' => 'Medium', 'description' => 'Address post-launch issues.'],
                ['title' => 'User Feedback Analysis', 'priority' => 'Low', 'description' => 'Analyze user feedback and suggestions.']
            ],
            'Project Closure & Handover' => [
                ['title' => 'Final Deliverables', 'priority' => 'High', 'description' => 'Prepare and deliver final project deliverables.'],
                ['title' => 'Documentation Completion', 'priority' => 'Medium', 'description' => 'Complete all project documentation.'],
                ['title' => 'Stakeholder Sign-off', 'priority' => 'High', 'description' => 'Obtain formal project acceptance.'],
                ['title' => 'Project Retrospective', 'priority' => 'Low', 'description' => 'Conduct project lessons learned session.']
            ]
        ];

        return $taskMap[$milestoneTitle] ?? [
            ['title' => 'Task Planning', 'priority' => 'Medium', 'description' => 'Plan and organize task activities.'],
            ['title' => 'Implementation', 'priority' => 'High', 'description' => 'Execute main task implementation.'],
            ['title' => 'Review & Testing', 'priority' => 'Medium', 'description' => 'Review and test completed work.'],
            ['title' => 'Documentation', 'priority' => 'Low', 'description' => 'Document task outcomes and learnings.']
        ];
    }

    private function getTaskStage($stages, $milestone, $taskStart)
    {
        $today = now();

        if ($milestone->status === 'Complete') {
            return $stages->where('complete', true)->first() ?? $stages->last();
        }

        if ($milestone->status === 'Ongoing') {
            if ($taskStart->lt($today)) {
                $rand = rand(1, 100);
                if ($rand <= 40) return $stages->skip(1)->first() ?? $stages->first(); // In Progress
                if ($rand <= 70) return $stages->skip(2)->first() ?? $stages->skip(1)->first(); // Review
                return $stages->where('complete', true)->first() ?? $stages->last(); // Done
            }
        }

        return $stages->first(); // Todo
    }

    private function createSubtasks($task, $milestoneTitle, $taskStart, $userId)
    {
        $subtaskTemplates = [
            'planning' => ['Requirements analysis', 'Resource allocation', 'Timeline planning'],
            'design' => ['Design mockups', 'Prototype creation', 'Design review'],
            'development' => ['Code implementation', 'Unit testing', 'Code review'],
            'testing' => ['Test execution', 'Bug reporting', 'Regression testing'],
            'deployment' => ['Environment setup', 'Deployment execution', 'Verification']
        ];

        $phase = str_contains(strtolower($milestoneTitle), 'design') ? 'design' :
                (str_contains(strtolower($milestoneTitle), 'development') ? 'development' :
                (str_contains(strtolower($milestoneTitle), 'testing') ? 'testing' :
                (str_contains(strtolower($milestoneTitle), 'deployment') ? 'deployment' : 'planning')));

        $subtasks = $subtaskTemplates[$phase];
        $numSubtasks = rand(2, 3);

        for ($i = 0; $i < $numSubtasks; $i++) {
            \Workdo\Taskly\Models\TaskSubtask::create([
                'task_id' => $task->id,
                'name' => $subtasks[$i % count($subtasks)],
                'due_date' => $taskStart->copy()->addDays(rand(1, 7))->format('Y-m-d'),
                'is_completed' => rand(1, 100) <= 65,
                'user_id' => $userId,
            ]);
        }
    }

    private function createComments($task, $stage, $teamMembers, $taskStart, $userId)
    {
        $comments = [
            'Task assigned and ready to begin.',
            'Making good progress on this task.',
            'Need clarification on requirements.',
            'Implementation completed, ready for review.',
            'Testing completed successfully.',
            'Task completed as per requirements.'
        ];

        $numComments = rand(1, 2);
        for ($i = 0; $i < $numComments; $i++) {
            \Workdo\Taskly\Models\TaskComment::create([
                'task_id' => $task->id,
                'comment' => $comments[array_rand($comments)],
                'user_id' => collect($teamMembers)->random(),
                'created_at' => $taskStart->copy()->addDays(rand(1, 5)),
            ]);
        }
    }
}
