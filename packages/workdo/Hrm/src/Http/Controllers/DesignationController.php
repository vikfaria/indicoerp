<?php

namespace Workdo\Hrm\Http\Controllers;

use Workdo\Hrm\Models\Designation;
use Workdo\Hrm\Http\Requests\StoreDesignationRequest;
use Workdo\Hrm\Http\Requests\UpdateDesignationRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;
use Workdo\Hrm\Events\CreateDesignation;
use Workdo\Hrm\Events\DestroyDesignation;
use Workdo\Hrm\Events\UpdateDesignation;

class DesignationController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-designations')) {
            $designations = Designation::with(['branch', 'department'])->select('id', 'designation_name', 'branch_id', 'department_id', 'created_at')
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-designations')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-designations')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('Hrm/SystemSetup/Designations/Index', [
                'designations' => $designations,
                'branches' => Branch::where('created_by', creatorId())->select('id', 'branch_name')->get(),
                'departments' => Department::where('created_by', creatorId())->select('id', 'department_name', 'branch_id')->get(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreDesignationRequest $request)
    {
        if (Auth::user()->can('create-designations')) {
            $validated = $request->validated();



            $designation = new Designation();
            $designation->designation_name = $validated['designation_name'];
            $designation->branch_id = $validated['branch_id'];
            $designation->department_id = $validated['department_id'];

            $designation->creator_id = Auth::id();
            $designation->created_by = creatorId();
            $designation->save();

            CreateDesignation::dispatch($request, $designation);

            return redirect()->route('hrm.designations.index')->with('success', __('The designation has been created successfully.'));
        } else {
            return redirect()->route('hrm.designations.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateDesignationRequest $request, Designation $designation)
    {
        if (Auth::user()->can('edit-designations')) {
            $validated = $request->validated();



            $designation->designation_name = $validated['designation_name'];
            $designation->branch_id = $validated['branch_id'];
            $designation->department_id = $validated['department_id'];

            $designation->save();

            UpdateDesignation::dispatch($request, $designation);

            return redirect()->route('hrm.designations.index')->with('success', __('The designation details are updated successfully.'));
        } else {
            return redirect()->route('hrm.designations.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(Designation $designation)
    {
        if (Auth::user()->can('delete-designations')) {
            DestroyDesignation::dispatch($designation);
            $designation->delete();

            return redirect()->route('hrm.designations.index')->with('success', __('The designation has been deleted.'));
        } else {
            return redirect()->route('hrm.designations.index')->with('error', __('Permission denied'));
        }
    }


}
