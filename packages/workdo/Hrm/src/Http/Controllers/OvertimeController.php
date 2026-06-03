<?php

namespace Workdo\Hrm\Http\Controllers;

use App\Services\MozambiqueLabourComplianceService;
use Workdo\Hrm\Models\Overtime;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Http\Requests\StoreOvertimeRequest;
use Workdo\Hrm\Http\Requests\UpdateOvertimeRequest;
use Workdo\Hrm\Http\Requests\UpdateOvertimeStatusRequest;
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

            $employee = Employee::query()
                ->where('id', (int) $validated['employee_id'])
                ->where('created_by', creatorId())
                ->first();

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
                $this->markAsPendingApproval($overtime);
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
            if (!$this->canAccessOvertime($overtime)) {
                return redirect()->back()->with('error', __('Permission denied'));
            }

            if ((bool) ($overtime->is_cancelled ?? false)) {
                return redirect()->back()->with('error', __('Cancelled overtime cannot be edited.'));
            }

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
            $this->markAsPendingApproval($overtime);
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
            if (!$this->canAccessOvertime($overtime)) {
                return redirect()->back()->with('error', __('Permission denied'));
            }

            if ((bool) ($overtime->is_cancelled ?? false)) {
                return redirect()->back()->with('error', __('Overtime is already cancelled.'));
            }

            $validated = request()->validate([
                'cancellation_reason' => 'required|string|min:5|max:1000',
            ]);

            DestroyOverTime::dispatch($overtime);
            $overtime->update([
                'is_cancelled' => true,
                'cancelled_at' => now(),
                'cancelled_by' => Auth::id(),
                'cancellation_reason' => trim((string) $validated['cancellation_reason']),
            ]);

            return redirect()->back()->with('success', __('The overtime has been cancelled.'))->with('timestamp', time());
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    public function updateStatus(UpdateOvertimeStatusRequest $request, Overtime $overtime)
    {
        if (Auth::user()->can('edit-overtimes')) {
            if (!$this->canAccessOvertime($overtime)) {
                return redirect()->back()->with('error', __('Permission denied'));
            }

            if ((bool) ($overtime->is_cancelled ?? false)) {
                return redirect()->back()->with('error', __('Cancelled overtime cannot be processed.'));
            }

            $validated = $request->validated();
            $status = (string) $validated['status'];

            if ($status === 'approved') {
                $overtime->approval_status = 'approved';
                $overtime->status = 'active';
                $overtime->approved_by = Auth::id();
                $overtime->approved_at = now();
                $overtime->rejected_by = null;
                $overtime->rejected_at = null;
                $overtime->rejection_reason = null;
            } else {
                $overtime->approval_status = 'rejected';
                $overtime->status = 'expired';
                $overtime->rejected_by = Auth::id();
                $overtime->rejected_at = now();
                $overtime->rejection_reason = trim((string) ($validated['rejection_reason'] ?? ''));
                $overtime->approved_by = null;
                $overtime->approved_at = null;
            }

            $overtime->save();
            UpdateOverTime::dispatch($request, $overtime);

            return redirect()->back()->with('success', __('Overtime status updated successfully.'))->with('timestamp', time());
        }

        return redirect()->back()->with('error', __('Permission denied'));
    }

    private function canAccessOvertime(Overtime $overtime): bool
    {
        return (int) $overtime->created_by === (int) creatorId();
    }

    private function markAsPendingApproval(Overtime $overtime): void
    {
        $overtime->approval_status = 'pending';
        $overtime->status = 'expired';
        $overtime->approved_by = null;
        $overtime->approved_at = null;
        $overtime->rejected_by = null;
        $overtime->rejected_at = null;
        $overtime->rejection_reason = null;
    }
}
