<?php

namespace Workdo\Goal\Http\Controllers;

use Workdo\Goal\Http\Requests\StoreCategoryRequest;
use Workdo\Goal\Http\Requests\UpdateCategoryRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Goal\Models\GoalCategory;
use Workdo\Goal\Events\CreateGoalCategory;
use Workdo\Goal\Events\UpdateGoalCategory;
use Workdo\Goal\Events\DestroyGoalCategory;

class GoalCategoryController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-categories')){
            $categories = GoalCategory::query()

                ->where(function($q) {
                    if(Auth::user()->can('manage-any-categories')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-categories')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('category_name'), function($q) {
                    $q->where(function($query) {
                    $query->where('category_name', 'like', '%' . request('category_name') . '%');
                    $query->orWhere('category_code', 'like', '%' . request('category_name') . '%');
                    });
                })
                ->when(request('is_active') !== null && request('is_active') !== '', fn($q) => $q->where('is_active', request('is_active') === '1' ? 1 : 0))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('Goal/Categories/Index', [
                'categories' => $categories,

            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreCategoryRequest $request)
    {
        if(Auth::user()->can('create-categories')){
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', true);

            $category = new GoalCategory();
            $category->category_name = $validated['category_name'];
            $category->category_code = $validated['category_code'];
            $category->description = $validated['description'];
            $category->is_active = $validated['is_active'];
            $category->creator_id = Auth::id();
            $category->created_by = creatorId();
            $category->save();

            CreateGoalCategory::dispatch($request, $category);

            return redirect()->route('goal.categories.index')->with('success', __('The category has been created successfully.'));
        }
        else{
            return redirect()->route('goal.categories.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateCategoryRequest $request, GoalCategory $category)
    {
        if(Auth::user()->can('edit-categories')){
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', true);


            $category->category_name = $validated['category_name'];
            $category->category_code = $validated['category_code'];
            $category->description = $validated['description'];
            $category->is_active = $validated['is_active'];
            $category->save();

            UpdateGoalCategory::dispatch($request, $category);

            return back()->with('success', __('The category details are updated successfully.'));
        }
        else{
            return redirect()->route('goal.categories.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(GoalCategory $category)
    {
        if(Auth::user()->can('delete-categories')){
            DestroyGoalCategory::dispatch($category);

            $category->delete();

            return redirect()->back()->with('success', __('The category has been deleted.'));
        }
        else{
            return redirect()->route('goal.categories.index')->with('error', __('Permission denied'));
        }
    }
}
