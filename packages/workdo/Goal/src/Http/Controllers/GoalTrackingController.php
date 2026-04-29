<?php

namespace Workdo\Goal\Http\Controllers;

use Workdo\Goal\Http\Requests\StoreTrackingRequest;
use Workdo\Goal\Http\Requests\UpdateTrackingRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Goal\Models\Goal;
use Workdo\Goal\Models\GoalTracking;
use Workdo\Goal\Events\CreateGoalTracking;
use Workdo\Goal\Events\UpdateGoalTracking;
use Workdo\Goal\Events\DestroyGoalTracking;

class GoalTrackingController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-goal-tracking')){
            $trackings = GoalTracking::query()
                ->with('goal')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-goal-tracking')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-goal-tracking')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('goal_id'), fn($q) => $q->where('goal_id', request('goal_id')))
                ->when(request('on_track_status'), fn($q) => $q->where('on_track_status', request('on_track_status')))
                ->when(request('date_from'), fn($q) => $q->whereDate('tracking_date', '>=', request('date_from')))
                ->when(request('date_to'), fn($q) => $q->whereDate('tracking_date', '<=', request('date_to')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            $goals = Goal::where('created_by', creatorId())->where('status', 'active')->select('id', 'goal_name')->get();

            return Inertia::render('Goal/Tracking/Index', [
                'trackings' => $trackings,
                'goals' => $goals,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreTrackingRequest $request)
    {
        if(Auth::user()->can('create-goal-tracking')){
            $validated = $request->validated();

            $tracking = new GoalTracking();
            $tracking->goal_id = $validated['goal_id'];
            $tracking->tracking_date = $validated['tracking_date'];
            $tracking->previous_amount = $validated['previous_amount'];
            $tracking->contribution_amount = $validated['contribution_amount'];
            $tracking->current_amount = $validated['current_amount'];
            $tracking->progress_percentage = $validated['progress_percentage'];
            $tracking->days_remaining = $validated['days_remaining'];
            $tracking->projected_completion_date = $validated['projected_completion_date'] ?? null;
            $tracking->on_track_status = $validated['on_track_status'];
            $tracking->creator_id = Auth::id();
            $tracking->created_by = creatorId();
            $tracking->save();

            CreateGoalTracking::dispatch($request, $tracking);

            return redirect()->route('goal.tracking.index')->with('success', __('The tracking has been created successfully.'));
        }
        else{
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateTrackingRequest $request, GoalTracking $goalTracking)
    {
        if(Auth::user()->can('edit-goal-tracking')){
            $validated = $request->validated();

            if(isset($validated['goal_id'])) {
                $goalTracking->goal_id = $validated['goal_id'];
            }
            $goalTracking->tracking_date = $validated['tracking_date'];
            $goalTracking->previous_amount = $validated['previous_amount'];
            $goalTracking->contribution_amount = $validated['contribution_amount'];
            $goalTracking->current_amount = $validated['current_amount'];
            $goalTracking->progress_percentage = $validated['progress_percentage'];
            $goalTracking->days_remaining = $validated['days_remaining'];
            $goalTracking->projected_completion_date = $validated['projected_completion_date'] ?? null;
            $goalTracking->on_track_status = $validated['on_track_status'];
            $goalTracking->save();

            UpdateGoalTracking::dispatch($request, $goalTracking);

            return back()->with('success', __('The tracking has been updated successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function show(GoalTracking $goalTracking)
    {
        if(Auth::user()->can('view-goal-tracking') && $goalTracking->created_by == creatorId()){
            $goalTracking->load('goal');

            return Inertia::render('Goal/Tracking/View', [
                'tracking' => $goalTracking,
            ]);
        }
        else{
            return redirect()->route('goal.tracking.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(GoalTracking $goalTracking)
    {
        if(Auth::user()->can('delete-goal-tracking')){
            DestroyGoalTracking::dispatch($goalTracking);

            $goalTracking->delete();

            return redirect()->route('goal.tracking.index')->with('success', __('The tracking has been deleted.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }
}
