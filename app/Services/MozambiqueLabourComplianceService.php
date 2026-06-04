<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Workdo\Hrm\Models\Attendance;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Holiday;
use Workdo\Hrm\Models\LeaveApplication;
use Workdo\Hrm\Models\LeaveType;
use Workdo\Hrm\Models\Overtime;

class MozambiqueLabourComplianceService
{
    public function getPolicy(?int $companyId = null): array
    {
        $companyId = $companyId ?: creatorId();

        return [
            'overtime_daily_limit_hours' => $this->toFloatWithDefault(company_setting('mz_overtime_daily_limit_hours', $companyId), 4.0),
            'overtime_weekly_limit_hours' => $this->toFloatWithDefault(company_setting('mz_overtime_weekly_limit_hours', $companyId), 16.0),
            'overtime_monthly_limit_hours' => $this->toNullableFloat(company_setting('mz_overtime_monthly_limit_hours', $companyId)),
            'overtime_quarterly_limit_hours' => $this->toNullableFloat(company_setting('mz_overtime_quarterly_limit_hours', $companyId)),
            'overtime_yearly_limit_hours' => $this->toNullableFloat(company_setting('mz_overtime_yearly_limit_hours', $companyId)),
            'night_work_premium_percent' => $this->toFloatWithDefault(company_setting('mz_night_work_premium_percent', $companyId), 20.0),
            'leave_min_notice_days' => max(0, (int) (company_setting('mz_leave_min_notice_days', $companyId) ?? 0)),
            'leave_max_consecutive_days' => $this->toNullableInt(company_setting('mz_leave_max_consecutive_days', $companyId)),
            'leave_count_non_working_days' => $this->toBool(company_setting('mz_leave_count_non_working_days', $companyId), true),
            'leave_count_holidays' => $this->toBool(company_setting('mz_leave_count_holidays', $companyId), true),
            'leave_entitlement_first_year_days' => max(1, (int) (company_setting('mz_leave_entitlement_first_year_days', $companyId) ?? 12)),
            'leave_entitlement_following_year_days' => max(1, (int) (company_setting('mz_leave_entitlement_following_year_days', $companyId) ?? 30)),
            'leave_entitlement_prorate_first_year' => $this->toBool(company_setting('mz_leave_entitlement_prorate_first_year', $companyId), true),
            'leave_unjustified_absence_penalty_per_day' => max(0, (int) (company_setting('mz_leave_unjustified_absence_penalty_per_day', $companyId) ?? 0)),
            'leave_unjustified_absence_max_penalty_days' => $this->toNullableInt(company_setting('mz_leave_unjustified_absence_max_penalty_days', $companyId)),
        ];
    }

    public function calculateLeaveEntitlementLimit(
        int $companyId,
        int $employeeUserId,
        LeaveType $leaveType,
        int $year
    ): array {
        $leaveTypeMaxDays = max(0, (int) ($leaveType->max_days_per_year ?? 0));

        if (!$this->isAnnualLeaveType($leaveType)) {
            return [
                'rule_source' => 'leave_type_max',
                'base_entitlement_days' => $leaveTypeMaxDays,
                'unjustified_absence_days' => 0,
                'absence_penalty_days' => 0,
                'final_entitlement_days' => $leaveTypeMaxDays,
                'service_year_index' => null,
            ];
        }

        return $this->buildAnnualLeaveEntitlement(
            $companyId,
            $employeeUserId,
            $year,
            $leaveTypeMaxDays > 0 ? $leaveTypeMaxDays : null
        );
    }

    public function validateOvertime(
        int $companyId,
        int $employeeUserId,
        string $startDate,
        string $endDate,
        float $hours,
        ?int $excludeOvertimeId = null
    ): array {
        $policy = $this->getPolicy($companyId);
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($start->gt($end)) {
            return [
                'valid' => false,
                'field' => 'end_date',
                'message' => __('End date must be greater than or equal to start date.'),
            ];
        }

        $totalDays = $start->diffInDays($end) + 1;
        $dailyAverage = $totalDays > 0 ? ($hours / $totalDays) : 0.0;

        if ($policy['overtime_daily_limit_hours'] !== null && $dailyAverage > $policy['overtime_daily_limit_hours']) {
            return [
                'valid' => false,
                'field' => 'hours',
                'message' => __('Overtime exceeds daily limit (:limit h/day). Split the entry or reduce hours.', [
                    'limit' => rtrim(rtrim(number_format($policy['overtime_daily_limit_hours'], 2, '.', ''), '0'), '.'),
                ]),
            ];
        }

        if ($policy['overtime_weekly_limit_hours'] !== null) {
            $periodStart = $start->copy();
            while ($periodStart->lte($end)) {
                $weekStart = $periodStart->copy()->startOfWeek(Carbon::MONDAY);
                $weekEnd = $periodStart->copy()->endOfWeek(Carbon::SUNDAY);
                $segmentEnd = $weekEnd->lt($end) ? $weekEnd : $end;

                $segmentDays = $periodStart->diffInDays($segmentEnd) + 1;
                $estimatedWeekHours = $dailyAverage * $segmentDays;

                $weeklyHours = $this->sumOvertimeHoursForRange(
                    $companyId,
                    $employeeUserId,
                    $weekStart->toDateString(),
                    $weekEnd->toDateString(),
                    $excludeOvertimeId
                );

                if (($weeklyHours + $estimatedWeekHours) > ($policy['overtime_weekly_limit_hours'] + 0.0001)) {
                    return [
                        'valid' => false,
                        'field' => 'hours',
                        'message' => __('Overtime exceeds weekly limit (:limit h).', [
                            'limit' => rtrim(rtrim(number_format($policy['overtime_weekly_limit_hours'], 2, '.', ''), '0'), '.'),
                        ]),
                    ];
                }

                $periodStart = $segmentEnd->copy()->addDay();
            }
        }

        if ($policy['overtime_monthly_limit_hours'] !== null) {
            if ($start->format('Y-m') !== $end->format('Y-m')) {
                return [
                    'valid' => false,
                    'field' => 'end_date',
                    'message' => __('When monthly overtime limit is active, each overtime record must stay within a single month.'),
                ];
            }

            $monthStart = $start->copy()->startOfMonth()->toDateString();
            $monthEnd = $start->copy()->endOfMonth()->toDateString();

            $monthlyHours = $this->sumOvertimeHoursForRange(
                $companyId,
                $employeeUserId,
                $monthStart,
                $monthEnd,
                $excludeOvertimeId
            );

            if (($monthlyHours + $hours) > $policy['overtime_monthly_limit_hours']) {
                return [
                    'valid' => false,
                    'field' => 'hours',
                    'message' => __('Overtime exceeds monthly limit (:limit h).', [
                        'limit' => rtrim(rtrim(number_format($policy['overtime_monthly_limit_hours'], 2, '.', ''), '0'), '.'),
                    ]),
                ];
            }
        }

        if ($policy['overtime_quarterly_limit_hours'] !== null) {
            if ($start->year !== $end->year || $start->quarter !== $end->quarter) {
                return [
                    'valid' => false,
                    'field' => 'end_date',
                    'message' => __('When quarterly overtime limit is active, each overtime record must stay within a single quarter.'),
                ];
            }

            $quarterStart = $start->copy()->startOfQuarter()->toDateString();
            $quarterEnd = $start->copy()->endOfQuarter()->toDateString();

            $quarterlyHours = $this->sumOvertimeHoursForRange(
                $companyId,
                $employeeUserId,
                $quarterStart,
                $quarterEnd,
                $excludeOvertimeId
            );

            if (($quarterlyHours + $hours) > $policy['overtime_quarterly_limit_hours']) {
                return [
                    'valid' => false,
                    'field' => 'hours',
                    'message' => __('Overtime exceeds quarterly limit (:limit h).', [
                        'limit' => rtrim(rtrim(number_format($policy['overtime_quarterly_limit_hours'], 2, '.', ''), '0'), '.'),
                    ]),
                ];
            }
        }

        if ($policy['overtime_yearly_limit_hours'] !== null) {
            if ($start->year !== $end->year) {
                return [
                    'valid' => false,
                    'field' => 'end_date',
                    'message' => __('When yearly overtime limit is active, each overtime record must stay within a single year.'),
                ];
            }

            $yearStart = $start->copy()->startOfYear()->toDateString();
            $yearEnd = $start->copy()->endOfYear()->toDateString();

            $yearlyHours = $this->sumOvertimeHoursForRange(
                $companyId,
                $employeeUserId,
                $yearStart,
                $yearEnd,
                $excludeOvertimeId
            );

            if (($yearlyHours + $hours) > $policy['overtime_yearly_limit_hours']) {
                return [
                    'valid' => false,
                    'field' => 'hours',
                    'message' => __('Overtime exceeds yearly limit (:limit h).', [
                        'limit' => rtrim(rtrim(number_format($policy['overtime_yearly_limit_hours'], 2, '.', ''), '0'), '.'),
                    ]),
                ];
            }
        }

        return [
            'valid' => true,
            'total_days' => $totalDays,
            'daily_average_hours' => round($dailyAverage, 2),
            'policy' => $policy,
        ];
    }

    public function validateLeaveApplication(int $companyId, string $startDate, string $endDate): array
    {
        $policy = $this->getPolicy($companyId);
        $today = Carbon::today();
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if (
            $policy['leave_min_notice_days'] > 0
            && $start->gte($today)
            && $start->lt($today->copy()->addDays($policy['leave_min_notice_days']))
        ) {
            return [
                'valid' => false,
                'field' => 'start_date',
                'message' => __('Leave start date must respect minimum notice of :days day(s).', [
                    'days' => $policy['leave_min_notice_days'],
                ]),
            ];
        }

        $leaveWindow = $this->evaluateLeaveWindow($companyId, $startDate, $endDate);
        if (!$leaveWindow['valid']) {
            return $leaveWindow;
        }

        if (
            $policy['leave_max_consecutive_days'] !== null &&
            $leaveWindow['calendar_days'] > $policy['leave_max_consecutive_days']
        ) {
            return [
                'valid' => false,
                'field' => 'end_date',
                'message' => __('Leave exceeds maximum consecutive days (:days).', [
                    'days' => $policy['leave_max_consecutive_days'],
                ]),
            ];
        }

        if ($leaveWindow['chargeable_days'] < 1) {
            return [
                'valid' => false,
                'field' => 'start_date',
                'message' => __('Selected leave range has no chargeable leave days after policy rules.'),
            ];
        }

        return [
            'valid' => true,
            'chargeable_days' => $leaveWindow['chargeable_days'],
            'calendar_days' => $leaveWindow['calendar_days'],
            'excluded_non_working_days' => $leaveWindow['excluded_non_working_days'],
            'excluded_holidays' => $leaveWindow['excluded_holidays'],
            'policy' => $policy,
        ];
    }

    private function buildAnnualLeaveEntitlement(
        int $companyId,
        int $employeeUserId,
        int $year,
        ?int $leaveTypeMaxDays = null
    ): array {
        $policy = $this->getPolicy($companyId);

        $employee = Employee::query()
            ->where('created_by', $companyId)
            ->where('user_id', $employeeUserId)
            ->first();

        $baseEntitlementDays = (int) $policy['leave_entitlement_following_year_days'];
        $serviceYearIndex = null;

        if ($employee?->date_of_joining) {
            $joiningDate = Carbon::parse($employee->date_of_joining)->startOfDay();
            $joinYear = (int) $joiningDate->year;

            if ($year < $joinYear) {
                $baseEntitlementDays = 0;
                $serviceYearIndex = 0;
            } else {
                $serviceYearIndex = ($year - $joinYear) + 1;

                if ($serviceYearIndex === 1) {
                    $firstYearCap = (int) $policy['leave_entitlement_first_year_days'];
                    if ($policy['leave_entitlement_prorate_first_year']) {
                        $monthsWorkedInJoinYear = max(0, 12 - (int) $joiningDate->month + 1);
                        $baseEntitlementDays = min($firstYearCap, $monthsWorkedInJoinYear);
                    } else {
                        $baseEntitlementDays = $firstYearCap;
                    }
                } else {
                    $baseEntitlementDays = (int) $policy['leave_entitlement_following_year_days'];
                }
            }
        }

        if ($leaveTypeMaxDays !== null && $leaveTypeMaxDays > 0) {
            $baseEntitlementDays = min($baseEntitlementDays, $leaveTypeMaxDays);
        }

        $penaltyPerAbsenceDay = (int) $policy['leave_unjustified_absence_penalty_per_day'];
        $unjustifiedAbsenceDays = $this->countUnjustifiedAbsenceDaysForYear($companyId, $employeeUserId, $year);
        $absencePenaltyDays = $unjustifiedAbsenceDays * $penaltyPerAbsenceDay;

        $maxPenaltyDays = $policy['leave_unjustified_absence_max_penalty_days'];
        if ($maxPenaltyDays !== null && $maxPenaltyDays >= 0) {
            $absencePenaltyDays = min($absencePenaltyDays, (int) $maxPenaltyDays);
        }

        $finalEntitlementDays = max(0, $baseEntitlementDays - $absencePenaltyDays);

        return [
            'rule_source' => 'mozambique_annual_leave_policy',
            'base_entitlement_days' => $baseEntitlementDays,
            'unjustified_absence_days' => $unjustifiedAbsenceDays,
            'absence_penalty_days' => $absencePenaltyDays,
            'final_entitlement_days' => $finalEntitlementDays,
            'service_year_index' => $serviceYearIndex,
        ];
    }

    private function countUnjustifiedAbsenceDaysForYear(int $companyId, int $employeeUserId, int $year): int
    {
        $absences = Attendance::query()
            ->active()
            ->where('created_by', $companyId)
            ->where('employee_id', $employeeUserId)
            ->where('status', 'absent')
            ->whereYear('date', $year)
            ->get(['date', 'is_justified']);

        if ($absences->isEmpty()) {
            return 0;
        }

        $yearStart = Carbon::create($year, 1, 1)->toDateString();
        $yearEnd = Carbon::create($year, 12, 31)->toDateString();

        $approvedLeaveRanges = LeaveApplication::query()
            ->active()
            ->where('created_by', $companyId)
            ->where('employee_id', $employeeUserId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $yearEnd)
            ->whereDate('end_date', '>=', $yearStart)
            ->get(['start_date', 'end_date']);

        $absenceRows = $absences
            ->map(static fn (Attendance $attendance): array => [
                'date' => Carbon::parse($attendance->date)->toDateString(),
                'is_justified' => $attendance->is_justified,
            ])
            ->sortBy('date')
            ->unique(fn (array $row): string => $row['date'])
            ->values();

        $unjustifiedCount = 0;

        foreach ($absenceRows as $absence) {
            $date = Carbon::parse((string) $absence['date'])->startOfDay();

            if ($absence['is_justified'] === true) {
                continue;
            }

            if ($absence['is_justified'] === false) {
                $unjustifiedCount++;
                continue;
            }

            $isCoveredByApprovedLeave = $approvedLeaveRanges->contains(function (LeaveApplication $leave) use ($date): bool {
                $leaveStart = Carbon::parse($leave->start_date)->startOfDay();
                $leaveEnd = Carbon::parse($leave->end_date)->endOfDay();

                return $date->betweenIncluded($leaveStart, $leaveEnd);
            });

            if (!$isCoveredByApprovedLeave) {
                $unjustifiedCount++;
            }
        }

        return $unjustifiedCount;
    }

    public function validateWeeklyRestForAttendanceDate(
        int $companyId,
        int $employeeUserId,
        string $attendanceDate,
        ?int $excludeAttendanceId = null
    ): array {
        $targetDate = Carbon::parse($attendanceDate)->startOfDay();
        $rangeStart = $targetDate->copy()->subDays(6)->toDateString();
        $rangeEnd = $targetDate->copy()->addDays(6)->toDateString();

        $workedDates = [];
        $attendances = Attendance::query()
            ->active()
            ->where('created_by', $companyId)
            ->where('employee_id', $employeeUserId)
            ->whereDate('date', '>=', $rangeStart)
            ->whereDate('date', '<=', $rangeEnd)
            ->whereNotNull('clock_in')
            ->when($excludeAttendanceId, fn ($query) => $query->where('id', '!=', $excludeAttendanceId))
            ->get(['date']);

        foreach ($attendances as $attendance) {
            $workedDates[Carbon::parse($attendance->date)->toDateString()] = true;
        }

        // Candidate day will become a worked day after successful attendance creation.
        $workedDates[$targetDate->toDateString()] = true;

        $windowStart = $targetDate->copy()->subDays(6);
        while ($windowStart->lte($targetDate)) {
            $windowEnd = $windowStart->copy()->addDays(6);
            $allDaysWorked = true;

            for ($cursor = $windowStart->copy(); $cursor->lte($windowEnd); $cursor->addDay()) {
                if (!isset($workedDates[$cursor->toDateString()])) {
                    $allDaysWorked = false;
                    break;
                }
            }

            if ($allDaysWorked) {
                return [
                    'valid' => false,
                    'field' => 'date',
                    'message' => __('Weekly rest rule violated. Worker must have at least 24 consecutive hours of rest every 7 days.'),
                ];
            }

            $windowStart->addDay();
        }

        return ['valid' => true];
    }

    public function validateLeaveTypeCompliance(
        int $companyId,
        LeaveType $leaveType,
        string $startDate,
        string $endDate,
        int $chargeableDays,
        ?string $legalReferenceDate = null,
        ?string $attachment = null,
        int $compensatedDays = 0
    ): array {
        $today = Carbon::today();
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($start->gt($end)) {
            return [
                'valid' => false,
                'field' => 'end_date',
                'message' => __('End date must be greater than or equal to start date.'),
            ];
        }

        $calendarDays = (int) ($start->diffInDays($end) + 1);

        if (
            $leaveType->min_advance_notice_days !== null &&
            $leaveType->min_advance_notice_days > 0 &&
            $start->gte($today) &&
            $start->lt($today->copy()->addDays((int) $leaveType->min_advance_notice_days))
        ) {
            return [
                'valid' => false,
                'field' => 'start_date',
                'message' => __('This leave type requires :days day(s) minimum notice.', [
                    'days' => (int) $leaveType->min_advance_notice_days,
                ]),
            ];
        }

        if ($leaveType->requires_supporting_document && $this->isBlank($attachment)) {
            return [
                'valid' => false,
                'field' => 'attachment',
                'message' => __('Supporting document is required for this leave type.'),
            ];
        }

        if ($leaveType->legal_code === 'maternity') {
            if ($this->isBlank($legalReferenceDate)) {
                return [
                    'valid' => false,
                    'field' => 'legal_reference_date',
                    'message' => __('Maternity leave requires expected childbirth date.'),
                ];
            }

            $referenceDate = Carbon::parse($legalReferenceDate)->startOfDay();
            $maxDaysBefore = (int) ($leaveType->pre_event_start_window_days ?? 20);
            $minimumStartDate = $referenceDate->copy()->subDays($maxDaysBefore);

            if ($start->lt($minimumStartDate) || $start->gt($referenceDate)) {
                return [
                    'valid' => false,
                    'field' => 'start_date',
                    'message' => __('Maternity leave must start between :from and :to.', [
                        'from' => $minimumStartDate->toDateString(),
                        'to' => $referenceDate->toDateString(),
                    ]),
                ];
            }
        }

        if ($leaveType->fixed_duration_days !== null && $calendarDays !== (int) $leaveType->fixed_duration_days) {
            return [
                'valid' => false,
                'field' => 'end_date',
                'message' => __('This leave type requires exactly :days calendar day(s).', [
                    'days' => (int) $leaveType->fixed_duration_days,
                ]),
            ];
        }

        if ($leaveType->legal_code === 'paternity') {
            if ($this->isBlank($legalReferenceDate)) {
                return [
                    'valid' => false,
                    'field' => 'legal_reference_date',
                    'message' => __('Paternity leave requires childbirth date.'),
                ];
            }

            $referenceDate = Carbon::parse($legalReferenceDate)->startOfDay();
            $offsetDays = (int) ($leaveType->post_event_start_offset_days ?? 1);
            $requiredStartDate = $referenceDate->copy()->addDays($offsetDays);

            if (!$start->isSameDay($requiredStartDate)) {
                return [
                    'valid' => false,
                    'field' => 'start_date',
                    'message' => __('Paternity leave must start on :date.', [
                        'date' => $requiredStartDate->toDateString(),
                    ]),
                ];
            }
        }

        if (in_array($leaveType->legal_code, ['adoption', 'foster_care'], true)) {
            if ($this->isBlank($legalReferenceDate)) {
                return [
                    'valid' => false,
                    'field' => 'legal_reference_date',
                    'message' => __('This leave type requires legal event date (adoption or foster care reference date).'),
                ];
            }

            $referenceDate = Carbon::parse($legalReferenceDate)->startOfDay();
            if ($start->lt($referenceDate)) {
                return [
                    'valid' => false,
                    'field' => 'start_date',
                    'message' => __('This leave type cannot start before legal event date.'),
                ];
            }
        }

        if ($compensatedDays < 0) {
            return [
                'valid' => false,
                'field' => 'compensated_days',
                'message' => __('Compensated days cannot be negative.'),
            ];
        }

        if ($compensatedDays > $chargeableDays) {
            return [
                'valid' => false,
                'field' => 'compensated_days',
                'message' => __('Compensated days cannot exceed requested leave days.'),
            ];
        }

        if ($compensatedDays > 0 && !$leaveType->allow_cash_out) {
            return [
                'valid' => false,
                'field' => 'compensated_days',
                'message' => __('This leave type does not allow financial compensation of leave days.'),
            ];
        }

        if ($compensatedDays > 0 && !$this->isAnnualLeaveType($leaveType)) {
            return [
                'valid' => false,
                'field' => 'compensated_days',
                'message' => __('Financial compensation is only allowed for annual leave.'),
            ];
        }

        $effectiveRestDays = max(0, $chargeableDays - $compensatedDays);
        $minimumEffectiveRestDays = $leaveType->min_effective_rest_days;
        if ($minimumEffectiveRestDays === null && $this->isAnnualLeaveType($leaveType)) {
            $minimumEffectiveRestDays = 6;
        }

        if (
            $minimumEffectiveRestDays !== null &&
            $compensatedDays > 0 &&
            $effectiveRestDays < (int) $minimumEffectiveRestDays
        ) {
            return [
                'valid' => false,
                'field' => 'compensated_days',
                'message' => __('Compensation leaves less than minimum required effective rest (:days day(s)).', [
                    'days' => (int) $minimumEffectiveRestDays,
                ]),
            ];
        }

        return [
            'valid' => true,
            'calendar_days' => $calendarDays,
            'effective_rest_days' => $effectiveRestDays,
        ];
    }

    private function evaluateLeaveWindow(int $companyId, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($start->gt($end)) {
            return [
                'valid' => false,
                'field' => 'end_date',
                'message' => __('End date must be greater than or equal to start date.'),
            ];
        }

        $policy = $this->getPolicy($companyId);
        $workingDays = $this->getWorkingDays($companyId);
        $holidays = Holiday::query()
            ->where('created_by', $companyId)
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get();

        $chargeableDays = 0;
        $excludedNonWorkingDays = 0;
        $excludedHolidays = 0;

        foreach (CarbonPeriod::create($start, $end) as $date) {
            $isWorkingDay = in_array($date->dayOfWeek, $workingDays, true);
            $isHoliday = $this->isHoliday($date, $holidays);

            if (!$policy['leave_count_non_working_days'] && !$isWorkingDay) {
                $excludedNonWorkingDays++;
                continue;
            }

            if (!$policy['leave_count_holidays'] && $isHoliday) {
                $excludedHolidays++;
                continue;
            }

            $chargeableDays++;
        }

        return [
            'valid' => true,
            'chargeable_days' => $chargeableDays,
            'calendar_days' => (int) ($start->diffInDays($end) + 1),
            'excluded_non_working_days' => $excludedNonWorkingDays,
            'excluded_holidays' => $excludedHolidays,
        ];
    }

    private function getWorkingDays(int $companyId): array
    {
        $raw = company_setting('working_days', $companyId);
        $days = is_string($raw) ? json_decode($raw, true) : [];

        if (!is_array($days) || $days === []) {
            return [1, 2, 3, 4, 5];
        }

        return array_values(array_unique(array_map('intval', $days)));
    }

    private function isHoliday(Carbon $date, Collection $holidays): bool
    {
        foreach ($holidays as $holiday) {
            $holidayStart = Carbon::parse($holiday->start_date)->startOfDay();
            $holidayEnd = Carbon::parse($holiday->end_date)->endOfDay();

            if ($date->betweenIncluded($holidayStart, $holidayEnd)) {
                return true;
            }
        }

        return false;
    }

    private function sumOvertimeHoursForRange(
        int $companyId,
        int $employeeUserId,
        string $rangeStart,
        string $rangeEnd,
        ?int $excludeOvertimeId = null
    ): float {
        return (float) Overtime::query()
            ->active()
            ->where('created_by', $companyId)
            ->where('employee_id', $employeeUserId)
            ->where(function ($query): void {
                $query->whereIn('approval_status', ['pending', 'approved'])
                    ->orWhere(function ($legacyQuery): void {
                        $legacyQuery->whereNull('approval_status')
                            ->where('status', 'active');
                    });
            })
            ->whereDate('start_date', '<=', $rangeEnd)
            ->whereDate('end_date', '>=', $rangeStart)
            ->when($excludeOvertimeId, fn ($query) => $query->where('id', '!=', $excludeOvertimeId))
            ->sum('hours');
    }

    private function toNullableFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function toFloatWithDefault($value, float $default): float
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return is_numeric($value) ? (float) $value : $default;
    }

    private function toNullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function toBool($value, bool $default): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower((string) $value);

        if (in_array($normalized, ['1', 'true', 'on', 'yes'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'off', 'no'], true)) {
            return false;
        }

        return $default;
    }

    private function isBlank(?string $value): bool
    {
        return $value === null || trim($value) === '';
    }

    private function isAnnualLeaveType(LeaveType $leaveType): bool
    {
        if ($leaveType->legal_code === 'annual') {
            return true;
        }

        $name = strtolower((string) ($leaveType->name ?? ''));
        return str_contains($name, 'ferias') || str_contains($name, 'annual');
    }
}
