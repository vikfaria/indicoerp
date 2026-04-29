<?php

namespace Workdo\Taskly\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Workdo\Taskly\Models\Project;
use Workdo\Taskly\Models\ProjectTask;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Workdo\Taskly\Models\ActivityLog;
use Workdo\Taskly\Models\ProjectMilestone;
use Workdo\Taskly\Models\ProjectBug;
use Workdo\Taskly\Models\ProjectClient;
use Workdo\Taskly\Models\ProjectFile;
use Workdo\Taskly\Models\ProjectUser;
use Workdo\Taskly\Models\TaskStage;

class ProjectApiController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        try {
            if (Auth::user()->can('manage-project')) {
                $items = Project::query()
                    ->select('id', 'name', 'description', 'budget', 'start_date', 'end_date', 'status', 'created_by')
                    ->with(['teamMembers:id,name,email,avatar', 'clients:id,name,email,avatar'])
                    ->where(function ($q) {
                        if (Auth::user()->can('manage-any-project')) {
                            $q->where('created_by', creatorId());
                        } else if (Auth::user()->can('manage-own-project')) {
                            $q->where(function ($subQ) {
                                $subQ->where('creator_id', Auth::id());
                                if (Auth::user()->type === 'client') {
                                    $subQ->orWhereHas('clients', function ($clientQ) {
                                        $clientQ->where('client_id', Auth::id());
                                    });
                                } else {
                                    $subQ->orWhereHas('teamMembers', function ($teamQ) {
                                        $teamQ->where('user_id', Auth::id());
                                    });
                                }
                            });
                        } else {
                            $q->whereRaw('1 = 0');
                        }
                    })
                    ->when($request->status && in_array($request->status, ['Ongoing', 'Onhold', 'Finished']), fn($q) => $q->where('status', $request->status))
                    ->when($request->sort, fn($q) => $q->orderBy($request->sort, $request->get('direction', 'asc')), fn($q) => $q->latest())
                    ->paginate($request->get('per_page', 10));

                $items->getCollection()->transform(function ($project) {
                    $totalTask     = ProjectTask::where('project_id', $project->id)->count();
                    $totalComments = ProjectTask::where('project_id', $project->id)->withCount('comments')->get()->sum('comments_count');
                    $members       = $project->teamMembers->map(function ($member) {
                        return [
                            'id'     => $member->id,
                            'name'   => $member->name,
                            'email'  => $member->email,
                            'avatar' => $member->avatar ? getImageUrlPrefix() . '/' . $member->avatar : getImageUrlPrefix() . '/avatar.png',
                        ];
                    });
                    $clients = $project->clients->map(function ($client) {
                        return [
                            'id'     => $client->id,
                            'name'   => $client->name,
                            'email'  => $client->email,
                            'avatar' => $client->avatar ? getImageUrlPrefix() . '/' . $client->avatar : getImageUrlPrefix() . '/avatar.png',
                        ];
                    });
                    return [
                        'id'             => $project->id,
                        'name'           => $project->name,
                        'status'         => $project->status,
                        'description'    => $project->description,
                        'total_task'     => $totalTask,
                        'total_comments' => $totalComments,
                        'start_date'     => $project->start_date->format('Y-m-d'),
                        'end_date'       => $project->end_date->format('Y-m-d'),
                        'created_by'     => $project->created_by,
                        'members'        => $members,
                        'clients'        => $clients,
                    ];
                });

                return $this->paginatedResponse($items, 'Projects retrieved successfully');
            } else {
                return $this->errorResponse('Permission denied');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    public function projectCreateAndUpdate(Request $request)
    {
        try {
            if ($request->project_id) {
                return $this->updateProject($request);
            } else {
                return $this->createProject($request);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    private function createProject(Request $request)
    {
        if (Auth::user()->can('create-project')) {
            $validator = Validator::make($request->all(), [
                'name'        => 'required|string',
                'description' => 'required|string',
                'budget'      => 'required|numeric|gt:0',
                'start_date'  => 'required|date_format:Y-m-d',
                'end_date'    => 'required|date_format:Y-m-d|after_or_equal:start_date',
                'user_ids'    => 'required|array|min:1',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $validated            = $validator->validated();
            $project              = new Project();
            $project->name        = $validated['name'];
            $project->description = $validated['description'];
            $project->budget      = $validated['budget'];
            $project->start_date  = $validated['start_date'];
            $project->end_date    = $validated['end_date'];
            $project->status      = 'Ongoing';
            $project->creator_id  = Auth::id();
            $project->created_by  = creatorId();
            $project->save();

            $project->teamMembers()->sync($validated['user_ids']);

            $responseData = [
                'id'          => $project->id,
                'name'        => $project->name,
                'description' => $project->description,
                'budget'      => $project->budget,
                'start_date'  => $project->start_date->format('Y-m-d'),
                'end_date'    => $project->end_date->format('Y-m-d'),
                'status'      => $project->status,
                'users'       => $project->teamMembers->map(function ($user) {
                    return [
                        'id'    => $user->id,
                        'name'  => $user->name,
                        'email' => $user->email
                    ];
                })
            ];
            return $this->successResponse($responseData, 'Project created successfully.');
        } else {
            return $this->errorResponse('Permission denied');
        }
    }

    private function updateProject(Request $request)
    {
        if (Auth::user()->can('edit-project')) {
            $validator = Validator::make($request->all(), [
                'project_id'  => 'required|exists:projects,id',
                'name'        => 'required|string',
                'description' => 'required|string',
                'budget'      => 'required|numeric|gt:0',
                'start_date'  => 'required|date_format:Y-m-d',
                'end_date'    => 'required|date_format:Y-m-d|after_or_equal:start_date',
                'status'      => 'in:Ongoing,Onhold,Finished',
                'user_ids'    => 'required|array|min:1',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $validated = $validator->validated();
            $project   = Project::findOrFail($validated['project_id']);

            $project->name        = $validated['name'];
            $project->description = $validated['description'];
            $project->budget      = $validated['budget'];
            $project->start_date  = $validated['start_date'];
            $project->end_date    = $validated['end_date'];
            if (isset($validated['status'])) {
                $project->status = $validated['status'];
            }
            $project->save();

            if (isset($validated['user_ids'])) {
                $project->teamMembers()->sync($validated['user_ids']);
            }

            $responseData = [
                'id'          => $project->id,
                'name'        => $project->name,
                'description' => $project->description,
                'budget'      => $project->budget,
                'start_date'  => $project->start_date->format('Y-m-d'),
                'end_date'    => $project->end_date->format('Y-m-d'),
                'status'      => $project->status,
                'users'       => $project->teamMembers->map(function ($user) {
                    return [
                        'id'    => $user->id,
                        'name'  => $user->name,
                        'email' => $user->email
                    ];
                })
            ];
            return $this->successResponse($responseData, 'Project updated successfully.');
        } else {
            return $this->errorResponse('Permission denied');
        }
    }

    public function getUsers()
    {
        try {
            $users = User::where('created_by', creatorId())
                ->emp()
                ->get(['id', 'name', 'email', 'type'])
                ->map(function ($user) {
                    return [
                        'id'    => $user->id,
                        'name'  => $user->name,
                        'email' => $user->email,
                        'type'  => $user->type
                    ];
                });

            return $this->successResponse($users);
        } catch (\Exception $e) {
            return $this->errorResponse('something went wrong');
        }
    }
    public function destroyProject(Request $request)
    {
        try {
            if (Auth::user()->can('delete-project')) {
                $validator = Validator::make($request->all(), [
                    'project_id' => 'required|exists:projects,id',
                ]);

                if ($validator->fails()) {
                    return $this->validationErrorResponse($validator->errors());
                }

                $project = Project::findOrFail($request->project_id);

                if ($project->created_by != creatorId()) {
                    return $this->errorResponse('Project Not Found', 404);
                }
                ProjectTask::where('project_id', $project->id)->delete();
                ProjectBug::where('project_id', $project->id)->delete();
                ProjectMilestone::where('project_id', $project->id)->delete();
                ActivityLog::where('project_id', $project->id)->delete();
                ProjectClient::where('project_id', $project->id)->delete();
                ProjectUser::where('project_id', $project->id)->delete();

                $project->delete();

                return $this->successResponse('', 'Project Deleted successfully.');
            } else {
                return $this->errorResponse('Permission denied');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    public function projectDetails(Request $request)
    {
        try {
            if (Auth::user()->can('view-project')) {
                $validator = Validator::make($request->all(), [
                    'project_id' => 'required|exists:projects,id',
                ]);

                if ($validator->fails()) {
                    return response()->json(['status' => 0, 'message' => $validator->errors()->first()], 403);
                }

                $project = Project::with(['teamMembers:id,name,email,avatar', 'clients:id,name,email,avatar', 'milestones'])
                    ->findOrFail($request->project_id);

                if ($project->created_by != creatorId()) {
                    return response()->json(['status' => 0, 'message' => 'Project Not Found'], 404);
                }

                $taskCount = ProjectTask::where('project_id', $project->id)->count();
                $daysLeft  = (int) ($project->end_date ? max(0, now()->diffInDays($project->end_date, false)) : 0);
                $members   = $project->teamMembers->map(function ($user) {
                    return [
                        'id'     => $user->id,
                        'name'   => $user->name,
                        'email'  => $user->email,
                        'avatar' => $user->avatar ? getImageUrlPrefix() . '/' . $user->avatar : getImageUrlPrefix() . '/avatar.png',
                    ];
                });
                $clients = $project->clients->map(function ($client) {
                    return [
                        'id'     => $client->id,
                        'name'   => $client->name,
                        'email'  => $client->email,
                        'avatar' => $client->avatar ? getImageUrlPrefix() . '/' . $client->avatar : getImageUrlPrefix() . '/avatar.png',
                    ];
                });
                $milestones = $project->milestones->map(function ($milestone) {
                    return [
                        'id'         => $milestone->id,
                        'title'      => $milestone->title,
                        'start_date' => $milestone->start_date->format('Y-m-d'),
                        'end_date'   => $milestone->end_date->format('Y-m-d'),
                        'status'     => $milestone->status,
                        'cost'       => $milestone->cost,
                        'progress'   => $milestone->progress,
                        'summary'    => $milestone->summary,
                    ];
                });
                $bugCount = ProjectBug::where('project_id', $project->id)->count();
                $files    = ProjectFile::where('project_id', $project->id)->get()->map(function ($file) {
                    return [
                        'id'         => $file->id,
                        'project_id' => $file->project_id,
                        'file_name'  => $file->file_name,
                        'file_path'  => getImageUrlPrefix() . $file->file_path,
                    ];
                });
                $taskStages = TaskStage::where('created_by', creatorId())->orderBy('order')->select('id', 'name')->get();

                $projectDetails = [
                    'id'          => $project->id,
                    'name'        => $project->name,
                    'start_date'  => $project->start_date->format('Y-m-d'),
                    'end_date'    => $project->end_date->format('Y-m-d'),
                    'status'      => $project->status,
                    'description' => $project->description,
                    'daysleft'    => $daysLeft,
                    'budget'      => number_format($project->budget),
                    'total_task'  => $taskCount,
                    'total_bug'   => $bugCount,
                    'members'     => $members,
                    'clients'     => $clients,
                    'milestones'  => $milestones,
                    'files'       => $files,
                    'task_stages' => $taskStages
                ];

                return $this->successResponse($projectDetails, 'Project details fetched successfully.');
            } else {
                return $this->errorResponse('Permission denied');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }
    public function projectStatusUpdate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'project_id' => 'required|exists:projects,id',
                'status'     => 'required|in:Ongoing,Finished,OnHold',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $project         = Project::find($request->project_id);
            $project->status = $request->status;
            $project->save();

            return $this->successResponse('', 'Project Status Change Successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    public function projectActivity(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'project_id' => 'required|exists:projects,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $project = Project::findOrFail($request->project_id);

            if ($project->created_by != creatorId()) {
                return $this->errorResponse('Project Not Found', 404);
            }

            $activities = ActivityLog::where('project_id', $project->id)
                ->with('user:id,name')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($activity) {
                    return [
                        'id'     => $activity->id,
                        'remark' => strip_tags($activity->getRemark()),
                        'time'   => $activity->created_at->diffForHumans(),
                    ];
                });

            return $this->successResponse($activities, 'Activity fetched successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }
    public function projectFileUpload(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'project_id' => 'required|exists:projects,id',
                'file'       => 'required|file'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $project = Project::findOrFail($request->project_id);

            if ($project->created_by != creatorId()) {
                return $this->errorResponse('Project Not Found', 404);
            }

            if ($request->hasFile('file')) {
                $filenameWithExt = $request->file('file')->getClientOriginalName();
                $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension       = $request->file('file')->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;

                $path = upload_file($request, 'file', $fileNameToStore, '');

                if ($path['flag'] == 0) {
                    return $this->errorResponse($path['msg']);
                }

                $projectFile = ProjectFile::create([
                    'project_id' => $project->id,
                    'file_name'  => $filename,
                    'file_path'  => ltrim($path['url'], '/'),
                ]);

                $data = [
                    'id'         => $projectFile->id,
                    'project_id' => $projectFile->project_id,
                    'file_name'  => $projectFile->file_name,
                    'file_path'  => getImageUrlPrefix() . $projectFile->file_path,
                ];

                return $this->successResponse($data, 'File uploaded successfully.');
            }
            return $this->errorResponse('No file provided');
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }
}
