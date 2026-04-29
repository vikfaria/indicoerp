<?php

namespace Workdo\Hrm\Http\Controllers;

use App\Services\MozambiqueLabourComplianceService;
use Workdo\Hrm\Models\Overtime;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Http\Requests\StoreOvertimeRequest;
use Workdo\Hrm\Http\Requests\UpdateOvertimeRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Workdo\Hrm\Events\CreateOverTime;
use Workdo\Hrm\Events\UpdateOverTime;
use Workdo\Hrm\Events\DestroyOverTime;

class OvertimeController extends Controller
{
    public function __construct(private readonly MozambiqueLabourComplianceService $labourComplianceService)
    {
    }

    public function store(StoreOvertimeRequest $request)
    {
        if (Auth::user()->can('create-overtimes')) {
            $validated = $request->validated();
            
            $employee = Employee::find($validated['employee_id']);

            if ($employee) {
                $overtimeCheck = $this->labourComplianceService->validateOvertime(
                    creatorId(),
                    (int) $employee->user_id,
                    $validated['start_date'],
                    $validated['end_date'],
                    (float) $validated['hours']
                );

                if (!$overtimeCheck['valid']) {
                    return redirect()->back()->withErrors([
                        $overtimeCheck['field'] => $overtimeCheck['message'],
                    ]);
                }

                $overtime = new Overtime();
                $overtime->title = $validated['title'];
                $overtime->employee_id = $employee->user_id;
                $overtime->total_days = $validated['total_days'];
                $overtime->hours = $validated['hours'];
                $overtime->rate = $validated['rate'];
                $overtime->start_date = $validated['start_date'];
                $overtime->end_date = $validated['end_date'];
                $overtime->notes = $validated['notes'];
                $overtime->status = $validated['status'];
                $overtime->creator_id = Auth::id();
                $overtime->created_by = creatorId();
                $overtime->save();

                CreateOverTime::dispatch($request, $overtime);

                return redirect()->back()->with('success', __('The overtime has been created successfully.'))->with('timestamp', time());
            } else {
                return redirect()->back()->with('error', __('Employee Not Found.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateOvertimeRequest $request, Overtime $overtime)
    {
        if (Auth::user()->can('edit-overtimes')) {

            $validated = $request->validated();
            $overtimeCheck = $this->labourComplianceService->validateOvertime(
                creatorId(),
                (int) $overtime->employee_id,
                $validated['start_date'],
                $validated['end_date'],
                (float) $validated['hours'],
                (int) $overtime->id
            );

            if (!$overtimeCheck['valid']) {
                return redirect()->back()->withErrors([
                    $overtimeCheck['field'] => $overtimeCheck['message'],
                ]);
            }

            $overtime->title = $validated['title'];
            $overtime->total_days = $validated['total_days'];
            $overtime->hours = $validated['hours'];
            $overtime->rate = $validated['rate'];
            $overtime->start_date = $validated['start_date'];
            $overtime->end_date = $validated['end_date'];
            $overtime->notes = $validated['notes'];
            $overtime->status = $validated['status'];
            $overtime->save();

            UpdateOverTime::dispatch($request, $overtime);

            return redirect()->back()->with('success', __('The overtime has been updated successfully.'))->with('timestamp', time());
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(Overtime $overtime, Employee $employee)
    {
        if (Auth::user()->can('delete-overtimes')) {
            DestroyOverTime::dispatch($overtime);
            $overtime->delete();

            return redirect()->back()->with('success', __('The overtime has been deleted.'))->with('timestamp', time());
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }
}
