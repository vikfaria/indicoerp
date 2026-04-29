<?php

namespace Workdo\Hrm\Http\Controllers;

use Workdo\Hrm\Models\DocumentCategory;
use Workdo\Hrm\Http\Requests\StoreDocumentCategoryRequest;
use Workdo\Hrm\Http\Requests\UpdateDocumentCategoryRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Hrm\Events\CreateDocumentCategory;
use Workdo\Hrm\Events\DestroyDocumentCategory;
use Workdo\Hrm\Events\UpdateDocumentCategory;


class DocumentCategoryController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-document-categories')){
            $documentcategories = DocumentCategory::select('id', 'document_type', 'status', 'created_at')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-document-categories')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-document-categories')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('Hrm/SystemSetup/DocumentCategories/Index', [
                'documentcategories' => $documentcategories,

            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreDocumentCategoryRequest $request)
    {
        if(Auth::user()->can('create-document-categories')){
            $validated = $request->validated();
            
            $validated['status'] = $request->boolean('status', true);
            
            $documentcategory = new DocumentCategory();
            $documentcategory->document_type = $validated['document_type'];
            $documentcategory->status = $validated['status'];

            $documentcategory->creator_id = Auth::id();
            $documentcategory->created_by = creatorId();
            $documentcategory->save();

            CreateDocumentCategory::dispatch($request, $documentcategory);

            return redirect()->route('hrm.document-categories.index')->with('success', __('The documentcategory has been created successfully.'));
        }
        else{
            return redirect()->route('hrm.document-categories.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateDocumentCategoryRequest $request, DocumentCategory $documentcategory)
    {
        if(Auth::user()->can('edit-document-categories')){
            $validated = $request->validated();

            $validated['status'] = $request->boolean('status', true);

            $documentcategory->document_type = $validated['document_type'];
            $documentcategory->status = $validated['status'];

            $documentcategory->save();

            UpdateDocumentCategory::dispatch($request, $documentcategory);

            return redirect()->route('hrm.document-categories.index')->with('success', __('The documentcategory details are updated successfully.'));
        }
        else{
            return redirect()->route('hrm.document-categories.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(DocumentCategory $documentcategory)
    {
        if(Auth::user()->can('delete-document-categories')){
            DestroyDocumentCategory::dispatch($documentcategory);
            $documentcategory->delete();

            return redirect()->route('hrm.document-categories.index')->with('success', __('The documentcategory has been deleted.'));
        }
        else{
            return redirect()->route('hrm.document-categories.index')->with('error', __('Permission denied'));
        }
    }


}