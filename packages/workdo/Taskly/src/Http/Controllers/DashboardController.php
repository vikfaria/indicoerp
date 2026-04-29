<?php

namespace Workdo\Taskly\Http\Controllers;

use App\Models\User;
use App\Models\Warehouse;
use App\Models\Order;
use App\Models\Plan;
use App\Models\DemoItem;
use App\Models\DemoType;
use App\Models\HelpdeskTicket;
use App\Models\Transfer;
use App\Models\LoginHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Workdo\Taskly\Models\Project;
use Workdo\Taskly\Models\ProjectBug;
use Workdo\Taskly\Models\ProjectTask;

class DashboardController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-project-dashboard')) {
            $user = Auth::user();
            $userType = $user->type;

            // Route to appropriate dashboard based on user type
            switch ($userType) {
                case 'company':
                    return $this->companyDashboard();
                case 'client':
                    return $this->clientDashboard();
                case 'staff':
                default:
                    return $this->staffDashboard();
            }
        }
    }

    private function companyDashboard()
    {
        $user = Auth::user();
        $creatorId = creatorId();

        // Company-wide Stats
        $totalProjects = Project::where('created_by', $creatorId)->count();
        $totalTasks = ProjectTask::where('created_by', $creatorId)->count();
        $totalBugs = ProjectBug::where('created_by', $creatorId)->count();
        $totalUsers = User::where('created_by', $creatorId)->where('type', 'staff')->count();
        $totalClients = User::where('created_by', $creatorId)->where('type', 'client')->count();
        $completedTasks = ProjectTask::where('created_by', $creatorId)
            ->whereHas('taskStage', function ($query) {
                $query->where('complete', true); })->count();

        // Company Recent Tasks with enhanced data
        $recentTasks = ProjectTask::with(['project', 'taskStage'])
            ->where('created_by', $creatorId)
            ->latest()
            ->limit(8)
            ->get()
            ->map(function ($task) {
                // Handle JSON assigned_to
                $assigneeNames = [];
                if ($task->assigned_to) {
                    $assigneeIds = is_array($task->assigned_to) ? $task->assigned_to : json_decode($task->assigned_to, true);
                    if ($assigneeIds) {
                        $assignees = User::whereIn('id', $assigneeIds)->get();
                        $assigneeNames = $assignees->pluck('name')->toArray();
                    }
                }

                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'priority' => $task->priority ?? 'Medium',
                    'project' => $task->project->name ?? 'No Project',
                    'stage' => $task->taskStage->name ?? 'No Stage',
                    'stage_color' => $task->taskStage->color ?? null,
                    'assignee' => !empty($assigneeNames) ? implode(', ', $assigneeNames) : 'Unassigned',
                    'created_at' => $task->created_at->format('M d, Y'),
                    'is_completed' => $task->taskStage ? $task->taskStage->complete : false
                ];
            });

        $isDemo = config('app.is_demo');
        // Company Project Status Distribution
        $projectStatus = [
            ['name' => 'Ongoing', 'value' => Project::where('created_by', $creatorId)->where('status', 'Ongoing')->count(), 'color' => '#3b82f6'],
            ['name' => 'Finished', 'value' => Project::where('created_by', $creatorId)->where('status', 'Finished')->count(), 'color' => '#10b77f'],
            ['name' => 'On Hold', 'value' => Project::where('created_by', $creatorId)->where('status', 'Onhold')->count(), 'color' => '#f59e0b']
        ];

        // Company Task Priority Distribution
        $taskPriority = [
            ['name' => 'High', 'value' => ProjectTask::where('created_by', $creatorId)->where('priority', 'High')->count(), 'color' => '#ef4444'],
            ['name' => 'Medium', 'value' => ProjectTask::where('created_by', $creatorId)->where('priority', 'Medium')->count(), 'color' => '#f59e0b'],
            ['name' => 'Low', 'value' => ProjectTask::where('created_by', $creatorId)->where('priority', 'Low')->count(), 'color' => '#10b77f']
        ];

        // Company Team Performance
        $teamPerformance = User::where('created_by', $creatorId)
            ->get()
            ->map(function ($user) use ($creatorId) {
                $totalTasks = ProjectTask::where('created_by', $creatorId)
                    ->where(function ($query) use ($user) {
                        $query->whereJsonContains('assigned_to', (string) $user->id)
                            ->orWhere('assigned_to', 'like', '%' . $user->id . '%');
                    })
                    ->count();

                $completedTasks = ProjectTask::where('created_by', $creatorId)
                    ->where(function ($query) use ($user) {
                        $query->whereJsonContains('assigned_to', (string) $user->id)
                            ->orWhere('assigned_to', 'like', '%' . $user->id . '%');
                    })
                    ->whereHas('taskStage', function ($q) {
                        $q->where('complete', true);
                    })
                    ->count();

                return [
                    'name' => $user->name,
                    'total_tasks' => $totalTasks,
                    'completed_tasks' => $completedTasks,
                    'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0
                ];
            })
            ->filter(function ($user) {
                return $user['total_tasks'] > 0;
            })
            ->take(6)
            ->values();

        // Company Monthly Progress (last 6 months)
        $monthlyProgress = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $date->format('M');

            if ($isDemo) {
                $tasksCreated = rand(20, 50);
                $tasksCompleted = rand(15, 45);
            } else {
                $tasksCreated = ProjectTask::where('created_by', $creatorId)
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->count();

                $tasksCompleted = ProjectTask::where('created_by', $creatorId)
                    ->whereMonth('updated_at', $date->month)
                    ->whereYear('updated_at', $date->year)
                    ->whereHas('taskStage', function ($query) {
                        $query->where('complete', true);
                    })
                    ->count();
            }

            $monthlyProgress[] = [
                'month' => $monthName,
                'created' => $tasksCreated,
                'completed' => $tasksCompleted
            ];
        }

        // Company Project Health (overdue projects)
        $overdueProjects = Project::where('created_by', $creatorId)
            ->where('end_date', '<', Carbon::now())
            ->where('status', '!=', 'Finished')
            ->count();

        // Company Bug Statistics
        $totalBugsCount = ProjectBug::where('created_by', $creatorId)->count();
        $resolvedBugsCount = ProjectBug::where('created_by', $creatorId)
            ->whereHas('bugStage', function ($query) {
                $query->where('complete', true);
            })->count();

        $bugStats = [
            'open' => $totalBugsCount - $resolvedBugsCount,
            'resolved' => $resolvedBugsCount
        ];

        return Inertia::render('Taskly/Dashboard/CompanyDashboard', [
            'stats' => [
                'total_projects' => $totalProjects,
                'total_tasks' => $totalTasks,
                'total_bugs' => $totalBugs,
                'total_users' => $totalUsers,
                'total_clients' => $totalClients,
                'completed_tasks' => $completedTasks,
                'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0,
                'overdue_projects' => $overdueProjects
            ],
            'recentTasks' => $recentTasks,
            'projectStatus' => $projectStatus,
            'taskPriority' => $taskPriority,
            'teamPerformance' => $teamPerformance,
            'monthlyProgress' => $monthlyProgress,
            'bugStats' => $bugStats
        ]);
    }

    private function clientDashboard()
    {
        $user = Auth::user();
        $creatorId = $user->created_by;

        // Client-specific projects
        $clientProjects = Project::where('created_by', $creatorId)
            ->whereHas('clients', function ($query) use ($user) {
                $query->where('client_id', $user->id);
            })
            ->with(['clients'])
            ->get();

        // Client-specific tasks (from client's projects)
        $clientProjectIds = $clientProjects->pluck('id');
        $clientTasks = ProjectTask::where('created_by', $creatorId)
            ->whereIn('project_id', $clientProjectIds)
            ->with(['project', 'taskStage'])
            ->latest()
            ->get();

        $completedTasks = $clientTasks->filter(function ($task) {
            return $task->taskStage && $task->taskStage->complete;
        })->count();

        // Recent tasks for client (from client's projects)
        $recentTasks = $clientTasks->take(6)->map(function ($task) {
            // Get assignee names
            $assigneeNames = [];
            if ($task->assigned_to) {
                $assigneeIds = is_array($task->assigned_to) ? $task->assigned_to : json_decode($task->assigned_to, true);
                if ($assigneeIds) {
                    $assignees = User::whereIn('id', $assigneeIds)->get();
                    $assigneeNames = $assignees->pluck('name')->toArray();
                }
            }

            return [
                'id' => $task->id,
                'title' => $task->title,
                'priority' => $task->priority ?? 'Medium',
                'project' => $task->project->name ?? 'No Project',
                'stage' => $task->taskStage->name ?? 'No Stage',
                'stage_color' => $task->taskStage->color ?? null,
                'assignee' => !empty($assigneeNames) ? implode(', ', $assigneeNames) : 'Unassigned',
                'created_at' => $task->created_at->format('M d, Y'),
                'is_completed' => $task->taskStage ? $task->taskStage->complete : false
            ];
        });

        // Project progress for client
        $projectProgress = $clientProjects->map(function ($project) {
            $totalTasks = $project->tasks->count();
            $completedTasks = $project->tasks->filter(function ($task) {
                return $task->taskStage && $task->taskStage->complete;
            })->count();

            return [
                'name' => $project->name,
                'progress' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'status' => $project->status
            ];
        });

        return Inertia::render('Taskly/Dashboard/ClientDashboard', [
            'stats' => [
                'total_projects' => $clientProjects->count(),
                'total_tasks' => $clientTasks->count(),
                'completed_tasks' => $completedTasks,
                'completion_rate' => $clientTasks->count() > 0 ? round(($completedTasks / $clientTasks->count()) * 100) : 0,
                'pending_tasks' => $clientTasks->count() - $completedTasks
            ],
            'recentTasks' => $recentTasks,
            'projectProgress' => $projectProgress,
            'clientProjects' => $clientProjects->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'status' => $project->status,
                    'start_date' => $project->start_date,
                    'end_date' => $project->end_date
                ];
            })
        ]);
    }

    private function staffDashboard()
    {
        $user = Auth::user();
        $creatorId = $user->created_by;

        // Staff personal tasks
        $personalTasks = ProjectTask::where('created_by', $creatorId)
            ->where(function ($query) use ($user) {
                $query->whereJsonContains('assigned_to', (string) $user->id)
                    ->orWhere('assigned_to', 'like', '%' . $user->id . '%');
            })
            ->with(['project', 'taskStage'])
            ->get();

        $completedTasks = $personalTasks->filter(function ($task) {
            return $task->taskStage && $task->taskStage->complete;
        })->count();

        $pendingTasks = $personalTasks->count() - $completedTasks;
        $overdueTasks = $personalTasks->filter(function ($task) {
            return $task->due_date && $task->due_date < now() && (!$task->taskStage || !$task->taskStage->complete);
        })->count();



        // Task priority breakdown
        $taskPriority = [
            ['name' => 'High', 'value' => $personalTasks->where('priority', 'High')->count(), 'color' => '#ef4444'],
            ['name' => 'Medium', 'value' => $personalTasks->where('priority', 'Medium')->count(), 'color' => '#f59e0b'],
            ['name' => 'Low', 'value' => $personalTasks->where('priority', 'Low')->count(), 'color' => '#10b77f']
        ];

        // Projects staff is involved in
        $staffProjects = Project::where('created_by', $creatorId)
            ->whereHas('tasks', function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->whereJsonContains('assigned_to', (string) $user->id)
                        ->orWhere('assigned_to', 'like', '%' . $user->id . '%');
                });
            })
            ->with(['tasks'])
            ->get()
            ->map(function ($project) use ($user) {
                $projectTasks = $project->tasks->filter(function ($task) use ($user) {
                    $assignedIds = is_array($task->assigned_to) ? $task->assigned_to : json_decode($task->assigned_to, true);
                    return $assignedIds && in_array((string) $user->id, $assignedIds);
                });
                $completedProjectTasks = $projectTasks->filter(function ($task) {
                    return $task->taskStage && $task->taskStage->complete;
                })->count();

                return [
                    'name' => $project->name,
                    'total_tasks' => $projectTasks->count(),
                    'completed_tasks' => $completedProjectTasks,
                    'progress' => $projectTasks->count() > 0 ? round(($completedProjectTasks / $projectTasks->count()) * 100) : 0,
                    'status' => $project->status
                ];
            });

        // Latest 6 assigned tasks
        $latestTasks = $personalTasks->take(6)->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'priority' => $task->priority ?? 'Medium',
                'project' => $task->project->name ?? 'No Project',
                'stage' => $task->taskStage->name ?? 'No Stage',
                'stage_color' => $task->taskStage->color ?? null,
                'is_completed' => $task->taskStage ? $task->taskStage->complete : false
            ];
        });
        return Inertia::render('Taskly/Dashboard/StaffDashboard', [
            'stats' => [
                'total_tasks' => $personalTasks->count() > 0 ? $personalTasks->count() : 0,
                'completed_tasks' => $completedTasks,
                'pending_tasks' => $pendingTasks > 0 ? $pendingTasks : 0,
                'overdue_tasks' => $overdueTasks,
                'completion_rate' => $personalTasks->count() > 0 ? round(($completedTasks / $personalTasks->count()) * 100) : 0
            ],
            'todayTasks' => [],
            'latestTasks' => $latestTasks,
            'taskPriority' => $taskPriority,
            'staffProjects' => $staffProjects
        ]);
    }
}
