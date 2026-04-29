<?php

namespace Workdo\Taskly\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Taskly\Models\ActivityLog;
use Workdo\Taskly\Models\Project;
use Workdo\Taskly\Models\ProjectTask;
use Workdo\Taskly\Models\ProjectBug;
use App\Models\User;
use Workdo\Taskly\Models\ProjectMilestone;

class DemoActivityLogSeeder extends Seeder
{
    public function run($userId): void
    {
        if (ActivityLog::where('user_id', $userId)->exists()) {
            return;
        }
        
        if (!empty($userId)) {
            $projects = Project::where('created_by', $userId)->get();
            $users = User::where('created_by', $userId)->emp()->get();
            $clients = User::where('created_by', $userId)->where('type', 'client')->get();
            
            if ($projects->isEmpty() || $users->isEmpty()) return;

            foreach ($projects as $project) {
                $this->createProjectLifecycleActivities($project, $users, $clients, $userId);
            }
        }
    }

    private function createProjectLifecycleActivities($project, $users, $clients, $userId)
    {
        $activities = [];
        $projectStart = $project->start_date;
        $projectEnd = $project->end_date;
        $daysSinceStart = $projectStart->diffInDays(now(), false);
        
        // 1. Project Setup Phase - Team Invitations (Day 0-2)
        foreach ($users->take(rand(1, 2)) as $index => $user) {
            $activities[] = [
                'user_id' => $userId,
                'user_type' => 'User',
                'project_id' => $project->id,
                'log_type' => 'Invite User',
                'remark' => json_encode(['user_id' => $user->id]),
                'created_at' => $projectStart->copy()->addDays($index),
            ];
        }
        
        // Client Sharing (Day 1)
        if ($clients->isNotEmpty()) {
            $activities[] = [
                'user_id' => $userId,
                'user_type' => 'User',
                'project_id' => $project->id,
                'log_type' => 'Share with Client',
                'remark' => json_encode(['client_id' => $clients->random()->id]),
                'created_at' => $projectStart->copy()->addDays(1),
            ];
        }
        
        // 2. Initial Documentation (Day 2-5)
        $fileTypes = ['requirements.pdf', 'design_mockups.zip', 'technical_specs.docx', 'project_charter.pdf'];
        foreach (collect($fileTypes)->random(rand(1, 2)) as $index => $file) {
            $activities[] = [
                'user_id' => $users->random()->id,
                'user_type' => 'User',
                'project_id' => $project->id,
                'log_type' => 'Upload File',
                'remark' => json_encode(['file_name' => $file]),
                'created_at' => $projectStart->copy()->addDays(2 + $index),
            ];
        }
        
        // 3. Milestone Creation (Day 3-10) - Max 3
        $milestones = ProjectMilestone::where('project_id', $project->id)->take(3)->get();
        foreach ($milestones as $index => $milestone) {
            $activities[] = [
                'user_id' => $userId,
                'user_type' => 'User',
                'project_id' => $project->id,
                'log_type' => 'Create Milestone',
                'remark' => json_encode(['title' => $milestone->title]),
                'created_at' => $projectStart->copy()->addDays(3 + $index * 2),
            ];
        }
        
        // 4. Task Creation and Progress (Day 5 onwards) - Max 3
        $tasks = ProjectTask::where('project_id', $project->id)->take(3)->get();
        foreach ($tasks as $index => $task) {
            $taskCreateDate = $projectStart->copy()->addDays(5 + $index);
            
            // Task Creation
            $activities[] = [
                'user_id' => $users->random()->id,
                'user_type' => 'User',
                'project_id' => $project->id,
                'log_type' => 'Create Task',
                'remark' => json_encode(['title' => $task->title]),
                'created_at' => $taskCreateDate,
            ];
            
            // Task Movements based on project status
            if ($project->status === 'Finished') {
                // Completed tasks progression
                $activities[] = [
                    'user_id' => $users->random()->id,
                    'user_type' => 'User',
                    'project_id' => $project->id,
                    'log_type' => 'Move',
                    'remark' => json_encode([
                        'title' => $task->title,
                        'old_status' => 'todo',
                        'new_status' => 'in progress'
                    ]),
                    'created_at' => $taskCreateDate->copy()->addDays(rand(1, 3)),
                ];
                
                $activities[] = [
                    'user_id' => $users->random()->id,
                    'user_type' => 'User',
                    'project_id' => $project->id,
                    'log_type' => 'Move',
                    'remark' => json_encode([
                        'title' => $task->title,
                        'old_status' => 'in progress',
                        'new_status' => 'done'
                    ]),
                    'created_at' => $taskCreateDate->copy()->addDays(rand(4, 8)),
                ];
            } elseif ($project->status === 'Ongoing') {
                // Ongoing tasks - some in progress
                if (rand(1, 100) <= 70) {
                    $activities[] = [
                        'user_id' => $users->random()->id,
                        'user_type' => 'User',
                        'project_id' => $project->id,
                        'log_type' => 'Move',
                        'remark' => json_encode([
                            'title' => $task->title,
                            'old_status' => 'todo',
                            'new_status' => 'in progress'
                        ]),
                        'created_at' => $taskCreateDate->copy()->addDays(rand(1, 5)),
                    ];
                }
            }
        }
        
        // 5. Bug Reports (Mid-project onwards) - Max 3
        if (in_array($project->status, ['Ongoing', 'Finished'])) {
            $bugs = ProjectBug::where('project_id', $project->id)->take(3)->get();
            foreach ($bugs as $index => $bug) {
                $bugCreateDate = $projectStart->copy()->addDays(15 + $index * 3);
                
                $activities[] = [
                    'user_id' => $users->random()->id,
                    'user_type' => 'User',
                    'project_id' => $project->id,
                    'log_type' => 'Create Bug',
                    'remark' => json_encode(['title' => $bug->title]),
                    'created_at' => $bugCreateDate,
                ];
                
                // Bug resolution for finished projects
                if ($project->status === 'Finished') {
                    $activities[] = [
                        'user_id' => $users->random()->id,
                        'user_type' => 'User',
                        'project_id' => $project->id,
                        'log_type' => 'Move Bug',
                        'remark' => json_encode([
                            'title' => $bug->title,
                            'old_status' => 'new',
                            'new_status' => 'resolved'
                        ]),
                        'created_at' => $bugCreateDate->copy()->addDays(rand(2, 7)),
                    ];
                }
            }
        }
        
        // 6. Regular Timesheet Entries (Throughout active period)
        if (in_array($project->status, ['Ongoing', 'Finished'])) {
            $activeDays = $project->status === 'Finished' ? 
                $projectStart->diffInDays($projectEnd) : 
                $projectStart->diffInDays(now());
                
            $timesheetCount = min(intval($activeDays / 5), 3); // Max 3 timesheets
            
            for ($i = 0; $i < $timesheetCount; $i++) {
                $activities[] = [
                    'user_id' => $users->random()->id,
                    'user_type' => 'User',
                    'project_id' => $project->id,
                    'log_type' => 'Create Timesheet',
                    'remark' => json_encode(['hours' => rand(6, 8)]),
                    'created_at' => $projectStart->copy()->addDays($i * 5 + rand(1, 3)),
                ];
            }
        }
        
        // Sort activities chronologically and create them
        collect($activities)
            ->sortBy('created_at')
            ->filter(function ($activity) {
                return $activity['created_at']->lte(now()); // Only past activities
            })
            ->each(function ($activity) {
                ActivityLog::create($activity);
            });
    }
}