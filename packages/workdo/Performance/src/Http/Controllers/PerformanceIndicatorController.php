<?php

namespace Workdo\Performance\Http\Controllers;

use Illuminate\Routing\Controller;
use Workdo\Performance\Models\PerformanceIndicator;
use Workdo\Performance\Models\PerformanceIndicatorCategory;
use Workdo\Performance\Http\Requests\StorePerformanceIndicatorRequest;
use Workdo\Performance\Http\Requests\UpdatePerformanceIndicatorRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Performance\Events\CreatePerformanceIndicator;
use Workdo\Performance\Events\UpdatePerformanceIndicator;
use Workdo\Performance\Events\DestroyPerformanceIndicator;

class PerformanceIndicatorController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-performance-indicators')) {
            $indicators = PerformanceIndicator::with('category')
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-performance-indicators')) {
                        $q->where('performance_indicators.created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-performance-indicators')) {
                        $q->where(function ($query) {
                            $query->where('performance_indicators.creator_id', Auth::id());
                        });
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('name'), fn($q) => $q->where('performance_indicators.name', 'like', '%' . request('name') . '%'))
                ->when(request('category_id'), fn($q) => $q->where('performance_indicators.category_id', request('category_id')))
                ->when(request('measurement_unit'), fn($q) => $q->where('performance_indicators.measurement_unit', 'like', '%' . request('measurement_unit') . '%'))
                ->when(request('status') !== null, fn($q) => $q->where('performance_indicators.status', request('status')))
                ->when(request('sort'), function ($q) {
                    $sort = request('sort');
                    $direction = request('direction', 'asc');

                    if ($sort === 'category') {
                        return $q->join('performance_indicator_categories', 'performance_indicators.category_id', '=', 'performance_indicator_categories.id')
                            ->orderBy('performance_indicator_categories.name', $direction)
                            ->select('performance_indicators.*');
                    }

                    return $q->orderBy($sort, $direction);
                }, fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            $categories = $this->getFilteredIndicatorCategories();

            return Inertia::render('Performance/Indicators/Index', [
                'indicators' => $indicators,
                'categories' => $categories,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StorePerformanceIndicatorRequest $request)
    {
        if (Auth::user()->can('create-performance-indicators')) {
            $validated = $request->validated();
            $indicator = new PerformanceIndicator();
            $indicator->category_id = $validated['category_id'];
            $indicator->name = $validated['name'];
            $indicator->description = $validated['description'] ?? null;
            $indicator->measurement_unit = $validated['measurement_unit'];
            $indicator->target_value = $validated['target_value'] ?? null;
            $indicator->status = $validated['status'] ?? 'active';
            $indicator->creator_id = Auth::id();
            $indicator->created_by = creatorId();
            $indicator->save();

            CreatePerformanceIndicator::dispatch($request, $indicator);

            return back()->with('success', __('The performance indicator has been created successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function update(UpdatePerformanceIndicatorRequest $request, PerformanceIndicator $indicator)
    {
        if (Auth::user()->can('edit-performance-indicators')) {
            if (!$this->canAccessIndicator($indicator)) {
                return back()->with('error', __('Permission denied'));
            }

            $validated = $request->validated();

            $indicator->category_id = $validated['category_id'];
            $indicator->name = $validated['name'];
            $indicator->description = $validated['description'] ?? null;
            $indicator->measurement_unit = $validated['measurement_unit'];
            $indicator->target_value = $validated['target_value'] ?? null;
            $indicator->status = $validated['status'] ?? 'active';
            $indicator->save();

            UpdatePerformanceIndicator::dispatch($request, $indicator);

            return back()->with('success', __('The performance indicator details are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(PerformanceIndicator $indicator)
    {
        if (Auth::user()->can('delete-performance-indicators')) {
            if (!$this->canAccessIndicator($indicator)) {
                return back()->with('error', __('Permission denied'));
            }

            DestroyPerformanceIndicator::dispatch($indicator);

            $indicator->delete();

            return back()->with('success', __('The performance indicator has been deleted.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    private function canAccessIndicator(PerformanceIndicator $indicator)
    {
        if (Auth::user()->can('manage-any-performance-indicators')) {
            return $indicator->created_by == creatorId();
        } elseif (Auth::user()->can('manage-own-performance-indicators')) {
            return $indicator->creator_id == Auth::id();
        } else {
            return false;
        }
    }

    private function getFilteredIndicatorCategories()
    {
        return PerformanceIndicatorCategory::where('created_by', creatorId())
            ->where('status', 'active')
            ->when(!Auth::user()->can('manage-any-performance-indicator-categories'), function ($q) {
                if (Auth::user()->can('manage-own-performance-indicator-categories')) {
                    $q->where('creator_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->select('id', 'name')->get();
    }
}