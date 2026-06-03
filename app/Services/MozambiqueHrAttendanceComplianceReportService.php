<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Workdo\Hrm\Models\Attendance;

class MozambiqueHrAttendanceComplianceReportService
{
    public function buildDataset(int $companyId, ?string $referencePeriod = null): array
    {
        [$period, $periodStart, $periodEnd] = $this->resolvePeriod($referencePeriod);
        $periodStartDate = $periodStart->toDateString();
        $periodEndDate = $periodEnd->toDateString();

        $attendances = Attendance::query()
            ->active()
            ->with([
                'user:id,name',
                'shift:id,shift_name,start_time,end_time,is_night_shift',
            ])
            ->where('created_by', $companyId)
            ->whereDate('date', '>=', $periodStartDate)
            ->whereDate('date', '<=', $periodEndDate)
            ->orderBy('date')
            ->orderBy('employee_id')
            ->orderBy('id')
            ->get([
                'id',
                'employee_id',
                'shift_id',
                'date',
                'clock_in',
                'clock_out',
                'break_hour',
                'total_hour',
                'overtime_hours',
                'overtime_amount',
                'status',
                'is_justified',
                'absence_category',
                'notes',
                'created_by',
            ]);

        $employeeIds = $attendances->pluck('employee_id')
            ->filter()
            ->map(static fn ($employeeId): int => (int) $employeeId)
            ->unique()
            ->values();

        $weeklyRestBreachDateLookup = $this->buildWeeklyRestBreachDateLookup(
            $companyId,
            $employeeIds,
            $periodStart,
            $periodEnd
        );

        $rows = $attendances->map(function (Attendance $attendance) use ($period, $periodStartDate, $periodEndDate, $weeklyRestBreachDateLookup): array {
            $attendanceDate = Carbon::parse($attendance->date)->toDateString();
            $clockIn = $attendance->clock_in ? Carbon::parse($attendance->clock_in) : null;
            $clockOut = $attendance->clock_out ? Carbon::parse($attendance->clock_out) : null;
            $shift = $attendance->shift;

            $clockOutBeforeClockIn = false;
            if ($clockIn && $clockOut && $clockOut->lessThan($clockIn)) {
                $clockOutBeforeClockIn = true;
                $clockOut = $clockOut->copy()->addDay();
            }

            [$scheduledStart, $scheduledEnd] = $this->resolveScheduledWindow($attendanceDate, $shift);
            $lateMinutes = $this->calculateLateMinutes($clockIn, $scheduledStart);
            $earlyExitMinutes = $this->calculateEarlyExitMinutes($clockOut, $scheduledEnd);

            $nightWorkMinutes = $this->calculateNightWorkMinutes($clockIn, $clockOut);
            $nightWork = $nightWorkMinutes > 0;

            $missingClockOut = $clockIn !== null && $clockOut === null;
            $excessiveHours = ((float) ($attendance->total_hour ?? 0)) > 16.0;

            $weeklyRestBreachRisk = (bool) ($weeklyRestBreachDateLookup[(int) $attendance->employee_id][$attendanceDate] ?? false);

            $isAbsent = (string) $attendance->status === 'absent';
            $justifiedAbsence = $isAbsent && $attendance->is_justified === true;
            $unjustifiedAbsence = $isAbsent && $attendance->is_justified !== true;

            $anomalyLateEntry = $lateMinutes > 0;
            $anomalyEarlyExit = $earlyExitMinutes > 0;
            $anomalyMissingClockOut = $missingClockOut;
            $anomalyExcessiveHours = $excessiveHours;
            $anomalyClockOrder = $clockOutBeforeClockIn;
            $anomalyWeeklyRest = $weeklyRestBreachRisk;

            $hasAnyAnomaly = $anomalyLateEntry
                || $anomalyEarlyExit
                || $anomalyMissingClockOut
                || $anomalyExcessiveHours
                || $anomalyClockOrder
                || $anomalyWeeklyRest;

            return [
                'reference_period' => $period,
                'period_start' => $periodStartDate,
                'period_end' => $periodEndDate,
                'attendance_id' => (int) $attendance->id,
                'attendance_date' => $attendanceDate,
                'employee_id' => (int) $attendance->employee_id,
                'employee_name' => (string) ($attendance->user?->name ?? '-'),
                'shift_name' => (string) ($shift?->shift_name ?? ''),
                'clock_in' => $clockIn?->format('Y-m-d H:i:s'),
                'clock_out' => $clockOut?->format('Y-m-d H:i:s'),
                'scheduled_start' => $scheduledStart?->format('Y-m-d H:i:s'),
                'scheduled_end' => $scheduledEnd?->format('Y-m-d H:i:s'),
                'status' => (string) ($attendance->status ?? ''),
                'absence_category' => (string) ($attendance->absence_category ?? ''),
                'justified_absence' => $justifiedAbsence,
                'unjustified_absence' => $unjustifiedAbsence,
                'break_hours' => round((float) ($attendance->break_hour ?? 0), 2),
                'worked_hours' => round((float) ($attendance->total_hour ?? 0), 2),
                'overtime_hours' => round((float) ($attendance->overtime_hours ?? 0), 2),
                'overtime_amount' => round((float) ($attendance->overtime_amount ?? 0), 2),
                'late_minutes' => $lateMinutes,
                'early_exit_minutes' => $earlyExitMinutes,
                'night_work' => $nightWork,
                'night_work_minutes' => $nightWorkMinutes,
                'weekly_rest_breach_risk' => $weeklyRestBreachRisk,
                'anomaly_late_entry' => $anomalyLateEntry,
                'anomaly_early_exit' => $anomalyEarlyExit,
                'anomaly_missing_clock_out' => $anomalyMissingClockOut,
                'anomaly_excessive_hours' => $anomalyExcessiveHours,
                'anomaly_clock_order' => $anomalyClockOrder,
                'anomaly_weekly_rest' => $anomalyWeeklyRest,
                'has_attendance_anomaly' => $hasAnyAnomaly,
                'notes' => (string) ($attendance->notes ?? ''),
            ];
        })->values();

        $workersWithWeeklyRestBreach = $rows
            ->filter(fn (array $row): bool => (bool) ($row['weekly_rest_breach_risk'] ?? false))
            ->pluck('employee_id')
            ->unique()
            ->count();

        return [
            'reference_period' => $period,
            'period_start' => $periodStartDate,
            'period_end' => $periodEndDate,
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'attendance_records_total' => $rows->count(),
                'presences_total' => $rows->where('status', 'present')->count(),
                'half_days_total' => $rows->where('status', 'half day')->count(),
                'absences_total' => $rows->where('status', 'absent')->count(),
                'absences_justified_total' => $rows->where('justified_absence', true)->count(),
                'absences_unjustified_total' => $rows->where('unjustified_absence', true)->count(),
                'overtime_hours_total' => round((float) $rows->sum('overtime_hours'), 2),
                'night_work_records_total' => $rows->where('night_work', true)->count(),
                'workers_with_weekly_rest_breach_risk' => $workersWithWeeklyRestBreach,
                'attendance_anomalies_total' => $rows->where('has_attendance_anomaly', true)->count(),
            ],
            'rows' => $rows->all(),
        ];
    }

    private function resolvePeriod(?string $referencePeriod): array
    {
        $period = is_string($referencePeriod) && preg_match('/^\d{4}-\d{2}$/', $referencePeriod)
            ? $referencePeriod
            : now()->format('Y-m');

        $periodStart = Carbon::createFromFormat('Y-m-d', $period . '-01')->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        return [$period, $periodStart, $periodEnd];
    }

    private function resolveScheduledWindow(string $attendanceDate, $shift): array
    {
        if (!$shift || !$shift->start_time || !$shift->end_time) {
            return [null, null];
        }

        $scheduledStart = Carbon::parse($attendanceDate . ' ' . $shift->start_time);
        $scheduledEnd = Carbon::parse($attendanceDate . ' ' . $shift->end_time);

        if ($shift->is_night_shift || $scheduledEnd->lessThanOrEqualTo($scheduledStart)) {
            $scheduledEnd->addDay();
        }

        return [$scheduledStart, $scheduledEnd];
    }

    private function calculateLateMinutes(?Carbon $clockIn, ?Carbon $scheduledStart): int
    {
        if (!$clockIn || !$scheduledStart || $clockIn->lessThanOrEqualTo($scheduledStart)) {
            return 0;
        }

        return max(0, $scheduledStart->diffInMinutes($clockIn));
    }

    private function calculateEarlyExitMinutes(?Carbon $clockOut, ?Carbon $scheduledEnd): int
    {
        if (!$clockOut || !$scheduledEnd || $clockOut->greaterThanOrEqualTo($scheduledEnd)) {
            return 0;
        }

        return max(0, $clockOut->diffInMinutes($scheduledEnd));
    }

    private function calculateNightWorkMinutes(?Carbon $clockIn, ?Carbon $clockOut): int
    {
        if (!$clockIn || !$clockOut) {
            return 0;
        }

        $start = $clockIn->copy();
        $end = $clockOut->copy();
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        $daysToInspect = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);
        $nightMinutes = 0;

        for ($i = 0; $i <= $daysToInspect; $i++) {
            $day = $start->copy()->startOfDay()->addDays($i);

            $windowAStart = $day->copy()->setTime(22, 0, 0);
            $windowAEnd = $day->copy()->endOfDay();
            $nightMinutes += $this->overlapMinutes($start, $end, $windowAStart, $windowAEnd);

            $windowBStart = $day->copy()->startOfDay();
            $windowBEnd = $day->copy()->setTime(7, 0, 0);
            $nightMinutes += $this->overlapMinutes($start, $end, $windowBStart, $windowBEnd);
        }

        return max(0, $nightMinutes);
    }

    private function overlapMinutes(Carbon $start, Carbon $end, Carbon $windowStart, Carbon $windowEnd): int
    {
        $effectiveStart = $start->greaterThan($windowStart) ? $start : $windowStart;
        $effectiveEnd = $end->lessThan($windowEnd) ? $end : $windowEnd;

        if ($effectiveEnd->lessThanOrEqualTo($effectiveStart)) {
            return 0;
        }

        return max(0, $effectiveStart->diffInMinutes($effectiveEnd));
    }

    private function buildWeeklyRestBreachDateLookup(
        int $companyId,
        Collection $employeeIds,
        Carbon $periodStart,
        Carbon $periodEnd
    ): array {
        if ($employeeIds->isEmpty()) {
            return [];
        }

        $rangeStart = $periodStart->copy()->subDays(6)->toDateString();
        $rangeEnd = $periodEnd->copy()->addDays(6)->toDateString();

        $attendances = Attendance::query()
            ->active()
            ->where('created_by', $companyId)
            ->whereIn('employee_id', $employeeIds->all())
            ->whereDate('date', '>=', $rangeStart)
            ->whereDate('date', '<=', $rangeEnd)
            ->whereNotNull('clock_in')
            ->get(['employee_id', 'date']);

        $lookup = [];

        $grouped = $attendances
            ->groupBy(static fn (Attendance $attendance): int => (int) $attendance->employee_id);

        foreach ($grouped as $employeeId => $rows) {
            $dates = $rows->pluck('date')
                ->map(static fn ($date): string => Carbon::parse($date)->toDateString())
                ->unique()
                ->sort()
                ->values()
                ->all();

            $breachDates = $this->extractDatesInSevenDayStreak($dates);
            foreach ($breachDates as $breachDate) {
                $lookup[(int) $employeeId][$breachDate] = true;
            }
        }

        return $lookup;
    }

    private function extractDatesInSevenDayStreak(array $dates): array
    {
        if ($dates === []) {
            return [];
        }

        $result = [];
        $currentRun = [];
        $previousDate = null;

        foreach ($dates as $date) {
            $currentDate = Carbon::parse($date)->startOfDay();

            if ($previousDate === null) {
                $currentRun = [$currentDate->toDateString()];
                $previousDate = $currentDate;
                continue;
            }

            if ($currentDate->diffInDays($previousDate) === 1) {
                $currentRun[] = $currentDate->toDateString();
            } else {
                if (count($currentRun) >= 7) {
                    foreach ($currentRun as $runDate) {
                        $result[$runDate] = true;
                    }
                }
                $currentRun = [$currentDate->toDateString()];
            }

            $previousDate = $currentDate;
        }

        if (count($currentRun) >= 7) {
            foreach ($currentRun as $runDate) {
                $result[$runDate] = true;
            }
        }

        return array_keys($result);
    }
}
