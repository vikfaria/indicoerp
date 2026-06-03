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
            $employee = Employee::query()
                ->where('id', (int) $validated['employee_id'])
                ->where('created_by', creatorId())
                ->first();

            if ($employee) {

                $existingDeduction = Deduction::query()
                    ->active()
                    ->where('created_by', creatorId())
                    ->where('employee_id', $employee->user_id)
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
            if (!$this->canAccessDeduction($deduction)) {
                return redirect()->back()->with('error', __('Permission denied'));
            }

            if ((bool) ($deduction->is_cancelled ?? false)) {
                return redirect()->back()->with('error', __('Cancelled deduction cannot be edited.'));
            }

            $validated = $request->validated();

            $existingDeduction = Deduction::query()
                ->active()
                ->where('created_by', creatorId())
                ->where('employee_id', $deduction->employee_id)
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
            if (!$this->canAccessDeduction($deduction)) {
                return redirect()->back()->with('error', __('Permission denied'));
            }

            if ((bool) ($deduction->is_cancelled ?? false)) {
                return redirect()->back()->with('error', __('Deduction is already cancelled.'));
            }

            $validated = request()->validate([
                'cancellation_reason' => 'required|string|min:5|max:1000',
            ]);

            DestroyDeduction::dispatch($deduction);
            $deduction->update([
                'is_cancelled' => true,
                'cancelled_at' => now(),
                'cancelled_by' => Auth::id(),
                'cancellation_reason' => trim((string) $validated['cancellation_reason']),
            ]);

            return redirect()->back()->with('success', __('The deduction has been cancelled.'))->with('timestamp', time());
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    private function canAccessDeduction(Deduction $deduction): bool
    {
        return (int) $deduction->created_by === (int) creatorId();
    }
}
