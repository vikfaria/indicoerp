<?php

namespace Workdo\Performance\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Performance\Models\PerformanceIndicatorCategory;
use Workdo\Performance\Http\Requests\StorePerformanceIndicatorCategoryRequest;
use Workdo\Performance\Http\Requests\UpdatePerformanceIndicatorCategoryRequest;
use Workdo\Performance\Events\CreatePerformanceIndicatorCategory;
use Workdo\Performance\Events\UpdatePerformanceIndicatorCategory;
use Workdo\Performance\Events\DestroyPerformanceIndicatorCategory;

class PerformanceIndicatorCategoryController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-performance-indicator-categories')) {
            $categories = PerformanceIndicatorCategory::select('id', 'name', 'description', 'status', 'created_at')
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-performance-indicator-categories')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-performance-indicator-categories')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('Performance/SystemSetup/IndicatorCategories/Index', [
                'categories' => $categories,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StorePerformanceIndicatorCategoryRequest $request)
    {
        if (Auth::user()->can('create-performance-indicator-categories')) {
            $validated = $request->validated();
            $indicatorCategory = new PerformanceIndicatorCategory();
            $indicatorCategory->name = $validated['name'];
            $indicatorCategory->description = $validated['description'] ?? null;
            $indicatorCategory->status = $validated['status'] ?? 'active';
            $indicatorCategory->creator_id = Auth::id();
            $indicatorCategory->created_by = creatorId();
            $indicatorCategory->save();

            CreatePerformanceIndicatorCategory::dispatch($request, $indicatorCategory);

            return back()->with('success', __('The indicator category has been created successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function update(UpdatePerformanceIndicatorCategoryRequest $request, PerformanceIndicatorCategory $indicatorCategory)
    {
        if (Auth::user()->can('edit-performance-indicator-categories')) {
            if (!$this->canAccessCategory($indicatorCategory)) {
                return back()->with('error', __('Permission denied'));
            }

            $validated = $request->validated();

            $indicatorCategory->name = $validated['name'];
            $indicatorCategory->description = $validated['description'] ?? null;
            $indicatorCategory->status = $validated['status'] ?? 'active';
            $indicatorCategory->save();

            UpdatePerformanceIndicatorCategory::dispatch($request, $indicatorCategory);

            return back()->with('success', __('The indicator category details are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(PerformanceIndicatorCategory $indicatorCategory)
    {
        if (Auth::user()->can('delete-performance-indicator-categories')) {
            if (!$this->canAccessCategory($indicatorCategory)) {
                return back()->with('error', __('Permission denied'));
            }

            DestroyPerformanceIndicatorCategory::dispatch($indicatorCategory);

            $indicatorCategory->delete();

            return back()->with('success', __('The indicator category has been deleted.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    private function canAccessCategory(PerformanceIndicatorCategory $category)
    {
        if (Auth::user()->can('manage-any-performance-indicator-categories')) {
            return $category->created_by == creatorId();
        } elseif (Auth::user()->can('manage-own-performance-indicator-categories')) {
            return $category->creator_id == Auth::id();
        } else {
            return false;
        }
    }
}
