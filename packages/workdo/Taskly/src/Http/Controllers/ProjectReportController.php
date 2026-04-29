<?php

namespace Workdo\Taskly\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Taskly\Models\Project;
use Workdo\Taskly\Models\ProjectTask;
use Workdo\Taskly\Models\ProjectBug;
use Workdo\Taskly\Models\ProjectMilestone;

class ProjectReportController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::query()
            ->with('teamMembers')
            ->select('id', 'name', 'start_date', 'end_date', 'status')
            ->where(function($q) {
                if(Auth::user()->can('manage-any-project')) {
                     $q->where('created_by', creatorId());
                } else if(Auth::user()->can('manage-own-project')) {
                    $q->where(function($subQ) {
                        $subQ->where('creator_id', Auth::id());
                        if (Auth::user()->type === 'client') {
                            $subQ->orWhereHas('clients', function($clientQ) {
                                $clientQ->where('client_id', Auth::id());
                            });
                        } else {
                            $subQ->orWhereHas('teamMembers', function($teamQ) {
                                $teamQ->where('user_id', Auth::id());
                            });
                        }
                    });
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->when($request->get('name'), fn($q) => $q->where('name', 'like', '%' . $request->get('name') . '%'))
            ->when($request->get('status'), fn($q) => $q->where('status', $request->get('status')))
            ->when($request->get('date'), fn($q) => $q->where('start_date', '<=', $request->get('date'))->where('end_date', '>=', $request->get('date')))
            ->when($request->get('sort'), fn($q) => $q->orderBy($request->get('sort'), $request->get('direction', 'asc')), fn($q) => $q->latest())
            ->paginate($request->get('per_page', 10))
            ->withQueryString();

        return Inertia::render('Taskly/Report/Index', [
            'projects' => $projects->through(fn($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'start_date' => $project->start_date?->format('Y-m-d'),
                'end_date' => $project->end_date?->format('Y-m-d'),
                'status' => $project->status,
                'tasks_count' => $this->getTasksCount($project->id),
                'bugs_count' => $this->getBugsCount($project->id),
                'milestones_count' => $this->getMilestonesCount($project->id)
            ])
        ]);
    }

    private function getTasksCount($projectId)
    {
        $totalTasks = ProjectTask::where('project_id', $projectId)->count();
        $completedTasks = ProjectTask::where('project_id', $projectId)
            ->whereHas('taskStage', function($q) {
                $q->where('complete', 1);
            })->count();

        return $totalTasks > 0 ? "{$completedTasks}/{$totalTasks}" : '0/0';
    }

    private function getBugsCount($projectId)
    {
        $totalBugs = ProjectBug::where('project_id', $projectId)->count();
        $completedBugs = ProjectBug::where('project_id', $projectId)
            ->whereHas('bugStage', function($q) {
                $q->where('complete', 1);
            })->count();

        return $totalBugs > 0 ? "{$completedBugs}/{$totalBugs}" : '0/0';
    }

    private function getMilestonesCount($projectId)
    {
        $totalMilestones = ProjectMilestone::where('project_id', $projectId)->count();
        $completedMilestones = ProjectMilestone::where('project_id', $projectId)
            ->where('status', 'Complete')->count();

        return $totalMilestones > 0 ? "{$completedMilestones}/{$totalMilestones}" : '0/0';
    }

    public function show($id)
    {
        if (Auth::user()->can('view-project-report')) {
            $project = Project::where(function($q) {
                    if(Auth::user()->can('manage-any-project')) {
                         $q->where('created_by', creatorId());
                    } else if(Auth::user()->can('manage-own-project')) {
                        $q->where(function($subQ) {
                            $subQ->where('creator_id', Auth::id());
                            if (Auth::user()->type === 'client') {
                                $subQ->orWhereHas('clients', function($clientQ) {
                                    $clientQ->where('client_id', Auth::id());
                                });
                            } else {
                                $subQ->orWhereHas('teamMembers', function($teamQ) {
                                    $teamQ->where('user_id', Auth::id());
                                });
                            }
                        });
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })->with([
                'tasks.taskStage',
                'teamMembers',
                'milestones'
            ])->findOrFail($id);

            // Task Status Data for Pie Chart
            $taskStatusData = $project->tasks
                ->groupBy('taskStage.name')
                ->map(function ($tasks, $stageName) {
                    return [
                        'name' => $stageName ?: 'No Stage',
                        'value' => $tasks->count(),
                        'color' => $tasks->first()->taskStage->color ?? '#6b7280'
                    ];
                })
                ->values()
                ->toArray();

            // Task Priority Data for Bar Chart
            $taskPriorityData = $project->tasks
                ->groupBy('priority')
                ->map(function ($tasks, $priority) {
                    return [
                        'name' => $priority ?: 'No Priority',
                        'value' => $tasks->count()
                    ];
                })
                ->values()
                ->toArray();

            // Project Summary Stats
            $projectStats = [
                'total_tasks' => $project->tasks->count(),
                'completed_tasks' => $project->tasks->filter(function($task) {
                    return $task->taskStage && $task->taskStage->complete;
                })->count(),
                'in_progress_tasks' => $project->tasks->filter(function($task) {
                    return $task->taskStage && !$task->taskStage->complete;
                })->count(),
                'team_members' => $project->teamMembers->count()
            ];

            // Users Data with Task Counts
            $usersData = $project->teamMembers->map(function ($user) use ($project) {
                $assignedTasks = $project->tasks->filter(function($task) use ($user) {
                    $assignedTo = is_array($task->assigned_to) ? $task->assigned_to : json_decode($task->assigned_to, true);
                    return $assignedTo && in_array($user->id, $assignedTo);
                })->count();
                
                $doneTasks = $project->tasks->filter(function($task) use ($user) {
                    $assignedTo = is_array($task->assigned_to) ? $task->assigned_to : json_decode($task->assigned_to, true);
                    return $assignedTo && in_array($user->id, $assignedTo) && $task->taskStage && $task->taskStage->complete;
                })->count();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'assigned_tasks' => $assignedTasks,
                    'done_tasks' => $doneTasks
                ];
            });

            // Milestones Data
            $milestonesData = $project->milestones->map(function ($milestone) {
                return [
                    'id' => $milestone->id,
                    'name' => $milestone->title,
                    'progress' => $milestone->progress ?? 0,
                    'cost' => $milestone->cost ?? 0,
                    'status' => $milestone->status,
                    'start_date' => $milestone->start_date?->format('Y-m-d'),
                    'end_date' => $milestone->end_date?->format('Y-m-d')
                ];
            });

            return Inertia::render('Taskly/Report/View', [
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'start_date' => $project->start_date?->format('Y-m-d'),
                    'end_date' => $project->end_date?->format('Y-m-d'),
                    'status' => $project->status,
                    'budget' => $project->budget
                ],
                'taskStatusData' => $taskStatusData,
                'taskPriorityData' => $taskPriorityData,
                'projectStats' => $projectStats,
                'usersData' => $usersData,
                'milestonesData' => $milestonesData
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }
}
