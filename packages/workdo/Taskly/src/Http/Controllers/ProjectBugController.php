<?php

namespace Workdo\Taskly\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;
use Workdo\Taskly\Events\CreateBugComment;
use Workdo\Taskly\Events\CreateProjectBug;
use Workdo\Taskly\Events\DestroyBugComment;
use Workdo\Taskly\Events\DestroyProjectBug;
use Workdo\Taskly\Events\UpdateProjectBug;
use Workdo\Taskly\Events\UpdateProjectBugStage;
use Workdo\Taskly\Models\ActivityLog;
use Workdo\Taskly\Models\Project;
use Workdo\Taskly\Models\BugStage;
use Workdo\Taskly\Models\ProjectBug;
use Workdo\Taskly\Models\BugComment;
use Workdo\Taskly\Http\Requests\StoreProjectBugRequest;
use Workdo\Taskly\Http\Requests\UpdateProjectBugRequest;

class ProjectBugController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-project-bug')) {
            $query = ProjectBug::with(['project:id,name', 'bugStage:id,name'])
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-project-bug')) {
                        // Admin can see all bugs
                        $q->where('created_by', creatorId());
                    } else if (Auth::user()->can('manage-own-project-bug')) {
                        // Show bugs user created OR assigned to
                        $q->where('created_by', creatorId())
                          ->where(function($subQ) {
                            $subQ->where('creator_id', Auth::id())
                                 ->orWhereJsonContains('assigned_to', (string)Auth::id());
                        });
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                });

            if ($request->has('project_id') && $request->project_id) {
                $query->where('project_id', $request->project_id);
                $project = Project::findOrFail($request->project_id);
            } else {
                $project = null;
            }

            if ($request->filled('title')) {
                $query->where('title', 'like', '%' . $request->title . '%');
            }

            if ($request->filled('priority')) {
                $query->where('priority', $request->priority);
            }

            $sortField = $request->get('sort', 'created_at');
            $sortDirection = $request->get('direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            $perPage = $request->get('per_page', 10);
            $bugs = $query->paginate($perPage);

            $bugs->getCollection()->transform(function ($bug) {
                $assignedUsers = [];
                if ($bug->assigned_to) {
                    $userIds = is_array($bug->assigned_to) ? $bug->assigned_to : json_decode($bug->assigned_to, true);
                    $assignedUsers = User::whereIn('id', $userIds)->select('id', 'name', 'avatar')->get();
                }
                $bug->assignedUsers = $assignedUsers;
                $bug->assignedUser = $assignedUsers->first();
                return $bug;
            });

            $teamMembers = [];
            $bugStages = BugStage::where('created_by', creatorId())->orderBy('order')->get();

            if ($project) {
                $teamMembers = $project->teamMembers()->select('users.id', 'users.name', 'users.avatar')->get();
            }

            return Inertia::render('Taskly/Bugs/Index', [
                'bugs' => $bugs,
                'project' => $project,
                'teamMembers' => $teamMembers,
                'bugStages' => $bugStages
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function kanban($projectId)
    {
        if (Auth::user()->can('manage-project-bug')) {
            $project = Project::with(['teamMembers:id,name,avatar'])->findOrFail($projectId);
            $stages = BugStage::where('created_by', creatorId())->orderBy('order')->get();
            $teamMembers = $project->teamMembers()->select('users.id', 'users.name', 'users.avatar')->get();

            // Get bugs organized by stages
            $query = ProjectBug::where('project_id', $projectId)
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-project-bug')) {
                        $q->where('created_by', creatorId());
                    } else {
                        // Show bugs user created OR assigned to
                        $q->where('created_by', creatorId())
                          ->where(function($subQ) {
                            $subQ->where('creator_id', Auth::id())
                                 ->orWhereJsonContains('assigned_to', (string)Auth::id());
                        });
                    }
                });

            $allBugs = $query->get();

            $bugs = [];
            foreach ($stages as $stage) {
                $stageKey = strtolower(str_replace(' ', '-', $stage->name));
                $stageBugs = $allBugs->where('stage_id', $stage->id)->map(function($bug) {
                    $assignedUsers = [];
                    if ($bug->assigned_to) {
                        $userIds = is_array($bug->assigned_to) ? $bug->assigned_to : json_decode($bug->assigned_to, true);
                        $assignedUsers = \App\Models\User::whereIn('id', $userIds)->select('id', 'name', 'avatar')->get();
                    }
                    return [
                        'id' => $bug->id,
                        'title' => $bug->title,
                        'description' => $bug->description,
                        'priority' => $bug->priority,
                        'assigned_users' => $assignedUsers,
                        'created_at' => $bug->created_at->format('Y-m-d')
                    ];
                })->values()->toArray();

                $bugs[$stageKey] = $stageBugs;
            }

            return Inertia::render('Taskly/Bugs/Kanban', [
                'project' => $project,
                'stages' => $stages,
                'bugs' => $bugs,
                'teamMembers' => $teamMembers,
                'bugStages' => $stages,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreProjectBugRequest $request)
    {
        if (Auth::user()->can('create-project-bug')) {
            $validated = $request->validated();

            $bug = new ProjectBug();
            $bug->project_id = $validated['project_id'];
            $bug->title = $validated['title'];
            $bug->priority = $validated['priority'];
            $bug->assigned_to =  is_array($validated['assigned_to']) ? json_encode($validated['assigned_to']) : $validated['assigned_to'];
            $bug->stage_id = $validated['stage_id'];
            $bug->description = $validated['description'];
            $bug->creator_id = Auth::id();
            $bug->created_by = creatorId();
            $bug->save();

            ActivityLog::create(
            [
                'user_id' => Auth::user()->id,
                'user_type' => get_class(Auth::user()),
                'project_id' => $bug->project_id,
                'log_type' => 'Create Bug',
                'remark' => json_encode(['title' => $bug->title]),
            ]);

            CreateProjectBug::dispatch($request, $bug);
            return response()->json(['success' => true, 'message' => __('The bug has been created successfully.')]);
        }
        return response()->json(['error' => __('Permission denied')], 403);
    }

    public function show(ProjectBug $bug)
    {
        // Check if user can view this specific bug
        $assignedUserIds = $bug->assigned_to ? (is_array($bug->assigned_to) ? $bug->assigned_to : json_decode($bug->assigned_to, true)) : [];
        $canView = Auth::user()->can('view-project-bug') ||
                   ($bug->creator_id == Auth::id()) ||
                   in_array((string)Auth::id(), $assignedUserIds);

        if (Auth::user()->can('view-project-bug') && $canView) {
            $bug->load(['project:id,name', 'bugStage:id,name,color']);

            $assignedUsers = [];
            if ($bug->assigned_to) {
                $userIds = is_array($bug->assigned_to) ? $bug->assigned_to : json_decode($bug->assigned_to, true);
                $assignedUsers = User::whereIn('id', $userIds)->select('id', 'name', 'avatar')->get();
            }

            return response()->json([
                'bug' => [
                    'id' => $bug->id,
                    'title' => $bug->title,
                    'priority' => $bug->priority,
                    'assigned_to' => $bug->assigned_to,
                    'stage_id' => $bug->stage_id,
                    'description' => $bug->description,
                    'project' => $bug->project,
                    'stage' => $bug->bugStage,
                    'assignedUsers' => $assignedUsers,
                    'created_at' => $bug->created_at
                ]
            ]);
        } else {
            return response()->json(['error' => __('Permission denied')], 403);
        }
    }

    public function update(UpdateProjectBugRequest $request, ProjectBug $bug)
    {
        // Check if user can edit this specific bug
        $assignedUserIds = $bug->assigned_to ? (is_array($bug->assigned_to) ? $bug->assigned_to : json_decode($bug->assigned_to, true)) : [];
        $canEdit = Auth::user()->can('edit-project-bug') ||
                   ($bug->creator_id == Auth::id()) ||
                   in_array((string)Auth::id(), $assignedUserIds);

        if (Auth::user()->can('edit-project-bug') && $canEdit) {
            $validated = $request->validated();

            $bug->update([
                'title' => $validated['title'],
                'priority' => $validated['priority'],
                'assigned_to' => isset($validated['assigned_to']) ? (is_array($validated['assigned_to']) ? json_encode($validated['assigned_to']) : $validated['assigned_to']) : null,
                'stage_id' => $validated['stage_id'],
                'description' => $validated['description'],
            ]);

            UpdateProjectBug::dispatch($request, $bug);

            return response()->json(['success' => true, 'message' => __('The bug has been updated successfully.')]);
        } else{
            return response()->json(['error' => __('Permission denied')], 403);
        }
    }

    public function destroy(ProjectBug $bug)
    {
        // Only creator or admin can delete bugs
        $canDelete = Auth::user()->can('delete-project-bug') ||
                     ($bug->creator_id == Auth::id());

        if (Auth::user()->can('delete-project-bug') && $canDelete) {

            DestroyProjectBug::dispatch($bug);

            $bug->delete();
            return response()->json(['success' => true, 'message' => __('The bug has been deleted.')]);
        }
        else{
            return response()->json(['error' => __('Permission denied')], 403);
        }
    }

    public function move(Request $request, ProjectBug $bug)
    {
        // Check if user can edit bug OR is assigned to the bug
        $assignedUserIds = $bug->assigned_to ? (is_array($bug->assigned_to) ? $bug->assigned_to : json_decode($bug->assigned_to, true)) : [];
        $canMove = Auth::user()->can('edit-project-bug') ||
                   in_array((string)Auth::id(), $assignedUserIds) ||
                   $bug->creator_id == Auth::id();

        if ($canMove) {
            $request->validate([
                'stage_id' => 'required|exists:bug_stages,id'
            ]);

            $oldStage = BugStage::find($bug->stage_id);
            $newStage = BugStage::find($request->stage_id);

            $bug->update(['stage_id' => $request->stage_id]);

            ActivityLog::create([
                'user_id' => Auth::user()->id,
                'user_type' => get_class(Auth::user()),
                'project_id' => $bug->project_id,
                'log_type' => 'Move Bug',
                'remark' => json_encode([
                    'title' => $bug->title,
                    'old_status' => $oldStage ? $oldStage->name : 'Unknown',
                    'new_status' => $newStage ? $newStage->name : 'Unknown',
                ]),
            ]);

            UpdateProjectBugStage::dispatch($request, $bug);

            return response()->json(['success' => true, 'message' => __('The bug has been moved successfully.')]);
        } else {
            return response()->json(['error' => __('Permission denied')], 403);
        }
    }

    public function getBugs($projectId)
    {
        if (Auth::user()->can('manage-project-bug') || Auth::user()->can('view-project-bug')) {
            $stages = BugStage::where('created_by', creatorId())->orderBy('order')->get();

            $query = ProjectBug::where('project_id', $projectId)
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-project-bug')) {
                        $q->where('created_by', creatorId());
                        // Admin can see all bugs
                    } else {
                        // Show bugs user created OR assigned to
                        $q->where('created_by', creatorId())
                          ->where(function($subQ) {
                            $subQ->where('creator_id', Auth::id())
                                 ->orWhereJsonContains('assigned_to', (string)Auth::id());
                        });
                    }
                });

            $allBugs = $query->get();

            $bugs = [];
            foreach ($stages as $stage) {
                $stageKey = strtolower(str_replace(' ', '-', $stage->name));
                $stageBugs = $allBugs->where('stage_id', $stage->id)->map(function($bug) {
                    $assignedUsers = [];
                    if ($bug->assigned_to) {
                        $userIds = is_array($bug->assigned_to) ? $bug->assigned_to : json_decode($bug->assigned_to, true);
                        $assignedUsers = \App\Models\User::whereIn('id', $userIds)->select('id', 'name', 'avatar')->get();
                    }
                    return [
                        'id' => $bug->id,
                        'title' => $bug->title,
                        'description' => $bug->description,
                        'priority' => $bug->priority,
                        'assigned_users' => $assignedUsers,
                        'created_at' => $bug->created_at->format('Y-m-d')
                    ];
                })->values()->toArray();
                $bugs[$stageKey] = $stageBugs;
            }

            return response()->json(['bugs' => $bugs]);
        }
        return response()->json(['error' => __('Permission denied')], 403);
    }

    public function getComments(ProjectBug $bug)
    {
        $assignedUserIds = $bug->assigned_to ? (is_array($bug->assigned_to) ? $bug->assigned_to : json_decode($bug->assigned_to, true)) : [];
        $canView = Auth::user()->can('manage-project-bug-comments') ||
                   ($bug->creator_id == Auth::id()) ||
                   in_array((string)Auth::id(), $assignedUserIds);

        if (Auth::user()->can('manage-project-bug-comments') && $canView) {
            $comments = BugComment::where('bug_id', $bug->id)
                ->with('user:id,name,avatar')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['comments' => $comments]);
        } else {
            return response()->json(['error' => __('Permission denied')], 403);
        }
    }

    public function storeComment(Request $request, ProjectBug $bug)
    {
        $assignedUserIds = $bug->assigned_to ? (is_array($bug->assigned_to) ? $bug->assigned_to : json_decode($bug->assigned_to, true)) : [];
        $canComment = Auth::user()->can('create-project-bug-comments') ||
                      ($bug->creator_id == Auth::id()) ||
                      in_array((string)Auth::id(), $assignedUserIds);
        if (Auth::user()->can('create-project-bug-comments') && $canComment) {
            $validated = $request->validate([
                'comment' => 'required|string'
            ]);

            $comment = BugComment::create([
                'bug_id' => $bug->id,
                'user_id' => Auth::id(),
                'comment' => $validated['comment'],
            ]);

            CreateBugComment::dispatch($request, $comment);

            return response()->json(['success' => true, 'message' => __('The comment has been added successfully.')]);
        } else {
            return response()->json(['error' =>  __('Permission denied')], 403);
        }
    }

    public function destroyComment(BugComment $comment)
    {
        if (Auth::user()->can('delete-project-bug-comments')) {
            DestroyBugComment::dispatch($comment);

            $comment->delete();
            return response()->json(['success' => true, 'message' => __('The comment has been deleted.')]);
        } else {
            return response()->json(['error' => __('Permission denied')], 403);
        }
    }
}