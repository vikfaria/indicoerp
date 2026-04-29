<?php

namespace Workdo\Performance\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Performance\Models\PerformanceGoalType;
use Workdo\Performance\Http\Requests\StoreGoalTypeRequest;
use Workdo\Performance\Http\Requests\UpdateGoalTypeRequest;
use Workdo\Performance\Events\CreateGoalType;
use Workdo\Performance\Events\UpdateGoalType;
use Workdo\Performance\Events\DestroyGoalType;

class GoalTypeController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-goal-types')) {
            $goalTypes = PerformanceGoalType::select('id', 'name', 'description', 'status', 'created_at')
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-goal-types')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-goal-types')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('Performance/SystemSetup/GoalTypes/Index', [
                'goalTypes' => $goalTypes,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreGoalTypeRequest $request)
    {
        if (Auth::user()->can('create-goal-types')) {
            $validated = $request->validated();
            $goalType = new PerformanceGoalType();
            $goalType->name = $validated['name'];
            $goalType->description = $validated['description'] ?? null;
            $goalType->status = $validated['status'] ?? 'active';
            $goalType->creator_id = Auth::id();
            $goalType->created_by = creatorId();
            $goalType->save();

            CreateGoalType::dispatch($request, $goalType);

            return back()->with('success', __('The goal type has been created successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateGoalTypeRequest $request, PerformanceGoalType $goalType)
    {
        if (Auth::user()->can('edit-goal-types')) {
            if (!$this->canAccessGoalType($goalType)) {
                return back()->with('error', __('Permission denied'));
            }

            $validated = $request->validated();
            
            $goalType->name = $validated['name'];
            $goalType->description = $validated['description'] ?? null;
            $goalType->status = $validated['status'] ?? 'active';
            $goalType->save();

            UpdateGoalType::dispatch($request, $goalType);

            return back()->with('success', __('The goal type details are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(PerformanceGoalType $goalType)
    {
        if (Auth::user()->can('delete-goal-types')) {
            if (!$this->canAccessGoalType($goalType)) {
                return back()->with('error', __('Permission denied'));
            }

            DestroyGoalType::dispatch($goalType);

            $goalType->delete();

            return back()->with('success', __('The goal type has been deleted.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    private function canAccessGoalType(PerformanceGoalType $goalType)
    {
        if (Auth::user()->can('manage-any-goal-types')) {
            return $goalType->created_by == creatorId();
        } elseif (Auth::user()->can('manage-own-goal-types')) {
            return $goalType->creator_id == Auth::id();
        } else {
            return false;
        }
    }
}