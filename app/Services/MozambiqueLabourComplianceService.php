<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Workdo\Hrm\Models\Holiday;
use Workdo\Hrm\Models\Overtime;

class MozambiqueLabourComplianceService
{
    public function getPolicy(?int $companyId = null): array
    {
        $companyId = $companyId ?: creatorId();

        return [
            'overtime_daily_limit_hours' => $this->toNullableFloat(company_setting('mz_overtime_daily_limit_hours', $companyId)),
            'overtime_monthly_limit_hours' => $this->toNullableFloat(company_setting('mz_overtime_monthly_limit_hours', $companyId)),
            'overtime_yearly_limit_hours' => $this->toNullableFloat(company_setting('mz_overtime_yearly_limit_hours', $companyId)),
            'leave_min_notice_days' => max(0, (int) (company_setting('mz_leave_min_notice_days', $companyId) ?? 0)),
            'leave_max_consecutive_days' => $this->toNullableInt(company_setting('mz_leave_max_consecutive_days', $companyId)),
            'leave_count_non_working_days' => $this->toBool(company_setting('mz_leave_count_non_working_days', $companyId), true),
            'leave_count_holidays' => $this->toBool(company_setting('mz_leave_count_holidays', $companyId), true),
        ];
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

            $monthlyHours = Overtime::query()
                ->where('created_by', $companyId)
                ->where('employee_id', $employeeUserId)
                ->whereDate('start_date', '<=', $monthEnd)
                ->whereDate('end_date', '>=', $monthStart)
                ->when($excludeOvertimeId, fn ($query) => $query->where('id', '!=', $excludeOvertimeId))
                ->sum('hours');

            if (((float) $monthlyHours + $hours) > $policy['overtime_monthly_limit_hours']) {
                return [
                    'valid' => false,
                    'field' => 'hours',
                    'message' => __('Overtime exceeds monthly limit (:limit h).', [
                        'limit' => rtrim(rtrim(number_format($policy['overtime_monthly_limit_hours'], 2, '.', ''), '0'), '.'),
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

            $yearlyHours = Overtime::query()
                ->where('created_by', $companyId)
                ->where('employee_id', $employeeUserId)
                ->whereDate('start_date', '<=', $yearEnd)
                ->whereDate('end_date', '>=', $yearStart)
                ->when($excludeOvertimeId, fn ($query) => $query->where('id', '!=', $excludeOvertimeId))
                ->sum('hours');

            if (((float) $yearlyHours + $hours) > $policy['overtime_yearly_limit_hours']) {
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
            'calendar_days' => $start->diffInDays($end) + 1,
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

    private function toNullableFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
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
}
