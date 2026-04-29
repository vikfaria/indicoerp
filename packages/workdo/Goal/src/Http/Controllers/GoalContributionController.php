<?php

namespace Workdo\Goal\Http\Controllers;

use Workdo\Goal\Http\Requests\StoreContributionRequest;
use Workdo\Goal\Http\Requests\UpdateContributionRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Goal\Models\Goal;
use Workdo\Goal\Models\GoalContribution;
use Workdo\Goal\Events\CreateGoalContribution;
use Workdo\Goal\Events\UpdateGoalContribution;
use Workdo\Goal\Events\DestroyGoalContribution;
use Workdo\Goal\Services\GoalService;

class GoalContributionController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-goal-contributions')){
            $contributions = GoalContribution::query()
                ->with(['goal'])
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-goal-contributions')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-goal-contributions')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->whereHas('goal', function($q) {
                    if(request('goal_name')) {
                        $q->where('goal_name', 'like', '%' . request('goal_name') . '%');
                    }
                })
                ->when(request('goal_id'), fn($q) => $q->where('goal_id', request('goal_id')))
                ->when(request('contribution_type'), fn($q) => $q->where('contribution_type', request('contribution_type')))
                ->when(request('date_from'), fn($q) => $q->whereDate('contribution_date', '>=', request('date_from')))
                ->when(request('date_to'), fn($q) => $q->whereDate('contribution_date', '<=', request('date_to')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            $goals = Goal::where('created_by', creatorId())
                        ->where('status', 'active')
                        ->select('id', 'goal_name')
                        ->get();

            return Inertia::render('Goal/Contributions/Index', [
                'contributions' => $contributions,
                'goals' => $goals,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreContributionRequest $request)
    {
        if(Auth::user()->can('create-goal-contributions')){
            $validated = $request->validated();

            $goalService = new GoalService();
            $contributionId = $goalService->addGoalContribution($validated['goal_id'], $validated);
            $contribution = GoalContribution::find($contributionId);

            CreateGoalContribution::dispatch($request, $contribution);

            return redirect()->route('goal.contributions.index')->with('success', __('The contribution has been created successfully.'));
        }
        else{
            return redirect()->route('goal.contributions.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateContributionRequest $request, GoalContribution $contribution)
    {
        if(Auth::user()->can('edit-goal-contributions')){
            $validated = $request->validated();
            $oldAmount = $contribution->contribution_amount;

            $contribution->goal_id = $validated['goal_id'];
            $contribution->contribution_date = $validated['contribution_date'];
            $contribution->contribution_amount = $validated['contribution_amount'];
            $contribution->contribution_type = $validated['contribution_type'];
            $contribution->reference_type = $validated['reference_type'] ?? 'manual';
            $contribution->reference_id = $validated['reference_id'] ?? null;
            $contribution->notes = $validated['notes'] ?? '';
            $contribution->save();

            // Update goal current amount
            $goal = Goal::find($validated['goal_id']);
            $goal->current_amount = $goal->current_amount - $oldAmount + $validated['contribution_amount'];
            $goal->save();

            // Update tracking and check milestones
            $goalService = new GoalService();
            $goalService->updateGoalTracking($validated['goal_id']);
            $goalService->checkMilestoneAchievements($validated['goal_id']);

            UpdateGoalContribution::dispatch($request, $contribution);

            return back()->with('success', __('The contribution details are updated successfully.'));
        }
        else{
            return redirect()->route('goal.contributions.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(GoalContribution $contribution)
    {
        if(Auth::user()->can('delete-goal-contributions')){
            // Update goal current amount
            $goal = Goal::find($contribution->goal_id);
            $goal->current_amount -= $contribution->contribution_amount;
            $goal->save();

            DestroyGoalContribution::dispatch($contribution);

            $contribution->delete();

            return redirect()->back()->with('success', __('The contribution has been deleted.'));
        }
        else{
            return redirect()->route('goal.contributions.index')->with('error', __('Permission denied'));
        }
    }
}
