<?php

namespace Workdo\Taskly\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Workdo\Taskly\Events\CreateProject;
use Workdo\Taskly\Events\CreateProjectMilestone;
use Workdo\Taskly\Events\DestroyProject;
use Workdo\Taskly\Events\DestroyProjectMilestone;
use Workdo\Taskly\Events\ProjectDeleteClient;
use Workdo\Taskly\Events\ProjectDeleteShareToClient;
use Workdo\Taskly\Events\ProjectInviteMember;
use Workdo\Taskly\Events\ProjectShareToClient;
use Workdo\Taskly\Events\UpdateProject;
use Workdo\Taskly\Events\UpdateProjectMilestone;
use Workdo\Taskly\Models\Project;
use Workdo\Taskly\Http\Requests\UpdateMilestoneRequest;
use Workdo\Taskly\Http\Requests\StoreMilestoneRequest;
use Workdo\Taskly\Http\Requests\StoreProjectRequest;
use Workdo\Taskly\Http\Requests\UpdateProjectRequest;
use Workdo\Taskly\Models\ActivityLog;
use Workdo\Taskly\Models\ProjectMilestone;
use Workdo\Taskly\Models\ProjectTask;
use Workdo\Taskly\Models\ProjectBug;
use Workdo\Taskly\Models\ProjectClient;
use Workdo\Taskly\Models\ProjectUser;
use Workdo\Taskly\Models\ProjectFile;

class ProjectController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-project')) {
            $items = Project::query()
                ->select('id', 'name', 'description', 'budget', 'start_date', 'end_date', 'status', 'created_by')
                ->with(['teamMembers:id,name,avatar'])
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-project')) {
                         $q->where('created_by', creatorId());
                    } else if(Auth::user()->can('manage-own-project')) {
                        // For all other users, show projects they are involved in
                        $q->where(function($subQ) {
                            // Show own created projects
                            $subQ->where('creator_id', Auth::id());
                            // OR show assigned projects (for staff)
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
                        // If no permissions, show nothing
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('name'), fn($q) => $q->where('name', 'like', '%' . request('name') . '%'))
                ->when(request('status'), fn($q) => $q->where('status', request('status')))
                ->when(request('date'), fn($q) => $q->where('start_date', '<=', request('date'))->where('end_date', '>=', request('date')))

                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            $users = User::where('created_by', creatorId())->emp()->get(['id', 'name']);

            // Transform items to include team_members with avatars and task count
            $items->getCollection()->transform(function ($project) {
                $project->team_members = $project->teamMembers->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'name' => $member->name,
                        'avatar' => $member->avatar
                    ];
                });
                $project->task_count = ProjectTask::where('project_id', $project->id)->count();

                // Add user relationship status
                $project->is_creator = $project->creator_id == Auth::id();
                $project->is_assigned = $project->teamMembers->contains('id', Auth::id());
                $project->user_role = $project->is_creator ? 'Creator' : ($project->is_assigned ? 'Team Member' : 'Other');

                return $project;
            });

            return Inertia::render('Taskly/Project/Index', [
                'items' => $items,
                'users' => $users,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreProjectRequest $request)
    {
        if (Auth::user()->can('create-project')) {
            $validated = $request->validated();

            $project                = new Project();
            $project->name          = $validated['name'];
            $project->description   = $validated['description'];
            $project->budget        = $validated['budget'];
            $project->start_date    = $validated['start_date'];
            $project->end_date      = $validated['end_date'];
            $project->status        = 'Ongoing';
            $project->creator_id    = Auth::id();
            $project->created_by    = creatorId();
            $project->save();

            $project->teamMembers()->sync($validated['user_ids']);

            CreateProject::dispatch($request, $project);

            return redirect()->route('project.index')->with('success', __('The project has been created successfully.'));
        } else {
            return redirect()->route('project.index')->with('error', __('Permission denied'));
        }
    }

    public function show(Project $project)
    {
        if (Auth::user()->can('view-project') && $project->created_by == creatorId()) {
            $project->load(['teamMembers:id,name', 'clients:id,name', 'milestones']);

            if(!Auth::user()->can('manage-any-project') && $project->creator_id != Auth::id())
            {
                if (Auth::user()->can('manage-own-project'))
                {
                    // Check if user is team member or client
                    $isTeamMember = $project->teamMembers->contains('id', Auth::id());
                    $isClient = $project->clients->contains('id', Auth::id());

                    if (!$isTeamMember && !$isClient) {
                        return redirect()->route('project.index')->with('error', __('Access denied'));
                    }
                }
                else {
                    return redirect()->route('project.index')->with('error', __('Access denied'));
                }
            }

            $teamMembers = User::emp()
                ->where('created_by', creatorId())
                ->whereNotIn('id', function ($q) use ($project) {
                    $q->select('user_id')->from('project_users')->where('project_id', '=', $project->id);
                })
                ->get(['id', 'name']);

            $available_clients = User::where('type', 'client')
                ->where('created_by', creatorId())
                ->whereNotIn('id', function ($q) use ($project) {
                    $q->select('client_id')->from('project_clients')->where('project_id', '=', $project->id);
                })
                ->get(['id', 'name']);

            // Get project statistics
            $taskCount = ProjectTask::where('project_id', $project->id)->count();
            $bugCount = ProjectBug::where('project_id', $project->id)->where('created_by', creatorId())->count();

            // Calculate days left
            $daysLeft = $project->end_date ? max(0, now()->diffInDays($project->end_date, false)) : 0;

            // Get task stages and last 5 months stage-wise task counts
            $taskStages = \Workdo\Taskly\Models\TaskStage::where('created_by', creatorId())->orderBy('order')->get();
            $chartData = [];
            for ($i = 4; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthData = ['name' => $date->format('M')];

                foreach ($taskStages as $stage) {
                    $stageTaskCount = ProjectTask::where('project_id', $project->id)
                        ->where('created_by', creatorId())
                        ->where('stage_id', $stage->id)
                        ->whereRaw('YEAR(STR_TO_DATE(SUBSTRING_INDEX(duration, " - ", 1), "%Y-%m-%d")) = ?', [$date->year])
                        ->whereRaw('MONTH(STR_TO_DATE(SUBSTRING_INDEX(duration, " - ", 1), "%Y-%m-%d")) = ?', [$date->month])
                        ->count();
                    $monthData[$stage->name] = $stageTaskCount;
                }
                $chartData[] = $monthData;
            }

            // Prepare lines configuration for multiple line chart
            $chartLines = [];
            foreach ($taskStages as $stage) {
                $chartLines[] = [
                    'dataKey' => $stage->name,
                    'color' => $stage->color,
                    'name' => $stage->name
                ];
            }
            // Get recent activity logs
            $activityLogs = ActivityLog::where('project_id', $project->id)
                ->with('user:id,name')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($log) {
                    return [
                        'id' => $log->id,
                        'log_type' => $log->log_type,
                        'remark' => $log->getRemark(),
                        'created_at' => $log->created_at,
                        'user' => $log->user
                    ];
                });

            return Inertia::render('Taskly/Project/View', [
                'project' => $project,
                'teamMembers' => $teamMembers,
                'available_clients' => $available_clients,
                'projectStats' => [
                    'taskCount' => $taskCount,
                    'bugCount' => $bugCount,
                    'daysLeft' => $daysLeft,
                    'budget' => $project->budget
                ],
                'chartData' => $chartData,
                'chartLines' => $chartLines,
                'activityLogs' => $activityLogs,
                'projectFiles' => ProjectFile::where('project_id', $project->id)->get(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function edit(Project $project)
    {
        if (Auth::user()->can('edit-project') && $project->created_by == creatorId()) {
            return response()->json([
                'project' => $project->only(['id', 'name', 'description', 'budget', 'start_date', 'end_date', 'status'])
            ]);
        } else {
            return response()->json(['error' => 'Permission denied'], 403);
        }
    }


    public function update(UpdateProjectRequest $request, Project $project)
    {
        if (Auth::user()->can('edit-project')) {
            $validated = $request->validated();

            $project->name         = $validated['name'];
            $project->description  = $validated['description'];
            $project->budget       = $validated['budget'];
            $project->start_date   = $validated['start_date'];
            $project->end_date     = $validated['end_date'];
            $project->status       = $validated['status'] ?? $project->status;
            $project->save();

            UpdateProject::dispatch($request, $project);

            return back()->with('success', __('The project details are updated successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(Project $project)
    {
        if (Auth::user()->can('delete-project')) {

            ProjectTask::where('project_id', '=', $project->id)->delete();
            ProjectBug::where('project_id', '=', $project->id)->delete();
            ProjectMilestone::where('project_id', '=', $project->id)->delete();
            ActivityLog::where('project_id', '=', $project->id)->delete();
            ProjectClient::where('project_id', '=', $project->id)->delete();
            ProjectUser::where('project_id', '=', $project->id)->delete();

            DestroyProject::dispatch($project);
            $project->delete();
           return back()->with('success', __('The project has been deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    public function duplicate(Request $request, Project $project)
    {
        if (Auth::user()->can('duplicate-project')) {
            $originalProject = Project::with(['bugs.comments', 'tasks.subtasks', 'tasks.comments', 'activityLogs', 'files'])->find($project->id);
            $newProject = $originalProject->replicate();
            $newProject->name = $originalProject->name . ' (Copy)';
            $newProject->status = $originalProject->status;
            $newProject->creator_id = Auth::id();
            $newProject->created_by = creatorId();
            $newProject->save();

            // Duplicate based on selected options
            if ($request->get('all') || $request->get('teamMembers')) {
                $teamMemberIds = $originalProject->teamMembers()->pluck('user_id')->toArray();
                $newProject->teamMembers()->sync($teamMemberIds);
            }

            if ($request->get('all') || $request->get('clients')) {
                $clientIds = $originalProject->clients()->pluck('client_id')->toArray();
                $newProject->clients()->sync($clientIds);
            }

            if ($request->get('all') || $request->get('milestones')) {
                foreach ($originalProject->milestones ?? [] as $milestone) {
                    $newMilestone = $milestone->replicate();
                    $newMilestone->project_id = $newProject->id;
                    $newMilestone->save();
                }
            }

            if ($request->get('all') || $request->get('tasks')) {
                foreach ($originalProject->tasks ?? [] as $task) {
                    $newTask = $task->replicate();
                    $newTask->project_id = $newProject->id;
                    $newTask->save();

                    if ($request->get('all') || $request->get('taskSubtasks')) {
                        foreach ($task->subtasks ?? [] as $subtask) {
                            $newSubtask = $subtask->replicate();
                            $newSubtask->task_id = $newTask->id;
                            $newSubtask->save();
                        }
                    }

                    if ($request->get('all') || $request->get('taskComments')) {
                        foreach ($task->comments ?? [] as $comment) {
                            $newComment = $comment->replicate();
                            $newComment->task_id = $newTask->id;
                            $newComment->save();
                        }
                    }
                }
            }
            if ($request->get('all') || $request->get('bugs')) {
                foreach ($originalProject->bugs ?? [] as $bug) {
                    $newBug = $bug->replicate();
                    $newBug->project_id = $newProject->id;
                    $newBug->save();

                    if ($request->get('all') || $request->get('bugComments')) {
                        foreach ($bug->comments ?? [] as $comment) {
                            $newComment = $comment->replicate();
                            $newComment->bug_id = $newBug->id;
                            $newComment->save();
                        }
                    }
                }
            }

            if ($request->get('all') || $request->get('activity')) {
                foreach ($originalProject->activityLogs ?? [] as $log) {
                    $newLog = $log->replicate();
                    $newLog->project_id = $newProject->id;
                    $newLog->save();
                }
            }

            if ($request->get('all') || $request->get('projectFiles')) {
                foreach ($originalProject->files ?? [] as $file) {
                    $newFile = $file->replicate();
                    $newFile->project_id = $newProject->id;
                    $newFile->save();
                }
            }

            return redirect()->route('project.index')->with('success', __('The project has been duplicated successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    public function invite(Request $request, Project $project)
    {
        if (Auth::user()->can('invite-project-member')) {
            $validated = $request->validate([
                'user_ids' => 'required|array|min:1',
                'user_ids.*' => 'exists:users,id',
            ]);

            $project->teamMembers()->syncWithoutDetaching($validated['user_ids']);

            ProjectInviteMember::dispatch($request, $project);

            // Log activity
            foreach ($validated['user_ids'] as $userId) {
                ActivityLog::create([
                    'user_id' => Auth::user()->id,
                    'user_type' => get_class(Auth::user()),
                    'project_id' => $project->id,
                    'log_type' => 'Invite User',
                    'remark' => json_encode(['user_id' => $userId]),
                ]);
            }

            return back()->with('success', __('User added to project successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function deleteMember(Request $request, Project $project)
    {
        if (Auth::user()->can('delete-project-member')) {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
            ]);

            $project->teamMembers()->detach($validated['user_id']);

            ProjectDeleteShareToClient::dispatch($request, $project);

            return back()->with('success', __('User removed from project successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function inviteClient(Request $request, Project $project)
    {
        if (Auth::user()->can('invite-project-client')) {
            $validated = $request->validate([
                'client_ids' => 'required|array|min:1',
                'client_ids.*' => 'exists:users,id',
            ]);

            $project->clients()->attach($validated['client_ids']);

            ProjectShareToClient::dispatch($request, $project);

            // Log activity
            foreach ($validated['client_ids'] as $clientId) {
                $client = User::find($clientId);
                if ($client) {
                    ActivityLog::create([
                        'user_id' => Auth::user()->id,
                        'user_type' => get_class(Auth::user()),
                        'project_id' => $project->id,
                        'log_type' => 'Share with Client',
                        'remark' => json_encode(['client_id' => $client->id]),
                    ]);
                }
            }

            return back()->with('success', __('Client added to project successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function deleteClient(Request $request, Project $project)
    {
        if (Auth::user()->can('delete-project-client')) {
            $validated = $request->validate([
                'client_id' => 'required|exists:users,id',
            ]);

            ProjectDeleteClient::dispatch($request, $project);

            $project->clients()->detach($validated['client_id']);

            return back()->with('success', __('Client removed from project successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function storeMilestone(StoreMilestoneRequest $request, Project $project)
    {
        if (Auth::user()->can('create-project-milestone')) {
            $validated = $request->validated();

            $milestone              = $project->milestones()->create($validated);
            $milestone->title       = $validated['title'];
            $milestone->cost        = $validated['cost'];
            $milestone->start_date  = $validated['start_date'];
            $milestone->end_date    = $validated['end_date'];
            $milestone->summary     = $validated['summary'];
            $milestone->project_id  = $project->id;
            $milestone->save();

            // Log activity
            ActivityLog::create(
            [
                'user_id' => Auth::user()->id,
                'user_type' => get_class(Auth::user()),
                'project_id' => $project->id,
                'log_type' => 'Create Milestone',
                'remark' => json_encode(['title' => $milestone->title]),
            ]);

            CreateProjectMilestone::dispatch($request, $milestone);

            return back()->with('success', __('The milestone has been created successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function updateMilestone(UpdateMilestoneRequest $request, Project $project)
    {
        if (Auth::user()->can('edit-project-milestone')) {
            $validated = $request->validated();
            $milestone = $project->milestones()->findOrFail($validated['milestone_id']);
            $milestone->title       = $validated['title'];
            $milestone->cost        = $validated['cost'];
            $milestone->start_date  = $validated['start_date'];
            $milestone->end_date    = $validated['end_date'];
            $milestone->summary     = $validated['summary'];
            $milestone->status      = $validated['status'];
            $milestone->progress    = $validated['progress'];
            $milestone->save();

            UpdateProjectMilestone::dispatch($request, $milestone);

            return back()->with('success', __('The milestone details are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function deleteMilestone(Request $request, Project $project)
    {
        if (Auth::user()->can('delete-project-milestone')) {
            $milestone = $project->milestones()->findOrFail($request->id);

            DestroyProjectMilestone::dispatch($milestone);

            $milestone->delete();
            return back()->with('success', __('The milestone has been deleted.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function storeFiles(Request $request, Project $project)
    {
        if (Auth::user()->can('edit-project')) {
            $request->validate([
                'images' => 'required|array',
                'images.*' => 'string'
            ]);

            foreach ($request->images as $imagePath) {
                $mediaFile = Media::where('file_name', basename($imagePath))->first();
                ProjectFile::create([
                    'project_id' => $project->id,
                    'file_name' => $mediaFile ? $mediaFile->name : basename($imagePath),
                    'file_path' => basename($imagePath),
                ]);
            }

            return back()->with('success', __('Files uploaded successfully.'));
        }
        return back()->with('error', __('Permission denied'));
    }

    public function deleteFile(ProjectFile $file)
    {
        if (Auth::user()->can('edit-project')) {
            $file->delete();
            return back()->with('success', __('The files has been deleted.'));
        }
        return back()->with('error', __('Permission denied'));
    }

    public function apiIndex()
    {
        if (Auth::user()->can('manage-project')) {
            $projects = Project::query()
                ->select('id', 'name')
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
                ->get();

            return response()->json($projects);
        }
        return response()->json(['error' => __('Permission denied')], 403);
    }
}
