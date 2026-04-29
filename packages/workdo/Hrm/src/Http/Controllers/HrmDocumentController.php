<?php

namespace Workdo\Hrm\Http\Controllers;

use App\Models\User;
use Workdo\Hrm\Models\HrmDocument;
use Workdo\Hrm\Http\Requests\StoreHrmDocumentRequest;
use Workdo\Hrm\Http\Requests\UpdateHrmDocumentRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Hrm\Models\DocumentCategory;
use Workdo\Hrm\Events\CreateDocument;
use Workdo\Hrm\Events\DestroyDocument;
use Workdo\Hrm\Events\UpdateDocument;

class HrmDocumentController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-hrm-documents')){
            $documents = HrmDocument::with(['uploadedBy:id,name', 'approvedBy:id,name'])
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-hrm-documents')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-hrm-documents')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('title'), function($q) {
                    $q->where('title', 'like', '%' . request('title') . '%');
                })
                ->when(request('document_category_id') && request('document_category_id') !== '', fn($q) => $q->where('document_category_id', request('document_category_id')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('Hrm/HrmDocuments/Index', [
                'documents' => $documents,
                'documentcategories' => DocumentCategory::where('created_by', creatorId())->select('id', 'document_type')->get(),
                'users' => User::where('created_by', creatorId())->select('id', 'name')->get(),
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreHrmDocumentRequest $request)
    {
        if(Auth::user()->can('create-hrm-documents')){
            $validated = $request->validated();

            $document = new HrmDocument();
            $document->title = $validated['title'];
            $document->description = $validated['description'];
            $document->document_category_id = $validated['document_category_id'];
            $document->document = $validated['document'];
            $document->uploaded_by  = Auth::id();
            $document->creator_id = Auth::id();
            $document->created_by = creatorId();
            $document->save();

            CreateDocument::dispatch($request, $document);

            return redirect()->route('hrm.documents.index')->with('success', __('The document has been created successfully.'));
        }
        else{
            return redirect()->route('hrm.documents.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateHrmDocumentRequest $request, HrmDocument $hrmDocument)
    {
        if(Auth::user()->can('edit-hrm-documents')){
            $validated = $request->validated();

            $hrmDocument->title = $validated['title'];
            $hrmDocument->description = $validated['description'];
            $hrmDocument->document_category_id = $validated['document_category_id'];
            $hrmDocument->document = $validated['document'];
            $hrmDocument->save();

            UpdateDocument::dispatch($request, $hrmDocument);

            return redirect()->back()->with('success', __('The document details are updated successfully.'));
        }
        else{
            return redirect()->route('hrm.documents.index')->with('error', __('Permission denied'));
        }
    }

    public function updateStatus(HrmDocument $hrmDocument)
    {
        if(Auth::user()->can('manage-hrm-documents-status')){
            $validated = request()->validate([
                'status' => 'required|in:pending,approve,reject',
            ]);

            $hrmDocument->status = $validated['status'];
            
            if($validated['status'] === 'approve') {
                $hrmDocument->effective_date = now()->toDateString();
                $hrmDocument->approved_by = Auth::id();
            }
            
            $hrmDocument->save();

            return redirect()->back()->with('success', __('Document status updated successfully.'));
        }
        else{
            return redirect()->route('hrm.documents.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(HrmDocument $hrmDocument)
    {
        if(Auth::user()->can('delete-hrm-documents')){
            DestroyDocument::dispatch($hrmDocument);
            $hrmDocument->delete();
            return redirect()->back()->with('success', __('The document has been deleted.'));
        }
        else{
            return redirect()->route('hrm.documents.index')->with('error', __('Permission denied'));
        }
    }
}