<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Workdo\Hrm\Models\AnnualLeavePlan;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\LeaveApplication;
use Workdo\Hrm\Models\LeaveType;

class MozambiqueHrLeaveComplianceReportService
{
    public function __construct(
        private readonly MozambiqueLabourComplianceService $labourComplianceService
    ) {}

    public function buildDataset(int $companyId, ?string $referenceDate = null): array
    {
        $asOfDate = $this->resolveReferenceDate($referenceDate);
        $currentYear = (int) $asOfDate->year;
        $previousYear = $currentYear - 1;

        $employees = Employee::query()
            ->where('created_by', $companyId)
            ->with(['user:id,name', 'branch:id,branch_name', 'department:id,department_name'])
            ->orderBy('employee_id')
            ->orderBy('id')
            ->get([
                'id',
                'employee_id',
                'user_id',
                'tax_payer_id',
                'date_of_joining',
                'branch_id',
                'department_id',
            ]);

        $employeeUserIds = $employees->pluck('user_id')
            ->filter(static fn ($userId): bool => (int) $userId > 0)
            ->map(static fn ($userId): int => (int) $userId)
            ->values();

        $annualLeaveTypes = $this->resolveAnnualLeaveTypes($companyId);
        $annualLeaveTypeIds = $annualLeaveTypes->pluck('id')->map(static fn ($id): int => (int) $id)->values();
        $primaryAnnualLeaveType = $annualLeaveTypes
            ->sortByDesc(static fn (LeaveType $leaveType): int => (int) ($leaveType->max_days_per_year ?? 0))
            ->first();

        $leaveByUser = $this->resolveLeaveApplicationsByUser(
            $companyId,
            $employeeUserIds,
            $annualLeaveTypeIds,
            Carbon::create($previousYear, 1, 1)->startOfDay(),
            Carbon::create($currentYear, 12, 31)->endOfDay()
        );

        $leavePlansByUser = $this->resolveAnnualPlansByUser($companyId, $employeeUserIds, $currentYear);

        $rows = $employees->map(function (Employee $employee) use (
            $asOfDate,
            $companyId,
            $currentYear,
            $previousYear,
            $primaryAnnualLeaveType,
            $leaveByUser,
            $leavePlansByUser
        ): array {
            $employeeUserId = (int) ($employee->user_id ?? 0);
            $leaveRows = $leaveByUser->get($employeeUserId, collect());
            $planRows = $leavePlansByUser->get($employeeUserId, collect());

            $approvedCurrentYear = $leaveRows->filter(function (LeaveApplication $leave) use ($currentYear): bool {
                return $leave->status === 'approved'
                    && $leave->start_date !== null
                    && (int) $leave->start_date->year === $currentYear;
            });

            $approvedPreviousYear = $leaveRows->filter(function (LeaveApplication $leave) use ($previousYear): bool {
                return $leave->status === 'approved'
                    && $leave->start_date !== null
                    && (int) $leave->start_date->year === $previousYear;
            });

            $futureScheduledCurrentYear = $leaveRows->filter(function (LeaveApplication $leave) use ($asOfDate, $currentYear): bool {
                return in_array((string) $leave->status, ['approved', 'pending'], true)
                    && $leave->start_date !== null
                    && (int) $leave->start_date->year === $currentYear
                    && $leave->start_date->greaterThan($asOfDate);
            });

            $takenDaysCurrentYear = round((float) $approvedCurrentYear->sum(fn (LeaveApplication $leave): float => (float) ($leave->total_days ?? 0)), 2);
            $compensatedDaysCurrentYear = round((float) $approvedCurrentYear->sum(fn (LeaveApplication $leave): float => (float) ($leave->compensated_days ?? 0)), 2);
            $effectiveRestDaysCurrentYear = round((float) $approvedCurrentYear->sum(function (LeaveApplication $leave): float {
                $effectiveRestDays = $leave->effective_rest_days;
                if ($effectiveRestDays !== null) {
                    return (float) $effectiveRestDays;
                }

                return max(0.0, (float) ($leave->total_days ?? 0) - (float) ($leave->compensated_days ?? 0));
            }), 2);

            $scheduledFutureDaysCurrentYear = round((float) $futureScheduledCurrentYear->sum(fn (LeaveApplication $leave): float => (float) ($leave->total_days ?? 0)), 2);
            $takenDaysPreviousYear = round((float) $approvedPreviousYear->sum(fn (LeaveApplication $leave): float => (float) ($leave->total_days ?? 0)), 2);

            $entitlementCurrentYear = 0.0;
            $entitlementPreviousYear = 0.0;
            if ($primaryAnnualLeaveType instanceof LeaveType && $employeeUserId > 0) {
                $entitlementCurrentYear = (float) (($this->labourComplianceService->calculateLeaveEntitlementLimit(
                    $companyId,
                    $employeeUserId,
                    $primaryAnnualLeaveType,
                    $currentYear
                ))['final_entitlement_days'] ?? 0);

                $entitlementPreviousYear = (float) (($this->labourComplianceService->calculateLeaveEntitlementLimit(
                    $companyId,
                    $employeeUserId,
                    $primaryAnnualLeaveType,
                    $previousYear
                ))['final_entitlement_days'] ?? 0);
            }

            $balanceCurrentYear = round($entitlementCurrentYear - $takenDaysCurrentYear, 2);
            $overduePreviousYear = round(max(0.0, $entitlementPreviousYear - $takenDaysPreviousYear), 2);
            $dueByYearEndCurrentYear = round(max(0.0, $balanceCurrentYear), 2);

            $plannedTotalCurrentYear = round((float) $planRows->sum('planned_days'), 2);
            $plannedApprovedCurrentYear = round((float) $planRows
                ->filter(fn (AnnualLeavePlan $plan): bool => $plan->status === AnnualLeavePlan::STATUS_APPROVED)
                ->sum('planned_days'), 2);
            $plannedPendingCurrentYear = round((float) $planRows
                ->filter(fn (AnnualLeavePlan $plan): bool => in_array($plan->status, [AnnualLeavePlan::STATUS_PENDING_MANAGER, AnnualLeavePlan::STATUS_PENDING_HR], true))
                ->sum('planned_days'), 2);
            $plannedRejectedCurrentYear = round((float) $planRows
                ->filter(fn (AnnualLeavePlan $plan): bool => $plan->status === AnnualLeavePlan::STATUS_REJECTED)
                ->sum('planned_days'), 2);

            return [
                'reference_date' => $asOfDate->toDateString(),
                'reference_year' => $currentYear,
                'employee_record_id' => (int) $employee->id,
                'employee_internal_id' => (string) ($employee->employee_id ?? ''),
                'employee_name' => (string) ($employee->user?->name ?? ('Employee #' . $employee->id)),
                'employee_nuit' => (string) ($employee->tax_payer_id ?? ''),
                'date_of_joining' => optional($employee->date_of_joining)->toDateString(),
                'branch' => (string) ($employee->branch?->branch_name ?? ''),
                'department' => (string) ($employee->department?->department_name ?? ''),
                'annual_leave_entitlement_days' => round($entitlementCurrentYear, 2),
                'annual_leave_taken_days' => $takenDaysCurrentYear,
                'annual_leave_compensated_days' => $compensatedDaysCurrentYear,
                'annual_leave_effective_rest_days' => $effectiveRestDaysCurrentYear,
                'annual_leave_balance_days' => $balanceCurrentYear,
                'annual_leave_overdue_days_previous_year' => $overduePreviousYear,
                'annual_leave_due_by_year_end_days' => $dueByYearEndCurrentYear,
                'annual_leave_scheduled_future_days' => $scheduledFutureDaysCurrentYear,
                'annual_leave_planned_days_current_year' => $plannedTotalCurrentYear,
                'annual_leave_planned_approved_days_current_year' => $plannedApprovedCurrentYear,
                'annual_leave_planned_pending_days_current_year' => $plannedPendingCurrentYear,
                'annual_leave_planned_rejected_days_current_year' => $plannedRejectedCurrentYear,
            ];
        })->values();

        return [
            'reference_date' => $asOfDate->toDateString(),
            'reference_year' => $currentYear,
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'workers_total' => $rows->count(),
                'annual_leave_types_configured' => $annualLeaveTypes->count(),
                'workers_with_positive_balance' => $rows->filter(fn (array $row): bool => (float) ($row['annual_leave_balance_days'] ?? 0) > 0)->count(),
                'workers_with_overdue_leave' => $rows->filter(fn (array $row): bool => (float) ($row['annual_leave_overdue_days_previous_year'] ?? 0) > 0)->count(),
                'compensated_leave_days_total' => round((float) $rows->sum('annual_leave_compensated_days'), 2),
                'annual_leave_planned_days_total_current_year' => round((float) $rows->sum('annual_leave_planned_days_current_year'), 2),
                'annual_leave_planned_approved_days_total_current_year' => round((float) $rows->sum('annual_leave_planned_approved_days_current_year'), 2),
            ],
            'rows' => $rows->all(),
        ];
    }

    private function resolveReferenceDate(?string $referenceDate): Carbon
    {
        if ($referenceDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $referenceDate)) {
            return Carbon::parse($referenceDate)->startOfDay();
        }

        return now()->startOfDay();
    }

    private function resolveAnnualLeaveTypes(int $companyId): Collection
    {
        if (!Schema::hasTable('leave_types')) {
            return collect();
        }

        return LeaveType::query()
            ->where('created_by', $companyId)
            ->where(function ($query): void {
                $query
                    ->where('legal_code', 'annual')
                    ->orWhereRaw('LOWER(name) like ?', ['%ferias%'])
                    ->orWhereRaw('LOWER(name) like ?', ['%férias%'])
                    ->orWhereRaw('LOWER(name) like ?', ['%annual%']);
            })
            ->orderBy('id')
            ->get(['id', 'name', 'legal_code', 'max_days_per_year']);
    }

    private function resolveLeaveApplicationsByUser(
        int $companyId,
        Collection $employeeUserIds,
        Collection $annualLeaveTypeIds,
        Carbon $periodStart,
        Carbon $periodEnd
    ): Collection {
        if (
            $employeeUserIds->isEmpty()
            || $annualLeaveTypeIds->isEmpty()
            || !Schema::hasTable('leave_applications')
        ) {
            return collect();
        }

        return LeaveApplication::query()
            ->where('created_by', $companyId)
            ->whereIn('employee_id', $employeeUserIds->all())
            ->whereIn('leave_type_id', $annualLeaveTypeIds->all())
            ->whereIn('status', ['approved', 'pending'])
            ->whereDate('start_date', '>=', $periodStart->toDateString())
            ->whereDate('start_date', '<=', $periodEnd->toDateString())
            ->get([
                'employee_id',
                'start_date',
                'total_days',
                'compensated_days',
                'effective_rest_days',
                'status',
            ])
            ->groupBy(static fn (LeaveApplication $leave): int => (int) $leave->employee_id);
    }

    private function resolveAnnualPlansByUser(int $companyId, Collection $employeeUserIds, int $year): Collection
    {
        if (
            $employeeUserIds->isEmpty()
            || !Schema::hasTable('annual_leave_plans')
        ) {
            return collect();
        }

        return AnnualLeavePlan::query()
            ->where('created_by', $companyId)
            ->whereIn('employee_id', $employeeUserIds->all())
            ->where('leave_year', $year)
            ->get([
                'employee_id',
                'planned_days',
                'status',
            ])
            ->groupBy(static fn (AnnualLeavePlan $plan): int => (int) $plan->employee_id);
    }
}
