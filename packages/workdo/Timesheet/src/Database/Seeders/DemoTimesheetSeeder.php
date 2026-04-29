<?php

namespace Workdo\Timesheet\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Taskly\Models\Project;
use Workdo\Taskly\Models\ProjectTask;
use Workdo\Timesheet\Models\Timesheet;
use App\Models\User;
use Carbon\Carbon;

class DemoTimesheetSeeder extends Seeder
{
    public function run($userId): void
    {
        if (empty($userId)) {
            return;
        }

        $users = User::where('created_by', $userId)
                    ->whereHas('roles', fn($q) => $q->where('name', 'staff'))
                    ->pluck('id')
                    ->toArray();
        if (empty($users)) {
            return;
        }

        $projects     = [];
        $projectTasks = [];

        if (\Schema::hasTable('projects')) {
            try {
                if (module_is_active('Taskly', $userId)) {
                    $projectsCollection = Project::where('created_by', $userId)->get();
                    $projects           = $projectsCollection->pluck('id')->toArray();

                    if (class_exists('\Workdo\Taskly\Models\ProjectTask') && \Schema::hasTable('project_tasks') && !empty($projects)) {
                        $tasksCollection = ProjectTask::whereIn('project_id', $projects)->get();
                        foreach ($tasksCollection as $task) {
                            $projectTasks[$task->project_id][] = $task->id;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Module not available
            }
        }

        // 10 project entries
        if (!empty($projects)) {
            for ($i = 0; $i < 10; $i++) {
                $selectedProject = $projects[array_rand($projects)];
                $selectedTask = isset($projectTasks[$selectedProject]) ? $projectTasks[$selectedProject][array_rand($projectTasks[$selectedProject])] : null;
                $user = $users[array_rand($users)];
                $date = Carbon::now()->subDays(rand(1, 60));
                if ($date->isWeekend()) { $i--; continue; }

                $exists = Timesheet::where('user_id', $user)->where('date', $date->format('Y-m-d'))->exists();
                if ($exists) { $i--; continue; }

                // Get task description for notes
                $taskNotes = 'Project work';
                if ($selectedTask) {
                    try {
                        $task = ProjectTask::find($selectedTask);
                        $taskNotes = $task ? $task->description : 'Project task work';
                    } catch (\Exception $e) {
                        $taskNotes = 'Project task work';
                    }
                }

                Timesheet::create([
                    'type' => 'project',
                    'project_id' => $selectedProject,
                    'task_id' => $selectedTask,
                    'hours' => rand(4, 8),
                    'minutes' => [0, 15, 30, 45][array_rand([0, 15, 30, 45])],
                    'notes' => $taskNotes,
                    'user_id' => $user,
                    'date' => $date->format('Y-m-d'),
                    'creator_id' => $user,
                    'created_by' => $userId,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }
        }

        // 10 manual entries
        for ($i = 0; $i < 10; $i++) {
            $user = $users[array_rand($users)];
            $date = Carbon::now()->subDays(rand(1, 60));
            if ($date->isWeekend()) { $i--; continue; }

            $exists = Timesheet::where('user_id', $user)->where('date', $date->format('Y-m-d'))->exists();
            if ($exists) { $i--; continue; }

            $manualNotes = [
                'Meeting with client',
                'Documentation work',
                'Training session',
                'Administrative tasks',
                'Research and analysis',
                'Team collaboration',
                'Planning activities'
            ];

            Timesheet::create([
                'type' => 'manual',
                'hours' => rand(3, 8),
                'minutes' => [0, 15, 30, 45][array_rand([0, 15, 30, 45])],
                'notes' => $manualNotes[array_rand($manualNotes)],
                'user_id' => $user,
                'date' => $date->format('Y-m-d'),
                'creator_id' => $user,
                'created_by' => $userId,
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }

        // 10 HRM entries if module active
        if (module_is_active('Hrm', $userId)) {
            for ($i = 0; $i < 10; $i++) {
                $user = $users[array_rand($users)];
                $date = Carbon::now()->subDays(rand(1, 60));
                if ($date->isWeekend()) { $i--; continue; }

                $exists = Timesheet::where('user_id', $user)->where('date', $date->format('Y-m-d'))->exists();
                if ($exists) { $i--; continue; }

                $clockNotes = [
                    'Clock in/out attendance',
                    'Regular office hours',
                    'Full day attendance',
                    'Standard work hours'
                ];

                Timesheet::create([
                    'type' => 'clock_in_out',
                    'hours' => rand(7, 9),
                    'minutes' => [0, 15, 30, 45][array_rand([0, 15, 30, 45])],
                    'notes' => $clockNotes[array_rand($clockNotes)],
                    'user_id' => $user,
                    'date' => $date->format('Y-m-d'),
                    'creator_id' => $user,
                    'created_by' => $userId,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }
        }
    }
}
