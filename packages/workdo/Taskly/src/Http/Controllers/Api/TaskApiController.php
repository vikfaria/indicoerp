<?php

namespace Workdo\Taskly\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Workdo\Taskly\Models\ProjectTask;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Workdo\Taskly\Models\TaskStage;
use Workdo\Taskly\Models\ActivityLog;
use Workdo\Taskly\Models\TaskComment;
use Workdo\Taskly\Models\TaskSubtask;
use Workdo\Taskly\Events\UpdateProjectTaskStage;
use Workdo\Taskly\Models\Project;

class TaskApiController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        try {
            if (Auth::user()->can('manage-project-task')) {
                $items = ProjectTask::select('project_tasks.id', 'project_id', 'milestone_id', 'title', 'priority', 'assigned_to', 'duration', 'description', 'stage_id', 'created_at')
                    ->with(['project:id,name', 'milestone:id,title', 'assignedUser:id,name,avatar'])
                    ->where(function ($q) {
                        if (Auth::user()->can('manage-any-project-task')) {
                            $q->where('created_by', creatorId());
                        } else if (Auth::user()->type == 'client') {
                            $clientProjectIds = Project::where('created_by', Auth::user()->created_by)
                                ->whereHas('clients', function ($query) {
                                    $query->where('client_id', Auth::id());
                                })
                                ->pluck('id');
                            $q->whereIn('project_id', $clientProjectIds);
                        } else if (Auth::user()->can('manage-own-project-task')) {
                            $q->where(function ($subQ) {
                                $subQ->where('creator_id', Auth::id())
                                    ->orWhereJsonContains('assigned_to', (string)Auth::id());
                            });
                        } else {
                            $q->whereRaw('1 = 0');
                        }
                    })
                    ->when($request->project_id, fn($q) => $q->where('project_id', $request->project_id))
                    ->when($request->status && in_array($request->status, ['High', 'Medium', 'Low']), fn($q) => $q->where('priority', $request->status))
                    ->when($request->sort, fn($q) => $q->orderBy($request->sort, $request->get('direction', 'asc')), fn($q) => $q->latest());

                $items = $items->get();
                $items->transform(function ($task) {
                      // Parse duration
                    if ($task->duration && strpos($task->duration, ' - ') !== false) {
                        $dateRange        = explode(' - ', $task->duration);
                        $task->start_date = trim($dateRange[0]);
                        $task->end_date   = trim($dateRange[1]);
                    } else {
                        $task->start_date = null;
                        $task->end_date   = null;
                    }

                      // Get assigned users
                    $assignedUsers = [];
                    if ($task->assigned_to) {
                        $userIds       = is_array($task->assigned_to) ? $task->assigned_to : json_decode($task->assigned_to, true);
                        $assignedUsers = User::whereIn('id', $userIds)
                            ->select('id', 'name', 'email', 'avatar')
                            ->get()
                            ->map(function ($user) {
                                return [
                                    'id'     => $user->id,
                                    'name'   => $user->name,
                                    'email'  => $user->email,
                                    'avatar' => $user->avatar ? getImageUrlPrefix() . '/' . $user->avatar : getImageUrlPrefix() . '/avatar.png',
                                ];
                            });
                    }

                    return [
                        'id'             => $task->id,
                        'title'          => $task->title,
                        'description'    => $task->description,
                        'priority'       => $task->priority,
                        'start_date'     => $task->start_date,
                        'end_date'       => $task->end_date,
                        'stage_id'       => $task->stage_id,
                        'project_id'     => $task->project_id,
                        'milestone_id'   => $task->milestone_id,
                        'project'        => $task->project,
                        'milestone'      => $task->milestone,
                        'assigned_users' => $assignedUsers,
                    ];
                });

                return $this->successResponse($items, 'Tasks retrieved successfully');
            } else {
                return $this->errorResponse('Permission denied');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    public function taskCreateAndUpdate(Request $request)
    {
        try {
            if ($request->task_id) {
                return $this->updateTask($request);
            } else {
                return $this->createTask($request);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    private function createTask(Request $request)
    {
        if (Auth::user()->can('create-project-task')) {
            $validator = Validator::make($request->all(), [
                'project_id'   => 'required|exists:projects,id',
                'title'        => 'required|string|max:255',
                'priority'     => 'nullable|in:High,Medium,Low',
                'assigned_to'  => 'required|array|min:1',
                'duration'     => 'required|string',
                'description'  => 'required|string',
                'milestone_id' => 'required|exists:project_milestones,id',
                'stage_id'     => 'nullable|exists:task_stages,id'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $validated          = $validator->validated();
            $task               = new ProjectTask();
            $task->project_id   = $validated['project_id'];
            $task->milestone_id = $validated['milestone_id'] ?? null;
            $task->title        = $validated['title'];
            $task->priority     = $validated['priority'];
            $task->assigned_to  = json_encode($validated['assigned_to']);
            $task->duration     = $validated['duration'];
            $task->description  = $validated['description'] ?? '';

            if (isset($validated['stage_id'])) {
                $task->stage_id = $validated['stage_id'];
            } else {
                $firstStage     = TaskStage::where('created_by', creatorId())->orderBy('order')->first();
                $task->stage_id = $firstStage ? $firstStage->id : null;
            }

            $task->creator_id = Auth::id();
            $task->created_by = creatorId();
            $task->save();

            ActivityLog::create([
                'user_id'    => Auth::user()->id,
                'user_type'  => get_class(Auth::user()),
                'project_id' => $task->project_id,
                'log_type'   => 'Create Task',
                'remark'     => json_encode(['title' => $task->title]),
            ]);

            $assignedUsers = User::whereIn('id', $validated['assigned_to'])
                ->select('id', 'name', 'email', 'avatar')
                ->get()
                ->map(function ($user) {
                    return [
                        'id'     => $user->id,
                        'name'   => $user->name,
                        'email'  => $user->email,
                        'avatar' => $user->avatar ? getImageUrlPrefix() . '/' . $user->avatar : getImageUrlPrefix() . '/avatar.png',
                    ];
                });

            $responseData = [
                'id'             => $task->id,
                'title'          => $task->title,
                'description'    => $task->description,
                'priority'       => $task->priority,
                'duration'       => $task->duration,
                'stage_id'       => $task->stage_id,
                'project_id'     => $task->project_id,
                'milestone_id'   => $task->milestone_id,
                'assigned_to'    => $assignedUsers,
            ];

            return $this->successResponse($responseData, 'Task created successfully.');
        } else {
            return $this->errorResponse('Permission denied');
        }
    }

    private function updateTask(Request $request)
    {
        if (Auth::user()->can('edit-project-task')) {
            $validator = Validator::make($request->all(), [
                'task_id'      => 'required|exists:project_tasks,id',
                'title'        => 'required|string|max:255',
                'priority'     => 'nullable|in:High,Medium,Low',
                'assigned_to'  => 'required|array|min:1',
                'duration'     => 'required|string',
                'description'  => 'required|string',
                'stage_id'     => 'nullable|exists:task_stages,id'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $validated = $validator->validated();
            $task      = ProjectTask::findOrFail($validated['task_id']);

            $task->title        = $validated['title'];
            $task->priority     = $validated['priority'];
            $task->assigned_to  = json_encode($validated['assigned_to']);
            $task->duration     = $validated['duration'];
            $task->description  = $validated['description'] ?? '';
            $task->milestone_id = $validated['milestone_id'] ?? null;
            $task->stage_id     = $validated['stage_id'] ?? $task->stage_id;
            $task->save();

            $assignedUsers = User::whereIn('id', $validated['assigned_to'])
                ->select('id', 'name', 'email', 'avatar')
                ->get()
                ->map(function ($user) {
                    return [
                        'id'     => $user->id,
                        'name'   => $user->name,
                        'email'  => $user->email,
                        'avatar' => $user->avatar ? getImageUrlPrefix() . '/' . $user->avatar : getImageUrlPrefix() . '/avatar.png',
                    ];
                });

            $responseData = [
                'id'             => $task->id,
                'title'          => $task->title,
                'description'    => $task->description,
                'priority'       => $task->priority,
                'duration'       => $task->duration,
                'stage_id'       => $task->stage_id,
                'project_id'     => $task->project_id,
                'milestone_id'   => $task->milestone_id,
                'assigned_users' => $assignedUsers,
            ];

            return $this->successResponse($responseData, 'Task updated successfully.');
        } else {
            return $this->errorResponse('Permission denied');
        }
    }

    public function taskDelete(Request $request)
    {
        try {
            if (Auth::user()->can('delete-project-task')) {
                $validator = Validator::make($request->all(), [
                    'task_id' => 'required|exists:project_tasks,id',
                ]);

                if ($validator->fails()) {
                    return $this->validationErrorResponse($validator->errors());
                }

                $task = ProjectTask::findOrFail($request->task_id);

                TaskComment::where('task_id', $task->id)->delete();
                TaskSubtask::where('task_id', $task->id)->delete();

                $task->delete();

                return $this->successResponse('', 'Task deleted successfully.');
            } else {
                return $this->errorResponse('Permission denied');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    public function taskDetails(Request $request)
    {
        try {
            if (Auth::user()->can('view-project-task')) {
                $validator = Validator::make($request->all(), [
                    'task_id' => 'required|exists:project_tasks,id',
                ]);

                if ($validator->fails()) {
                    return $this->validationErrorResponse($validator->errors());
                }

                $task = ProjectTask::with(['project:id,name', 'milestone:id,title'])
                    ->findOrFail($request->task_id);

                $assignedUsers = [];
                if ($task->assigned_to) {
                    $userIds       = is_array($task->assigned_to) ? $task->assigned_to : json_decode($task->assigned_to, true);
                    $assignedUsers = User::whereIn('id', $userIds)
                        ->select('id', 'name', 'email', 'avatar')
                        ->get()
                        ->map(function ($user) {
                            return [
                                'id'     => $user->id,
                                'name'   => $user->name,
                                'email'  => $user->email,
                                'avatar' => $user->avatar ? getImageUrlPrefix() . '/' . $user->avatar : getImageUrlPrefix() . '/avatar.png',
                            ];
                        });
                }

                $startDate = null;
                $endDate   = null;
                if ($task->duration && strpos($task->duration, ' - ') !== false) {
                    $dateRange = explode(' - ', $task->duration);
                    $startDate = trim($dateRange[0]);
                    $endDate   = trim($dateRange[1]);
                }

                $taskDetails = [
                    'id'             => $task->id,
                    'title'          => $task->title,
                    'description'    => $task->description,
                    'priority'       => $task->priority,
                    'start_date'     => $startDate,
                    'end_date'       => $endDate,
                    'stage_id'       => $task->stage_id,
                    'project_id'     => $task->project_id,
                    'milestone_id'   => $task->milestone_id,
                    'created_at'     => $task->created_at,
                    'project'        => $task->project,
                    'milestone'      => $task->milestone,
                    'assigned_users' => $assignedUsers,
                ];

                return $this->successResponse($taskDetails, 'Task details fetched successfully.');
            } else {
                return $this->errorResponse('Permission denied');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    public function taskStageUpdate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'task_id'  => 'required|exists:project_tasks,id',
                'stage_id' => 'required|exists:task_stages,id'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $task = ProjectTask::findOrFail($request->task_id);

            $assignedUserIds = $task->assigned_to ? (is_array($task->assigned_to) ? $task->assigned_to : json_decode($task->assigned_to, true)) : [];
            $canMove         = Auth::user()->can('edit-project-task') ||
                in_array((string)Auth::id(), $assignedUserIds) ||
                $task->creator_id == Auth::id();

            if ($canMove) {
                if ($request->stage_id != $task->stage_id) {
                    $oldStage = TaskStage::find($task->stage_id);
                    $newStage = TaskStage::find($request->stage_id);

                    $task->update(['stage_id' => $request->stage_id]);

                    ActivityLog::create([
                        'user_id'    => Auth::user()->id,
                        'user_type'  => get_class(Auth::user()),
                        'project_id' => $task->project_id,
                        'log_type'   => 'Move',
                        'remark'     => json_encode([
                            'title'      => $task->title,
                            'old_status' => $oldStage ? $oldStage->name : 'Unknown',
                            'new_status' => $newStage ? $newStage->name : 'Unknown',
                        ]),
                    ]);

                    $request->merge(['old_stage_id' => $oldStage->id, 'new_stage_id' => $newStage->id]);
                }

                return $this->successResponse('', 'Task stage updated successfully.');
            } else {
                return $this->errorResponse('Permission denied');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    public function taskboard(Request $request)
    {
        try {
            if (Auth::user()->can('manage-project-task')) {
                $validator = Validator::make($request->all(), [
                    'project_id' => 'required|exists:projects,id',
                ]);

                if ($validator->fails()) {
                    return $this->validationErrorResponse($validator->errors());
                }

                $projectId = $request->project_id;
                $stages    = TaskStage::where('created_by', creatorId())->orderBy('order')->get();

                $query = ProjectTask::where('project_id', $projectId)
                    ->where(function ($q) {
                        if (Auth::user()->can('manage-any-project-task')) {
                            $q->where('created_by', creatorId());
                        } else if (Auth::user()->type == 'client') {
                            $clientProjectIds = Project::where('created_by', Auth::user()->created_by)
                                ->whereHas('clients', function ($query) {
                                    $query->where('client_id', Auth::id());
                                })
                                ->pluck('id');
                            $q->whereIn('project_id', $clientProjectIds);
                        } else {
                            $q->where(function ($subQ) {
                                $subQ->where('creator_id', Auth::id())
                                    ->orWhereJsonContains('assigned_to', (string)Auth::id());
                            });
                        }
                    });

                $allTasks = $query->with(['milestone:id,title'])->get();

                $stagesData = [];
                foreach ($stages as $key => $stage) {
                    $stageTasks = $allTasks->where('stage_id', $stage->id)->map(function ($task) use ($stages, $key) {
                        $assignedUsers = [];
                        if ($task->assigned_to) {
                            $userIds       = is_array($task->assigned_to) ? $task->assigned_to : json_decode($task->assigned_to, true);
                            $assignedUsers = User::whereIn('id', $userIds)
                                ->select('id', 'name', 'email', 'avatar')
                                ->get()
                                ->map(function ($user) {
                                    return [
                                        'id'     => $user->id,
                                        'name'   => $user->name,
                                        'email'  => $user->email,
                                        'avatar' => $user->avatar ? getImageUrlPrefix() . '/' . $user->avatar : getImageUrlPrefix() . '/avatar.png',
                                    ];
                                });
                        }

                        $startDate = null;
                        $endDate   = null;
                        if ($task->duration && strpos($task->duration, ' - ') !== false) {
                            $dateRange = explode(' - ', $task->duration);
                            $startDate = trim($dateRange[0]);
                            $endDate   = trim($dateRange[1]);
                        }

                        return [
                            'id'             => $task->id,
                            'title'          => $task->title,
                            'description'    => $task->description,
                            'priority'       => $task->priority,
                            'start_date'     => $startDate,
                            'end_date'       => $endDate,
                            'project_id'     => $task->project_id,
                            'milestone_id'   => $task->milestone_id,
                            'previous_stage' => isset($stages[$key - 1]) ? $stages[$key - 1]->id : 0,
                            'current_stage'  => $stages[$key]->id,
                            'next_stage'     => isset($stages[$key + 1]) ? $stages[$key + 1]->id : 0,
                            'assigned_users' => $assignedUsers,
                        ];
                    })->values();

                    $stagesData[] = [
                        'id'       => $stage->id,
                        'name'     => $stage->name,
                        'color'    => $stage->color,
                        'complete' => $stage->complete,
                        'order'    => $stage->order,
                        'tasks'    => $stageTasks,
                    ];
                }

                return $this->successResponse($stagesData, 'Taskboard retrieved successfully');
            } else {
                return $this->errorResponse('Permission denied');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }
}
