<?php

namespace Workdo\Goal\Http\Controllers;

use Workdo\Goal\Http\Requests\StoreMilestoneRequest;
use Workdo\Goal\Http\Requests\UpdateMilestoneRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Goal\Models\Goal;
use Workdo\Goal\Models\GoalMilestone;
use Workdo\Goal\Events\CreateGoalMilestone;
use Workdo\Goal\Events\UpdateGoalMilestone;
use Workdo\Goal\Events\DestroyGoalMilestone;

class GoalMilestoneController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-goal-milestones')){
            $milestones = GoalMilestone::query()
                ->with('goal')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-goal-milestones')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-goal-milestones')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('milestone_name'), function($q) {
                    $q->where('milestone_name', 'like', '%' . request('milestone_name') . '%');
                })
                ->when(request('status'), fn($q) => $q->where('status', request('status')))
                ->when(request('goal_id'), fn($q) => $q->where('goal_id', request('goal_id')))
                ->when(request('date_from'), fn($q) => $q->whereDate('target_date', '>=', request('date_from')))
                ->when(request('date_to'), fn($q) => $q->whereDate('target_date', '<=', request('date_to')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')),fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            $goals = Goal::where('created_by', creatorId())
                        ->where('status', 'active')
                        ->select('id', 'goal_name')
                        ->get();

            return Inertia::render('Goal/Milestones/Index', [
                'milestones' => $milestones,
                'goals' => $goals,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreMilestoneRequest $request)
    {
        if(Auth::user()->can('create-goal-milestones')){
            $validated = $request->validated();

            $milestone = new GoalMilestone();
            $milestone->goal_id = $validated['goal_id'];
            $milestone->milestone_name = $validated['milestone_name'];
            $milestone->milestone_description = $validated['milestone_description'];
            $milestone->target_amount = $validated['target_amount'];
            $milestone->target_date = $validated['target_date'];
            $milestone->achieved_amount = 0;
            $milestone->status = 'pending';
            $milestone->creator_id = Auth::id();
            $milestone->created_by = creatorId();
            $milestone->save();

            CreateGoalMilestone::dispatch($request, $milestone);

            return redirect()->route('goal.milestones.index')->with('success', __('The milestone has been created successfully.'));
        }
        else{
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateMilestoneRequest $request, GoalMilestone $milestone)
    {
        if(Auth::user()->can('edit-goal-milestones')){
            $validated = $request->validated();

            if(isset($validated['goal_id'])) {
                $milestone->goal_id = $validated['goal_id'];
            }
            $milestone->milestone_name = $validated['milestone_name'];
            $milestone->milestone_description = $validated['milestone_description'];
            $milestone->target_amount = $validated['target_amount'];
            $milestone->target_date = $validated['target_date'];
            $milestone->achieved_amount = $validated['achieved_amount'] ?? 0;
            $milestone->status = $validated['status'];
            $milestone->save();

            UpdateGoalMilestone::dispatch($request, $milestone);

            return back()->with('success', __('The milestone details are updated successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function show(GoalMilestone $milestone)
    {
        if(Auth::user()->can('view-goal-milestones') && $milestone->created_by == creatorId()){
            $milestone->load('goal');
            $milestone->makeVisible(['achieved_amount']);

            return Inertia::render('Goal/Milestones/View', [
                'milestone' => $milestone,
            ]);
        }
        else{
            return redirect()->route('goal.milestones.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(GoalMilestone $milestone)
    {
        if(Auth::user()->can('delete-goal-milestones')){
            DestroyGoalMilestone::dispatch($milestone);

            $milestone->delete();

            return redirect()->route('goal.milestones.index')->with('success', __('The milestone has been deleted.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }
}
