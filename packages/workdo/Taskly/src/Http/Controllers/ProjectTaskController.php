<?php

namespace Workdo\Taskly\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;
use Workdo\Taskly\Events\CreateProjectTask;
use Workdo\Taskly\Events\CreateTaskComment;
use Workdo\Taskly\Events\DestroyProjectTask;
use Workdo\Taskly\Events\UpdateProjectTask;
use Workdo\Taskly\Models\Project;
use Workdo\Taskly\Models\ProjectTask;
use Workdo\Taskly\Models\ProjectMilestone;
use Workdo\Taskly\Models\TaskStage;
use Workdo\Taskly\Http\Requests\StoreProjectTaskRequest;
use Workdo\Taskly\Http\Requests\UpdateProjectTaskRequest;
use Workdo\Taskly\Models\ActivityLog;
use Workdo\Taskly\Models\TaskComment;
use Workdo\Taskly\Models\TaskSubtask;
use Workdo\Taskly\Events\CreateTaskSubtask;
use Workdo\Taskly\Events\DestroyTaskComment;
use Workdo\Taskly\Events\UpdateProjectTaskStage;

class ProjectTaskController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-project-task')) {
            $projectId = $request->get('project_id');
            $project = null;

            if ($projectId) {
                $project = Project::with(['teamMembers:id,name'])->findOrFail($projectId);
            }

            $query = ProjectTask::select('project_tasks.id', 'project_id', 'milestone_id', 'title', 'priority', 'assigned_to', 'duration', 'description', 'stage_id', 'created_at')
                ->with(['project:id,name', 'milestone:id,title', 'assignedUser:id,name,avatar'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-project-task')) {
                        $q->where('created_by', creatorId());
                    } else if (Auth::user()->can('manage-own-project-task')) {
                        $q->where(function ($subQ) {
                            $subQ->where('creator_id', Auth::id())
                                ->orWhereJsonContains('assigned_to', (string) Auth::id());
                        });
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                });

            $tasks = $query->when($projectId, fn($q) => $q->where('project_id', $projectId))
                ->when(request('title'), fn($q) => $q->where('title', 'like', '%' . request('title') . '%'))
                ->when(request('priority'), fn($q) => $q->where('priority', request('priority')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            // Parse duration and add assignedUsers
            $tasks->getCollection()->transform(function ($task) {
                if ($task->duration && strpos($task->duration, ' - ') !== false) {
                    $dateRange = explode(' - ', $task->duration);
                    $task->start_date = trim($dateRange[0]);
                    $task->end_date = trim($dateRange[1]);
                } else {
                    $task->start_date = null;
                    $task->end_date = null;
                }

                // Add assignedUsers array with proper avatar
                $assignedUsers = [];
                if ($task->assigned_to) {
                    $userIds = is_array($task->assigned_to) ? $task->assigned_to : json_decode($task->assigned_to, true);
                    $assignedUsers = \App\Models\User::whereIn('id', $userIds)
                        ->select('id', 'name', 'avatar')
                        ->get()
                        ->map(function ($user) {
                            return [
                                'id' => $user->id,
                                'name' => $user->name,
                                'avatar' => $user->avatar
                            ];
                        });
                }
                $task->assignedUsers = $assignedUsers;

                return $task;
            });

            $milestones = $project ? $project->milestones()->select('id', 'title')->get() : [];
            $teamMembers = $project ? $project->teamMembers()->select('users.id', 'users.name', 'users.avatar')->get() : [];
            $taskStages = TaskStage::where('created_by', creatorId())->orderBy('order')->get();

            return Inertia::render('Taskly/Tasks/Index', [
                'tasks' => $tasks,
                'project' => $project,
                'milestones' => $milestones,
                'teamMembers' => $teamMembers,
                'taskStages' => $taskStages,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreProjectTaskRequest $request)
    {
        if (Auth::user()->can('create-project-task')) {
            $validated = $request->validated();
            $task = new ProjectTask();
            $task->project_id = $validated['project_id'];
            $task->milestone_id = $validated['milestone_id'];
            $task->title = $validated['title'];
            $task->priority = $validated['priority'];
            $task->assigned_to = is_array($validated['assigned_to']) ? json_encode($validated['assigned_to']) : $validated['assigned_to'];
            $task->duration = $validated['duration'];
            $task->description = $validated['description'];
            if (isset($validated['stage_id']) && is_numeric($validated['stage_id'])) {
                $task->stage_id = (int) $validated['stage_id'];
            } else {
                $firstStage = TaskStage::where('created_by', creatorId())->orderBy('order')->first();
                $task->stage_id = $firstStage ? $firstStage->id : null;
            }
            $task->creator_id = Auth::id();
            $task->created_by = creatorId();
            $task->save();

            // Log activity
            ActivityLog::create(
                [
                    'user_id' => Auth::user()->id,
                    'user_type' => get_class(Auth::user()),
                    'project_id' => $task->project_id,
                    'log_type' => 'Create Task',
                    'remark' => json_encode(['title' => $task->title]),
                ]
            );

            CreateProjectTask::dispatch($request, $task);

            return redirect()->route('project.tasks.kanban', $validated['project_id'])->with('success', __('The task has been created successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function kanban($projectId)
    {
        if (Auth::user()->can('manage-project-task')) {
            $project = Project::with(['teamMembers:id,name,avatar'])->findOrFail($projectId);
            $stages = TaskStage::where('created_by', creatorId())->orderBy('order')->get();
            $milestones = $project->milestones()->select('id', 'title')->get();
            $teamMembers = $project->teamMembers()->select('users.id', 'users.name', 'users.avatar')->get();

            // Get tasks organized by stages
            $query = ProjectTask::where('project_id', $projectId)
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-project-task')) {
                        $q->where('created_by', creatorId());
                    } else {
                        // Show tasks user created OR assigned to
                        $q->where(function ($subQ) {
                            $subQ->where('creator_id', Auth::id())
                                ->orWhereJsonContains('assigned_to', (string) Auth::id());
                        });
                    }
                });

            $allTasks = $query->with(['assignedUser:id,name,avatar', 'milestone:id,title'])
                ->get();

            $tasks = [];
            foreach ($stages as $stage) {
                $stageKey = strtolower(str_replace(' ', '-', $stage->name));
                $stageTasks = $allTasks->where('stage_id', $stage->id)->map(function ($task) {
                    $assignedUsers = [];
                    if ($task->assigned_to) {
                        $userIds = is_array($task->assigned_to) ? $task->assigned_to : json_decode($task->assigned_to, true);
                        $assignedUsers = \App\Models\User::whereIn('id', $userIds)->select('id', 'name', 'avatar')->get();
                    }
                    return [
                        'id' => $task->id,
                        'title' => $task->title,
                        'description' => $task->description,
                        'priority' => $task->priority,
                        'assigned_users' => $assignedUsers,
                        'milestone' => $task->milestone ? $task->milestone->title : null,
                        'due_date' => $task->duration,
                        'created_at' => $task->created_at->format('Y-m-d')
                    ];
                })->values()->toArray();

                $tasks[$stageKey] = $stageTasks;
            }

            return Inertia::render('Taskly/Tasks/Kanban', [
                'project' => $project,
                'stages' => $stages,
                'tasks' => $tasks,
                'milestones' => $milestones,
                'teamMembers' => $teamMembers,
                'taskStages' => $stages,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function calendar($projectId)
    {
        if (Auth::user()->can('manage-project-task')) {
            $project = Project::with(['teamMembers:id,name'])->findOrFail($projectId);

            $query = ProjectTask::where('project_id', $projectId)
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-project-task')) {
                        $q->where('created_by', creatorId());
                    } else {
                        // Show tasks user created OR assigned to
                        $q->where(function ($subQ) {
                            $subQ->where('creator_id', Auth::id())
                                ->orWhereJsonContains('assigned_to', (string) Auth::id());
                        });
                    }
                });

            $allTasks = $query->with(['assignedUser:id,name', 'milestone:id,title'])
                ->get();

            $tasks = $allTasks->map(function ($task) {
                $assignedUsers = [];
                if ($task->assigned_to) {
                    $userIds = is_array($task->assigned_to) ? $task->assigned_to : json_decode($task->assigned_to, true);
                    $assignedUsers = User::whereIn('id', $userIds)->pluck('name')->toArray();
                }

                $startDate = $task->created_at->format('Y-m-d');
                $endDate = $task->created_at->format('Y-m-d');

                // Parse duration field (format: "2025-09-18 - 2025-09-25")
                if ($task->duration && strpos($task->duration, ' - ') !== false) {
                    $dateRange = explode(' - ', $task->duration);
                    $start = trim($dateRange[0]);
                    $end = trim($dateRange[1]);

                    try {
                        $startDate = \Carbon\Carbon::parse($start)->format('Y-m-d');
                        $endDate = \Carbon\Carbon::parse($end)->format('Y-m-d');
                    } catch (\Exception $e) {
                        // Keep default dates
                    }
                }

                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'time' => '09:00',
                    'description' => $task->description,
                    'type' => 'Task',
                    'color' => $task->priority === 'High' ? '#ef4444' : ($task->priority === 'Medium' ? '#f59e0b' : '#10b77f'),
                    'attendees' => $assignedUsers
                ];
            });

            return Inertia::render('Taskly/Tasks/Calendar', [
                'project' => $project,
                'events' => $tasks,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function move(Request $request, ProjectTask $task)
    {
        // Check if user can edit task OR is assigned to the task
        $assignedUserIds = $task->assigned_to ? (is_array($task->assigned_to) ? $task->assigned_to : json_decode($task->assigned_to, true)) : [];
        $canMove = Auth::user()->can('edit-project-task') ||
            in_array((string) Auth::id(), $assignedUserIds) ||
            $task->creator_id == Auth::id();

        if ($canMove) {
            $request->validate([
                'stage_id' => 'required|exists:task_stages,id'
            ]);

            $oldStage = TaskStage::find($task->stage_id);
            $newStage = TaskStage::find($request->stage_id);

            $request['old_stage_id'] = $task->stage_id;
            $request['new_stage_id'] = $request->stage_id;

            $task->update(['stage_id' => $request->stage_id]);

            // Log activity
            ActivityLog::create([
                'user_id' => Auth::user()->id,
                'user_type' => get_class(Auth::user()),
                'project_id' => $task->project_id,
                'log_type' => 'Move',
                'remark' => json_encode([
                    'title' => $task->title,
                    'old_status' => $oldStage ? $oldStage->name : 'Unknown',
                    'new_status' => $newStage ? $newStage->name : 'Unknown',
                ]),
            ]);

            UpdateProjectTaskStage::dispatch($request, $task);

            return response()->json(['success' => true, 'message' => __('The task has been moved successfully.')]);
        }
        return response()->json(['error' => __('Permission denied')], 403);
    }

    public function update(UpdateProjectTaskRequest $request, ProjectTask $task)
    {
        if (Auth::user()->can('edit-project-task')) {
            $validated = $request->validated();

            $task->title = $validated['title'];
            $task->priority = $validated['priority'] ?? 'Medium';
            $task->assigned_to = isset($validated['assigned_to']) ? (is_array($validated['assigned_to']) ? json_encode($validated['assigned_to']) : $validated['assigned_to']) : null;
            $task->description = $validated['description'];
            $task->duration = $validated['duration'];
            $task->stage_id = $validated['stage_id'];
            $task->save();

            UpdateProjectTask::dispatch($request, $task);

            return response()->json(['success' => true, 'message' => __('The task has been updated successfully.')]);
        }
        return response()->json(['error' => __('Permission denied')], 403);
    }

    public function getTasks($projectId)
    {
        if (Auth::user()->can('manage-project-task')) {
            $stages = TaskStage::where('created_by', creatorId())->orderBy('order')->get();

            $query = ProjectTask::where('project_id', $projectId)

                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-project-task')) {
                        $q->where('created_by', creatorId());
                        // Admin can see all tasks
                    } else {
                        // Show tasks user created OR assigned to
                        $q->where(function ($subQ) {
                            $subQ->where('creator_id', Auth::id())
                                ->orWhereJsonContains('assigned_to', (string) Auth::id());
                        });
                    }
                });

            $allTasks = $query->with(['assignedUser:id,name,avatar', 'milestone:id,title'])
                ->get();

            $tasks = [];
            foreach ($stages as $stage) {
                $stageKey = strtolower(str_replace(' ', '-', $stage->name));
                $stageTasks = $allTasks->where('stage_id', $stage->id)->map(function ($task) {
                    $assignedUsers = [];
                    if ($task->assigned_to) {
                        $userIds = is_array($task->assigned_to) ? $task->assigned_to : json_decode($task->assigned_to, true);
                        $assignedUsers = \App\Models\User::whereIn('id', $userIds)->select('id', 'name', 'avatar')->get();
                    }
                    return [
                        'id' => $task->id,
                        'title' => $task->title,
                        'description' => $task->description,
                        'priority' => $task->priority,
                        'assigned_users' => $assignedUsers,
                        'milestone' => $task->milestone ? $task->milestone->title : null,
                        'due_date' => $task->duration,
                        'created_at' => $task->created_at->format('Y-m-d')
                    ];
                })->values()->toArray();
                $tasks[$stageKey] = $stageTasks;
            }

            return response()->json(['tasks' => $tasks]);
        }
        return response()->json(['error' => __('Permission denied')], 403);
    }

    public function show(ProjectTask $task)
    {
        if (Auth::user()->can('view-project-task')) {
            $task->load([
                'project:id,name',
                'milestone:id,title',
                'assignedUser:id,name'
            ]);

            $stage = TaskStage::select('id', 'name', 'color')->find($task->stage_id);

            // Get assigned users from comma-separated string
            $assignedUsers = [];
            if ($task->assigned_to) {
                $userIds = is_array($task->assigned_to) ? $task->assigned_to : json_decode($task->assigned_to, true);
                $assignedUsers = User::whereIn('id', $userIds)->select('id', 'name', 'avatar')->get();
            }

            // Parse duration into start_date and end_date
            $startDate = null;
            $endDate = null;
            if ($task->duration && strpos($task->duration, ' - ') !== false) {
                $dateRange = explode(' - ', $task->duration);
                $startDate = trim($dateRange[0]);
                $endDate = trim($dateRange[1]);
            }

            return response()->json([
                'task' => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'priority' => $task->priority,
                    'duration' => $task->duration,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'stage_id' => $task->stage_id,
                    'project_id' => $task->project_id,
                    'milestone_id' => $task->milestone_id,
                    'assigned_to' => $task->assigned_to,
                    'project' => $task->project,
                    'milestone' => $task->milestone,
                    'stage' => $stage,
                    'assignedUsers' => $assignedUsers,
                    'created_at' => $task->created_at
                ]
            ]);
        }
        return response()->json(['error' => __('Permission denied')], 403);
    }

    public function destroy(ProjectTask $task)
    {
        if (Auth::user()->can('delete-project-task')) {

            TaskComment::where('task_id', '=', $task->id)->delete();
            TaskSubtask::where('task_id', '=', $task->id)->delete();

            DestroyProjectTask::dispatch($task);

            $task->delete();
            return response()->json(['success' => true]);
        }
        return response()->json(['error' => __('Permission denied')], 403);
    }

    public function getComments(ProjectTask $task)
    {
        if (Auth::user()->can('manage-project-task-comments')) {
            $comments = TaskComment::where('task_id', $task->id)
                ->with('user:id,name,avatar')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['comments' => $comments]);
        }
        return response()->json(['error' => __('Permission denied')], 403);
    }

    public function storeComment(Request $request, ProjectTask $task)
    {
        if (Auth::user()->can('create-project-task-comments')) {
            $request->validate([
                'comment' => 'required|string|max:1000'
            ]);

            $comment = TaskComment::create([
                'task_id' => $task->id,
                'comment' => $request->comment,
                'user_id' => Auth::id()
            ]);

            CreateTaskComment::dispatch($request, $comment);

            return response()->json(['success' => true, 'message' => __('The comment has been added successfully.')]);
        }
        return response()->json(['error' => __('Permission denied')], 403);
    }

    public function destroyComment(TaskComment $comment)
    {
        if (Auth::user()->can('delete-project-task-comments')) {

            DestroyTaskComment::dispatch($comment);

            $comment->delete();
            return response()->json(['success' => true, 'message' => __('The comment has been deleted.')]);
        }
        return response()->json(['error' => __('Permission denied')], 403);
    }

    public function getSubtasks(ProjectTask $task)
    {
        if (Auth::user()->can('manage-project-subtask')) {
            $subtasks = TaskSubtask::where('task_id', $task->id)
                ->with('user:id,name')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['subtasks' => $subtasks]);
        }
        return response()->json(['error' => __('Permission denied')], 403);
    }

    public function storeSubtask(Request $request, ProjectTask $task)
    {
        if (Auth::user()->can('create-project-subtask')) {
            $request->validate([
                'name' => 'required|string|max:255',
                'due_date' => 'nullable|date'
            ]);

            $taskSubTask = TaskSubtask::create([
                'task_id' => $task->id,
                'name' => $request->name,
                'due_date' => $request->due_date,
                'user_id' => Auth::id()
            ]);

            CreateTaskSubtask::dispatch($request, $taskSubTask);

            return response()->json(['success' => true, 'message' => __('The subtask has been added successfully.')]);
        }
        return response()->json(['error' => __('Permission denied')], 403);
    }

    public function toggleSubtask(TaskSubtask $subtask)
    {
        if (Auth::user()->can('manage-project-subtask')) {
            $subtask->update([
                'is_completed' => !$subtask->is_completed
            ]);

            return response()->json(['success' => true]);
        }
        return response()->json(['error' => __('Permission denied')], 403);
    }

    public function apiTasks($projectId)
    {
        if (Auth::user()->can('manage-project-task')) {
            $tasks = ProjectTask::where('project_id', $projectId)
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-project-task')) {
                        $q->where('created_by', creatorId());
                    } else if (Auth::user()->can('manage-own-project-task')) {
                        $q->where(function ($subQ) {
                            $subQ->where('creator_id', Auth::id())
                                ->orWhereJsonContains('assigned_to', (string) Auth::id());
                        });
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->select('id', 'title')
                ->get();

            return response()->json($tasks);
        }
        return response()->json(['error' => __('Permission denied')], 403);
    }
}