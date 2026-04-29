<?php

namespace Workdo\Taskly\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Workdo\Taskly\Models\Project;
use Workdo\Taskly\Models\ProjectMilestone;

class DemoProjectMilestoneSeeder extends Seeder
{
    public function run($userId): void
    {
        if (ProjectMilestone::where('project_id', Project::where('created_by', $userId)->first()?->id)->exists()) {
            return;
        }
        
        if (!empty($userId)) {
            $projects = Project::where('created_by', $userId)->get();
            if ($projects->isEmpty()) {
                return;
            }

            $milestones = [
                ['title' => 'Project Initiation & Planning', 'summary' => 'Project charter creation, stakeholder identification, and initial planning phase completion.', 'cost' => 8500],
                ['title' => 'Requirements Gathering', 'summary' => 'Detailed business requirements analysis, user story creation, and acceptance criteria definition.', 'cost' => 12000],
                ['title' => 'System Architecture Design', 'summary' => 'Technical architecture planning, database design, and system integration specifications.', 'cost' => 15000],
                ['title' => 'UI/UX Design & Prototyping', 'summary' => 'User interface mockups, wireframes, prototypes, and design system creation.', 'cost' => 18000],
                ['title' => 'Backend Development Phase 1', 'summary' => 'Core API development, database implementation, and authentication system setup.', 'cost' => 25000],
                ['title' => 'Frontend Development Phase 1', 'summary' => 'User interface implementation, component development, and responsive design integration.', 'cost' => 22000],
                ['title' => 'Integration & API Development', 'summary' => 'Third-party service integration, API endpoint creation, and data synchronization setup.', 'cost' => 16000],
                ['title' => 'Security Implementation', 'summary' => 'Security audit, vulnerability assessment, encryption implementation, and access control setup.', 'cost' => 14000],
                ['title' => 'Performance Optimization', 'summary' => 'Code optimization, database tuning, caching implementation, and load testing.', 'cost' => 11000],
                ['title' => 'Quality Assurance Testing', 'summary' => 'Comprehensive testing including unit tests, integration tests, and user acceptance testing.', 'cost' => 13000],
                ['title' => 'User Training & Documentation', 'summary' => 'User manual creation, training material development, and knowledge transfer sessions.', 'cost' => 9000],
                ['title' => 'Production Deployment', 'summary' => 'Production environment setup, deployment pipeline configuration, and go-live activities.', 'cost' => 8000],
                ['title' => 'Post-Launch Monitoring', 'summary' => 'System monitoring setup, performance tracking, and initial bug fixes after launch.', 'cost' => 7500],
                ['title' => 'Project Closure & Handover', 'summary' => 'Final deliverables handover, project documentation completion, and stakeholder sign-off.', 'cost' => 5000],
            ];

            foreach ($projects as $project) {
                // Pick 3–5 random milestones
                $projectMilestones = collect($milestones)->random(rand(3, 5))->values();

                $projectStart = Carbon::parse($project->start_date);
                $projectEnd   = Carbon::parse($project->end_date);

                $totalDuration = $projectStart->diffInDays($projectEnd);
                $chunk = max(5, floor($totalDuration / ($projectMilestones->count() + 1))); // evenly spaced

                $currentStart = clone $projectStart;

                foreach ($projectMilestones as $index => $milestoneData) {
                    $start = (clone $currentStart)->addDays($chunk * $index);
                    $end   = (clone $start)->addDays(rand(5, 12));

                    // Make sure end doesn’t exceed project end date
                    if ($end->gt($projectEnd)) {
                        $end = clone $projectEnd;
                    }

                    $today = now();
                    if ($today->lt($start)) {
                        // Milestone hasn't started yet
                        $status = 'Incomplete';
                        $progress = 0;

                    } elseif ($today->between($start, $end)) {
                        // Milestone is ongoing
                        $status = 'Ongoing';
                        $progress = rand(20, 80);

                    } else {
                        // $today > $end → milestone should be finished, but not always!
                        if (rand(1, 100) <= 25) {
                            // 25% chance it's still incomplete even though deadline passed
                            $status = 'Incomplete';
                            $progress = rand(1, 99);
                        } else {
                            // Majority case → completed
                            $status = 'Complete';
                            $progress = 100;
                        }
                    }

                    ProjectMilestone::create([
                        'project_id' => $project->id,
                        'title'      => $milestoneData['title'],
                        'summary'    => $milestoneData['summary'],
                        'cost'       => $milestoneData['cost'],
                        'status'     => $status,
                        'progress'   => $progress,
                        'start_date' => $start,
                        'end_date'   => $end,
                    ]);
                }
            }
        }
    }
}
