<?php

namespace Workdo\Hrm\Http\Controllers;

use Workdo\Hrm\Models\Deduction;
use Workdo\Hrm\Models\DeductionType;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Http\Requests\StoreDeductionRequest;
use Workdo\Hrm\Http\Requests\UpdateDeductionRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Workdo\Hrm\Events\CreateDeduction;
use Workdo\Hrm\Events\UpdateDeduction;
use Workdo\Hrm\Events\DestroyDeduction;

class DeductionController extends Controller
{
    public function store(StoreDeductionRequest $request)
    {
        if (Auth::user()->can('create-deductions')) {
            $validated = $request->validated();
            $employee = Employee::find($validated['employee_id']);

            if ($employee) {

                $existingDeduction = Deduction::where('employee_id', $employee->user_id)
                    ->where('deduction_type_id', $validated['deduction_type_id'])
                    ->first();

                if ($existingDeduction) {
                    return redirect()->back()->with('error', __('This deduction type already exists for this employee.'));
                }

                $deduction = new Deduction();
                $deduction->employee_id = $employee->user_id;
                $deduction->deduction_type_id = $validated['deduction_type_id'];
                $deduction->type = $validated['type'];
                $deduction->amount = $validated['amount'];
                $deduction->creator_id = Auth::id();
                $deduction->created_by = creatorId();
                $deduction->save();

                CreateDeduction::dispatch($request, $deduction);

                return redirect()->back()->with('success', __('The deduction has been created successfully.'))->with('timestamp', time());
            } else {
                return redirect()->back()->with('error', __('Employee Not Found.'));
            }
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateDeductionRequest $request, Deduction $deduction)
    {
        if (Auth::user()->can('edit-deductions')) {
            $validated = $request->validated();

            $existingDeduction = Deduction::where('employee_id', $deduction->employee_id)
                ->where('deduction_type_id', $validated['deduction_type_id'])
                ->where('id', '!=', $deduction->id)
                ->first();

            if ($existingDeduction) {
                $employee = Employee::where('user_id', $deduction->employee_id)->first();
                return redirect()->back()->with('error', __('This deduction type already exists for this employee.'));
            }

            $deduction->deduction_type_id = $validated['deduction_type_id'];
            $deduction->type = $validated['type'];
            $deduction->amount = $validated['amount'];
            $deduction->save();

            UpdateDeduction::dispatch($request, $deduction);

            return redirect()->back()->with('success', __('The deduction has been updated successfully.'))->with('timestamp', time());
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(Deduction $deduction, Employee $employee)
    {
        if (Auth::user()->can('delete-deductions')) {
            DestroyDeduction::dispatch($deduction);
            $deduction->delete();

            return redirect()->back()->with('success', __('The deduction has been deleted.'))->with('timestamp', time());
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }
}
