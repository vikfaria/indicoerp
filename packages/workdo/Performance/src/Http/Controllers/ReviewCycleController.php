<?php

namespace Workdo\Performance\Http\Controllers;

use Illuminate\Routing\Controller;
use Workdo\Performance\Models\PerformanceReviewCycle;
use Workdo\Performance\Http\Requests\StoreReviewCycleRequest;
use Workdo\Performance\Http\Requests\UpdateReviewCycleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Performance\Events\CreateReviewCycle;
use Workdo\Performance\Events\UpdateReviewCycle;
use Workdo\Performance\Events\DestroyReviewCycle;

class ReviewCycleController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-review-cycles')) {
            $reviewCycles = PerformanceReviewCycle::with(['createdBy'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-review-cycles')) {
                        $q->where('performance_review_cycles.created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-review-cycles')) {
                        $q->where(function ($query) {
                            $query->where('performance_review_cycles.creator_id', Auth::id());
                        });
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('name'), fn($q) => $q->where('performance_review_cycles.name', 'like', '%' . request('name') . '%'))
                ->when(request('frequency'), fn($q) => $q->where('performance_review_cycles.frequency', request('frequency')))
                ->when(request('status') !== null, fn($q) => $q->where('performance_review_cycles.status', request('status')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('Performance/ReviewCycles/Index', [
                'reviewCycles' => $reviewCycles,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreReviewCycleRequest $request)
    {
        if (Auth::user()->can('create-review-cycles')) {
            $validated = $request->validated();
            $reviewCycle = new PerformanceReviewCycle();
            $reviewCycle->name = $validated['name'];
            $reviewCycle->frequency = $validated['frequency'];
            $reviewCycle->description = $validated['description'] ?? null;
            $reviewCycle->status = $validated['status'] ?? 'active';
            $reviewCycle->creator_id = Auth::id();
            $reviewCycle->created_by = creatorId();
            $reviewCycle->save();

            CreateReviewCycle::dispatch($request, $reviewCycle);

            return back()->with('success', __('The review cycle has been created successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateReviewCycleRequest $request, PerformanceReviewCycle $reviewCycle)
    {
        if (Auth::user()->can('edit-review-cycles')) {
            if (!$this->canAccessReviewCycle($reviewCycle)) {
                return back()->with('error', __('Permission denied'));
            }

            $validated = $request->validated();

            $reviewCycle->name = $validated['name'];
            $reviewCycle->frequency = $validated['frequency'];
            $reviewCycle->description = $validated['description'] ?? null;
            $reviewCycle->status = $validated['status'] ?? 'active';
            $reviewCycle->save();

            UpdateReviewCycle::dispatch($request, $reviewCycle);

            return back()->with('success', __('The review cycle details are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function show(PerformanceReviewCycle $reviewCycle)
    {
        if (Auth::user()->can('view-review-cycles')) {
            if (!$this->canAccessReviewCycle($reviewCycle)) {
                return back()->with('error', __('Permission denied'));
            }

            $data = [
                'reviewCycle' => $reviewCycle->load(['creator', 'createdBy', 'employeeReviews.user', 'employeeReviews.reviewer']),
            ];

            if (request()->wantsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['props' => $data]);
            }

            return Inertia::render('Performance/ReviewCycles/Show', $data);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(PerformanceReviewCycle $reviewCycle)
    {
        if (Auth::user()->can('delete-review-cycles')) {
            if (!$this->canAccessReviewCycle($reviewCycle)) {
                return back()->with('error', __('Permission denied'));
            }

            DestroyReviewCycle::dispatch($reviewCycle);

            $reviewCycle->delete();

            return back()->with('success', __('The review cycle has been deleted.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    private function canAccessReviewCycle(PerformanceReviewCycle $reviewCycle)
    {
        if (Auth::user()->can('manage-any-review-cycles')) {
            return $reviewCycle->created_by == creatorId();
        } elseif (Auth::user()->can('manage-own-review-cycles')) {
            return $reviewCycle->creator_id == Auth::id();
        } else {
            return false;
        }
    }
}