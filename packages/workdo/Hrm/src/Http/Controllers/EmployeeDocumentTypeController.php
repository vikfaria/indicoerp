<?php

namespace Workdo\Hrm\Http\Controllers;

use Workdo\Hrm\Models\EmployeeDocumentType;
use Workdo\Hrm\Http\Requests\StoreEmployeeDocumentTypeRequest;
use Workdo\Hrm\Http\Requests\UpdateEmployeeDocumentTypeRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;


class EmployeeDocumentTypeController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-employee-document-types')) {
            $employeedocumenttypes = EmployeeDocumentType::select('id', 'document_name', 'description', 'is_required', 'created_at')
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-employee-document-types')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-employee-document-types')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('Hrm/SystemSetup/EmployeeDocumentTypes/Index', [
                'employeedocumenttypes' => $employeedocumenttypes,

            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreEmployeeDocumentTypeRequest $request)
    {
        if (Auth::user()->can('create-employee-document-types')) {
            $validated = $request->validated();

            $validated['is_required'] = $request->boolean('is_required', false);

            $employeedocumenttype = new EmployeeDocumentType();
            $employeedocumenttype->document_name = $validated['document_name'];
            $employeedocumenttype->description = $validated['description'];
            $employeedocumenttype->is_required = $validated['is_required'];

            $employeedocumenttype->creator_id = Auth::id();
            $employeedocumenttype->created_by = creatorId();
            $employeedocumenttype->save();

            return redirect()->route('hrm.employee-document-types.index')->with('success', __('The document type has been created successfully.'));
        } else {
            return redirect()->route('hrm.employee-document-types.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateEmployeeDocumentTypeRequest $request, EmployeeDocumentType $employeedocumenttype)
    {
        if (Auth::user()->can('edit-employee-document-types')) {
            $validated = $request->validated();

            $validated['is_required'] = $request->boolean('is_required', false);

            $employeedocumenttype->document_name = $validated['document_name'];
            $employeedocumenttype->description = $validated['description'];
            $employeedocumenttype->is_required = $validated['is_required'];

            $employeedocumenttype->save();

            return redirect()->route('hrm.employee-document-types.index')->with('success', __('The document type has been updated successfully.'));
        } else {
            return redirect()->route('hrm.employee-document-types.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(EmployeeDocumentType $employeedocumenttype)
    {
        if (Auth::user()->can('delete-employee-document-types')) {
            $employeedocumenttype->delete();

            return redirect()->route('hrm.employee-document-types.index')->with('success', __('The document type has been deleted.'));
        } else {
            return redirect()->route('hrm.employee-document-types.index')->with('error', __('Permission denied'));
        }
    }
}
