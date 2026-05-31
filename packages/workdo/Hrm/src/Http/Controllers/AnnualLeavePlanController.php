<?php

namespace Workdo\Hrm\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Workdo\Hrm\Http\Requests\StoreAnnualLeavePlanRequest;
use Workdo\Hrm\Http\Requests\UpdateAnnualLeavePlanRequest;
use Workdo\Hrm\Http\Requests\UpdateAnnualLeavePlanStatusRequest;
use Workdo\Hrm\Models\AnnualLeavePlan;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\LeaveType;

class AnnualLeavePlanController extends Controller
{
    public function store(StoreAnnualLeavePlanRequest $request)
    {
        if (!Auth::user()->can('create-leave-applications')) {
            return redirect()->back()->with('error', __('Permission denied'));
        }

        $validated = $request->validated();

        if (!$this->employeeBelongsToCompany((int) $validated['employee_id'])) {
            return redirect()->back()->withErrors([
                'employee_id' => __('Employee not found for this company.'),
            ]);
        }

        $leaveType = LeaveType::query()
            ->where('id', (int) $validated['leave_type_id'])
            ->where('created_by', creatorId())
            ->first();

        if ($leaveType === null) {
            return redirect()->back()->withErrors([
                'leave_type_id' => __('Invalid leave type selected.'),
            ]);
        }

        if (!$this->isAnnualLeaveType($leaveType)) {
            return redirect()->back()->withErrors([
                'leave_type_id' => __('Annual leave plan must use an annual leave type.'),
            ]);
        }

        $startDate = Carbon::parse($validated['planned_start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['planned_end_date'])->startOfDay();
        $plannedDays = (int) ($startDate->diffInDays($endDate) + 1);

        if ($this->hasOverlap((int) $validated['employee_id'], $startDate->toDateString(), $endDate->toDateString(), null)) {
            return redirect()->back()->withErrors([
                'planned_start_date' => __('Annual leave plan overlaps with another active plan for this worker.'),
            ]);
        }

        if (!$this->fitsYearlyLeaveLimit((int) $validated['employee_id'], (int) $validated['leave_type_id'], (int) $validated['leave_year'], $plannedDays, null)) {
            return redirect()->back()->withErrors([
                'planned_end_date' => __('Planned days exceed annual leave limit for this leave type.'),
            ]);
        }

        AnnualLeavePlan::query()->create([
            'employee_id' => (int) $validated['employee_id'],
            'leave_type_id' => (int) $validated['leave_type_id'],
            'leave_year' => (int) $validated['leave_year'],
            'planned_start_date' => $startDate->toDateString(),
            'planned_end_date' => $endDate->toDateString(),
            'planned_days' => $plannedDays,
            'status' => AnnualLeavePlan::STATUS_PENDING_MANAGER,
            'notes' => $validated['notes'] ?? null,
            'creator_id' => Auth::id(),
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Annual leave plan created successfully.'));
    }

    public function update(UpdateAnnualLeavePlanRequest $request, AnnualLeavePlan $annualLeavePlan)
    {
        if (!Auth::user()->can('edit-leave-applications')) {
            return redirect()->back()->with('error', __('Permission denied'));
        }

        if (!$this->canAccessPlan($annualLeavePlan)) {
            return redirect()->back()->with('error', __('Permission denied'));
        }

        if ($annualLeavePlan->status === AnnualLeavePlan::STATUS_APPROVED) {
            return redirect()->back()->with('error', __('Approved annual leave plans cannot be edited.'));
        }

        $validated = $request->validated();

        if (!$this->employeeBelongsToCompany((int) $validated['employee_id'])) {
            return redirect()->back()->withErrors([
                'employee_id' => __('Employee not found for this company.'),
            ]);
        }

        $leaveType = LeaveType::query()
            ->where('id', (int) $validated['leave_type_id'])
            ->where('created_by', creatorId())
            ->first();

        if ($leaveType === null) {
            return redirect()->back()->withErrors([
                'leave_type_id' => __('Invalid leave type selected.'),
            ]);
        }

        if (!$this->isAnnualLeaveType($leaveType)) {
            return redirect()->back()->withErrors([
                'leave_type_id' => __('Annual leave plan must use an annual leave type.'),
            ]);
        }

        $startDate = Carbon::parse($validated['planned_start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['planned_end_date'])->startOfDay();
        $plannedDays = (int) ($startDate->diffInDays($endDate) + 1);

        if ($this->hasOverlap((int) $validated['employee_id'], $startDate->toDateString(), $endDate->toDateString(), (int) $annualLeavePlan->id)) {
            return redirect()->back()->withErrors([
                'planned_start_date' => __('Annual leave plan overlaps with another active plan for this worker.'),
            ]);
        }

        if (!$this->fitsYearlyLeaveLimit((int) $validated['employee_id'], (int) $validated['leave_type_id'], (int) $validated['leave_year'], $plannedDays, (int) $annualLeavePlan->id)) {
            return redirect()->back()->withErrors([
                'planned_end_date' => __('Planned days exceed annual leave limit for this leave type.'),
            ]);
        }

        $annualLeavePlan->fill([
            'employee_id' => (int) $validated['employee_id'],
            'leave_type_id' => (int) $validated['leave_type_id'],
            'leave_year' => (int) $validated['leave_year'],
            'planned_start_date' => $startDate->toDateString(),
            'planned_end_date' => $endDate->toDateString(),
            'planned_days' => $plannedDays,
            'status' => AnnualLeavePlan::STATUS_PENDING_MANAGER,
            'manager_approved_by' => null,
            'manager_approved_at' => null,
            'hr_approved_by' => null,
            'hr_approved_at' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'notes' => $validated['notes'] ?? null,
        ])->save();

        return redirect()->back()->with('success', __('Annual leave plan updated successfully.'));
    }

    public function updateStatus(UpdateAnnualLeavePlanStatusRequest $request, AnnualLeavePlan $annualLeavePlan)
    {
        if (!Auth::user()->can('manage-leave-status')) {
            return redirect()->back()->with('error', __('Permission denied'));
        }

        if (!$this->canAccessPlan($annualLeavePlan)) {
            return redirect()->back()->with('error', __('Permission denied'));
        }

        $validated = $request->validated();
        $action = (string) $validated['action'];

        if ($action === 'manager_approve') {
            if ($annualLeavePlan->status !== AnnualLeavePlan::STATUS_PENDING_MANAGER) {
                return redirect()->back()->withErrors([
                    'action' => __('Plan must be pending manager approval.'),
                ]);
            }

            $annualLeavePlan->fill([
                'status' => AnnualLeavePlan::STATUS_PENDING_HR,
                'manager_approved_by' => Auth::id(),
                'manager_approved_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ])->save();

            return redirect()->back()->with('success', __('Annual leave plan approved by manager and moved to HR review.'));
        }

        if ($action === 'hr_approve') {
            if ($annualLeavePlan->status !== AnnualLeavePlan::STATUS_PENDING_HR) {
                return redirect()->back()->withErrors([
                    'action' => __('Plan must be pending HR approval.'),
                ]);
            }

            $annualLeavePlan->fill([
                'status' => AnnualLeavePlan::STATUS_APPROVED,
                'hr_approved_by' => Auth::id(),
                'hr_approved_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ])->save();

            return redirect()->back()->with('success', __('Annual leave plan approved by HR.'));
        }

        if (!in_array($annualLeavePlan->status, [AnnualLeavePlan::STATUS_PENDING_MANAGER, AnnualLeavePlan::STATUS_PENDING_HR], true)) {
            return redirect()->back()->withErrors([
                'action' => __('Only pending plans can be rejected.'),
            ]);
        }

        $annualLeavePlan->fill([
            'status' => AnnualLeavePlan::STATUS_REJECTED,
            'rejected_by' => Auth::id(),
            'rejected_at' => now(),
            'rejection_reason' => trim((string) ($validated['rejection_reason'] ?? '')),
            'hr_approved_by' => null,
            'hr_approved_at' => null,
        ])->save();

        return redirect()->back()->with('success', __('Annual leave plan rejected.'));
    }

    public function destroy(AnnualLeavePlan $annualLeavePlan)
    {
        if (!Auth::user()->can('delete-leave-applications')) {
            return redirect()->back()->with('error', __('Permission denied'));
        }

        if (!$this->canAccessPlan($annualLeavePlan)) {
            return redirect()->back()->with('error', __('Permission denied'));
        }

        if ($annualLeavePlan->status === AnnualLeavePlan::STATUS_APPROVED) {
            return redirect()->back()->with('error', __('Approved annual leave plans cannot be deleted.'));
        }

        $annualLeavePlan->delete();

        return redirect()->back()->with('success', __('Annual leave plan deleted successfully.'));
    }

    private function canAccessPlan(AnnualLeavePlan $annualLeavePlan): bool
    {
        return (int) $annualLeavePlan->created_by === (int) creatorId();
    }

    private function employeeBelongsToCompany(int $employeeUserId): bool
    {
        return Employee::query()
            ->where('user_id', $employeeUserId)
            ->where('created_by', creatorId())
            ->exists();
    }

    private function isAnnualLeaveType(LeaveType $leaveType): bool
    {
        return $leaveType->legal_code === null || $leaveType->legal_code === 'annual';
    }

    private function hasOverlap(int $employeeUserId, string $startDate, string $endDate, ?int $excludeId): bool
    {
        return AnnualLeavePlan::query()
            ->where('created_by', creatorId())
            ->where('employee_id', $employeeUserId)
            ->whereIn('status', [
                AnnualLeavePlan::STATUS_PENDING_MANAGER,
                AnnualLeavePlan::STATUS_PENDING_HR,
                AnnualLeavePlan::STATUS_APPROVED,
            ])
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->where(function ($query) use ($startDate, $endDate): void {
                $query->whereBetween('planned_start_date', [$startDate, $endDate])
                    ->orWhereBetween('planned_end_date', [$startDate, $endDate])
                    ->orWhere(function ($fullCoverQuery) use ($startDate, $endDate): void {
                        $fullCoverQuery->where('planned_start_date', '<=', $startDate)
                            ->where('planned_end_date', '>=', $endDate);
                    });
            })
            ->exists();
    }

    private function fitsYearlyLeaveLimit(
        int $employeeUserId,
        int $leaveTypeId,
        int $leaveYear,
        int $plannedDays,
        ?int $excludeId
    ): bool {
        $leaveType = LeaveType::query()
            ->where('id', $leaveTypeId)
            ->where('created_by', creatorId())
            ->first();

        if ($leaveType === null || $leaveType->max_days_per_year === null) {
            return true;
        }

        $alreadyPlanned = (int) AnnualLeavePlan::query()
            ->where('created_by', creatorId())
            ->where('employee_id', $employeeUserId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('leave_year', $leaveYear)
            ->whereIn('status', [
                AnnualLeavePlan::STATUS_PENDING_MANAGER,
                AnnualLeavePlan::STATUS_PENDING_HR,
                AnnualLeavePlan::STATUS_APPROVED,
            ])
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->sum('planned_days');

        return ($alreadyPlanned + $plannedDays) <= (int) $leaveType->max_days_per_year;
    }
}
