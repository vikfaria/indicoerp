<?php

namespace Workdo\Taskly\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Carbon\Carbon;
use Workdo\Taskly\Models\Project;
use Workdo\Taskly\Models\ProjectBug;
use Workdo\Taskly\Models\ProjectTask;

class DashboardApiController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        try {
            if (Auth::user()->can('manage-project-dashboard')) {
                $user = Auth::user();
                $userType = $user->type;

                switch ($userType) {
                    case 'company':
                        return $this->companyDashboard();
                    case 'client':
                        return $this->clientDashboard();
                    case 'staff':
                    default:
                        return $this->staffDashboard();
                }
            } else {
                return $this->errorResponse('Permission denied');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    private function companyDashboard()
    {
        $creatorId = creatorId();

        $totalProjects = Project::where('created_by', $creatorId)->count();
        $totalTasks = ProjectTask::where('created_by', $creatorId)->count();
        $totalBugs = ProjectBug::where('created_by', $creatorId)->count();
        $totalUsers = User::where('created_by', $creatorId)->where('type', 'staff')->count();
        $totalClients = User::where('created_by', $creatorId)->where('type', 'client')->count();
        $completedTasks = ProjectTask::where('created_by', $creatorId)
            ->whereHas('taskStage', function ($query) {
                $query->where('complete', true);
            })->count();

        $recentTasks = ProjectTask::with(['project', 'taskStage'])
            ->where('created_by', $creatorId)
            ->latest()
            ->limit(8)
            ->get()
            ->map(function ($task) {
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

        $projectStatus = [
            ['name' => 'Ongoing', 'value' => Project::where('created_by', $creatorId)->where('status', 'Ongoing')->count(), 'color' => '#3b82f6'],
            ['name' => 'Finished', 'value' => Project::where('created_by', $creatorId)->where('status', 'Finished')->count(), 'color' => '#10b77f'],
            ['name' => 'On Hold', 'value' => Project::where('created_by', $creatorId)->where('status', 'Onhold')->count(), 'color' => '#f59e0b']
        ];

        $taskPriority = [
            ['name' => 'High', 'value' => ProjectTask::where('created_by', $creatorId)->where('priority', 'High')->count(), 'color' => '#ef4444'],
            ['name' => 'Medium', 'value' => ProjectTask::where('created_by', $creatorId)->where('priority', 'Medium')->count(), 'color' => '#f59e0b'],
            ['name' => 'Low', 'value' => ProjectTask::where('created_by', $creatorId)->where('priority', 'Low')->count(), 'color' => '#10b77f']
        ];

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

        $monthlyProgress = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $date->format('M');

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

            $monthlyProgress[] = [
                'month' => $monthName,
                'created' => $tasksCreated,
                'completed' => $tasksCompleted
            ];
        }

        $overdueProjects = Project::where('created_by', $creatorId)
            ->where('end_date', '<', Carbon::now())
            ->where('status', '!=', 'Finished')
            ->count();

        $totalBugsCount = ProjectBug::where('created_by', $creatorId)->count();
        $resolvedBugsCount = ProjectBug::where('created_by', $creatorId)
            ->whereHas('bugStage', function ($query) {
                $query->where('complete', true);
            })->count();

        $bugStats = [
            'open' => $totalBugsCount - $resolvedBugsCount,
            'resolved' => $resolvedBugsCount
        ];

        return $this->successResponse([
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
        ], 'Dashboard data retrieved successfully');
    }

    private function clientDashboard()
    {
        $user = Auth::user();
        $creatorId = $user->created_by;

        $clientProjects = Project::where('created_by', $creatorId)
            ->whereHas('clients', function ($query) use ($user) {
                $query->where('client_id', $user->id);
            })
            ->with(['clients'])
            ->get();

        $clientProjectIds = $clientProjects->pluck('id');
        $clientTasks = ProjectTask::where('created_by', $creatorId)
            ->whereIn('project_id', $clientProjectIds)
            ->with(['project', 'taskStage'])
            ->latest()
            ->get();

        $completedTasks = $clientTasks->filter(function ($task) {
            return $task->taskStage && $task->taskStage->complete;
        })->count();

        $recentTasks = $clientTasks->take(6)->map(function ($task) {
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
        $data = [
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
                    'start_date' => $project->start_date->format('Y-m-d'),
                    'end_date' => $project->end_date->format('Y-m-d')
                ];
            })
        ];
        return $this->successResponse($data, 'Dashboard data retrieved successfully');
    }

    private function staffDashboard()
    {
        $user = Auth::user();
        $creatorId = $user->created_by;

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

        $taskPriority = [
            ['name' => 'High', 'value' => $personalTasks->where('priority', 'High')->count(), 'color' => '#ef4444'],
            ['name' => 'Medium', 'value' => $personalTasks->where('priority', 'Medium')->count(), 'color' => '#f59e0b'],
            ['name' => 'Low', 'value' => $personalTasks->where('priority', 'Low')->count(), 'color' => '#10b77f']
        ];

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
        $data = [
            'stats' => [
                'total_tasks' => $personalTasks->count() > 0 ? $personalTasks->count() : 2,
                'completed_tasks' => $completedTasks,
                'pending_tasks' => $pendingTasks > 0 ? $pendingTasks : 2,
                'overdue_tasks' => $overdueTasks,
                'completion_rate' => $personalTasks->count() > 0 ? round(($completedTasks / $personalTasks->count()) * 100) : 0
            ],
            'latestTasks' => $latestTasks,
            'taskPriority' => $taskPriority,
            'staffProjects' => $staffProjects
        ];
        return $this->successResponse($data, 'Dashboard data retrieved successfully');
    }
}
