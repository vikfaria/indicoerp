<?php

namespace Workdo\SupportTicket\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\SupportTicket\Models\KnowledgeBaseCategory;
use Workdo\SupportTicket\Events\CreateKnowledgeBaseCategory;
use Workdo\SupportTicket\Events\DestroyKnowledgeBaseCategory;
use Workdo\SupportTicket\Events\UpdateKnowledgeBaseCategory;
use Workdo\SupportTicket\Http\Requests\StoreKnowledgeBaseCategoryRequest;
use Workdo\SupportTicket\Http\Requests\UpdateKnowledgeBaseCategoryRequest;

class KnowledgebaseCategoryController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-knowledge-base')) {
            $categories = KnowledgeBaseCategory::select('id', 'title', 'created_at')
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-knowledge-base')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-knowledge-base')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('SupportTicket/SystemSetup/KnowledgeCategories/Index', [
                'categories' => $categories
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreKnowledgeBaseCategoryRequest $request)
    {
        if (Auth::user()->can('create-knowledge-base')) {
            $knowledgeBaseCategory = new KnowledgeBaseCategory();
            $knowledgeBaseCategory->title = $request->title;
            $knowledgeBaseCategory->creator_id = Auth::id();
            $knowledgeBaseCategory->created_by = creatorId();
            $knowledgeBaseCategory->save();

            CreateKnowledgeBaseCategory::dispatch($request, $knowledgeBaseCategory);

            return redirect()->route('knowledge-category.index')->with('success', __('The knowledge category has been created successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateKnowledgeBaseCategoryRequest $request, $category)
    {
        if (Auth::user()->can('edit-knowledge-base')) {
            $knowledgeCategory = KnowledgeBaseCategory::find($category);
            
            $knowledgeCategory->title = $request->title;
            $knowledgeCategory->save();

            UpdateKnowledgeBaseCategory::dispatch($request, $knowledgeCategory);

            return back()->with('success', __('The knowledge category details are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy($id)
    {
        if (Auth::user()->can('delete-knowledge-base')) {
            $knowledgeBaseCategory = KnowledgeBaseCategory::find($id);
            if ($knowledgeBaseCategory) {
                DestroyKnowledgeBaseCategory::dispatch($knowledgeBaseCategory);
                $knowledgeBaseCategory->delete();
                return back()->with('success', __('The knowledge category has been deleted.'));
            }
            return back()->with('error', __('Category not found'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }
}