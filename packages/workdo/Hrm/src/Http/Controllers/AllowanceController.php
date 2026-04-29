<?php

namespace Workdo\Hrm\Http\Controllers;

use Workdo\Hrm\Models\Allowance;
use Workdo\Hrm\Models\AllowanceType;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Http\Requests\StoreAllowanceRequest;
use Workdo\Hrm\Http\Requests\UpdateAllowanceRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Workdo\Hrm\Events\CreateAllowance;
use Workdo\Hrm\Events\UpdateAllowance;
use Workdo\Hrm\Events\DestroyAllowance;

class AllowanceController extends Controller
{
    public function store(StoreAllowanceRequest $request)
    {
        if (Auth::user()->can('create-allowances')) {
            $validated = $request->validated();
            $employee = Employee::find($validated['employee_id']);

            if ($employee) {
                $existingAllowance = Allowance::where('employee_id', $employee->user_id)
                    ->where('allowance_type_id', $validated['allowance_type_id'])
                    ->first();

                if ($existingAllowance) {
                    return redirect()->back()->with('error', __('This allowance type already exists for this employee.'))->with('timestamp', time());
                }

                $allowance = new Allowance();
                $allowance->employee_id = $employee->user_id;
                $allowance->allowance_type_id = $validated['allowance_type_id'];
                $allowance->type = $validated['type'];
                $allowance->amount = $validated['amount'];
                $allowance->creator_id = Auth::id();
                $allowance->created_by = creatorId();
                $allowance->save();

                CreateAllowance::dispatch($request, $allowance);

                return redirect()->back()->with('success', __('The allowance has been created successfully.'))->with('timestamp', time());
            } else {
                return redirect()->back()->with('error', __('Employee Not Found.'));
            }
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateAllowanceRequest $request, Allowance $allowance)
    {
        if (Auth::user()->can('edit-allowances')) {
            $validated = $request->validated();

            $existingAllowance = Allowance::where('employee_id', $allowance->employee_id)
                ->where('allowance_type_id', $validated['allowance_type_id'])
                ->where('id', '!=', $allowance->id)
                ->first();

            if ($existingAllowance) {
                $employee = Employee::where('user_id', $allowance->employee_id)->first();
                return redirect()->back()->with('error', __('This allowance type already exists for this employee.'));
            }

            $allowance->allowance_type_id = $validated['allowance_type_id'];
            $allowance->type = $validated['type'];
            $allowance->amount = $validated['amount'];
            $allowance->save();

            UpdateAllowance::dispatch($request, $allowance);

            return redirect()->back()->with('success', __('The allowance has been updated successfully.'))->with('timestamp', time());
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(Allowance $allowance, Request $request)
    {
        if (Auth::user()->can('delete-allowances')) {
            DestroyAllowance::dispatch($allowance);
            $allowance->delete();

            return redirect()->back()->with('success', __('The allowance has been deleted.'))->with('timestamp', time());
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }
}
