<?php

namespace Workdo\Hrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\Attendance;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Shift;
use Workdo\Hrm\Models\LeaveApplication;
use App\Models\User;
use Carbon\Carbon;

class DemoAttendanceSeeder extends Seeder
{
    public function run($userId)
    {
        if (Attendance::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        // Validate userId
        if (!$userId || !is_numeric($userId)) {
            return;
        }

        // Get employees with proper validation
        $employees = Employee::where('created_by', $userId)
            ->whereHas('user')
            ->with(['user'])
            ->get();

        if ($employees->isEmpty()) {
            return;
        }

        $months = [7, 8, 9, 10]; // July, August, September, October
        $year = 2025;

        // Process month-wise for better organization
        foreach ($months as $month) {
            $startDate = Carbon::create($year, $month, 1);
            $endDate = $startDate->copy()->endOfMonth();

            // Iterate through each day of the month
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                // Skip weekends (Saturday = 6, Sunday = 0)
                if ($date->dayOfWeek == 0 || $date->dayOfWeek == 6) {
                    continue;
                }

                // Process each employee for this date
                foreach ($employees as $employeeIndex => $employee) {
                    // Skip if employee user doesn't exist
                    if (!$employee->user_id || !$employee->user) {
                        continue;
                    }

                    // Check if employee has approved leave for this date
                    $hasApprovedLeave = LeaveApplication::where('employee_id', $employee->user_id)
                        ->where('status', 'approved')
                        ->where('start_date', '<=', $date->format('Y-m-d'))
                        ->where('end_date', '>=', $date->format('Y-m-d'))
                        ->where('created_by', $userId)
                        ->exists();

                    // Skip attendance if employee has approved leave
                    if ($hasApprovedLeave) {
                        continue;
                    }

                    // Get employee's shift
                    $shift = Shift::where('id', $employee->shift)
                        ->where('created_by', $userId)
                        ->first();

                    if (!$shift) {
                        continue;
                    }

                    // Generate dynamic attendance pattern
                    $dayOfMonth = $date->day;
                    $attendancePattern = ($employeeIndex + $dayOfMonth) % 10;

                    // Determine attendance status dynamically - always create attendance record
                    if ($attendancePattern <= 6) { // 70% Present
                        $clockIn = $shift->start_time;
                        // 20% chance of overtime
                        $hasOvertime = ($employeeIndex + $dayOfMonth) % 5 == 0;
                        $clockOut = $hasOvertime ?
                            Carbon::parse($shift->end_time)->addHours(1.5)->format('H:i:s') :
                            $shift->end_time;
                    } elseif ($attendancePattern <= 8) { // 20% Half day
                        $clockIn = $shift->start_time;
                        $shiftMidTime = Carbon::parse($shift->start_time)->addHours(4);
                        $clockOut = $shiftMidTime->format('H:i:s');
                    } else { // 10% Absent - create absent record
                        $clockIn = $shift->start_time;
                        $clockOut = $shift->start_time; // Same time = 0 hours worked
                    }

                    // Calculate attendance data using controller functions
                    $calculatedData = $this->calculateAttendanceData(
                        $clockIn,
                        $clockOut,
                        0, // break_hour
                        $employee->shift,
                        $employee,
                        $userId
                    );

                    // Create or update attendance record
                    Attendance::updateOrCreate(
                        [
                            'employee_id' => $employee->user_id,
                            'date' => $date->format('Y-m-d'),
                            'created_by' => $userId
                        ],
                        [
                            'shift_id' => $employee->shift,
                            'clock_in' => $date->format('Y-m-d') . ' ' . $clockIn,
                            'clock_out' => $date->format('Y-m-d') . ' ' . $clockOut,
                            'break_hour' => $calculatedData['total_hour']['total_break_hours'],
                            'total_hour' => $calculatedData['total_hour']['total_working_hours'],
                            'overtime_hours' => $calculatedData['overtime_hours'],
                            'overtime_amount' => $calculatedData['overtime_amount'],
                            'status' => $calculatedData['status'],
                            'notes' => 'Demo attendance record',
                            'creator_id' => $userId,
                        ]
                    );
                }
            }
        }
    }

    private function calculateTotalHours($clockIn, $clockOut, $shift)
    {
        if (!$clockIn || !$clockOut) {
            return [
                'total_working_hours' => 0,
                'total_break_hours' => 0,
            ];
        }

        $clockInTime = \Carbon\Carbon::parse($clockIn);
        $clockOutTime = \Carbon\Carbon::parse($clockOut);

        // Handle next day clock out (night shifts)
        if ($clockOutTime->lt($clockInTime)) {
            $clockOutTime->addDay();
        }

        $totalMinutes = abs($clockOutTime->diffInMinutes($clockInTime));
        $breakMinutes = 0;

        if ($shift && $shift->break_start_time && $shift->break_end_time) {
            $breakStart = \Carbon\Carbon::parse($shift->break_start_time);
            $breakEnd = \Carbon\Carbon::parse($shift->break_end_time);

            // Handle next day break times for night shifts
            if ($breakEnd->lt($breakStart)) {
                $breakEnd->addDay();
            }

            //  Only deduct break if employee worked through the break period
            if ($clockInTime->lte($breakStart) && $clockOutTime->gte($breakEnd)) {
                $breakMinutes = $this->breakDuration(shift: $shift);
            } elseif ($clockInTime->lte($breakStart) && $clockOutTime->gt($breakStart) && $clockOutTime->lte($breakEnd)) {
                // Left during break - deduct time spent on break
                $breakMinutes = abs($clockOutTime->diffInMinutes($breakStart));
            } elseif ($clockInTime->gt($breakStart) && $clockInTime->lt($breakEnd) && $clockOutTime->gte($breakEnd)) {
                // Came during break - deduct partial break (missed part of break)
                $breakMinutes = abs($breakEnd->diffInMinutes($clockInTime));
            } elseif ($clockInTime->gt($breakStart) && $clockOutTime->lt($breakEnd)) {
                // Came and left during break - no break deduction
                $breakMinutes = 0;
            }
        }

        $workingMinutes = max(0, $totalMinutes - $breakMinutes);
        $calculatedHours =   round($workingMinutes / 60, 2);
        $totalBreakHour =   round($breakMinutes / 60, 2);
        $totalHours = [
            'total_working_hours' => $calculatedHours ?? 0,
            'total_break_hours' => $totalBreakHour ?? 0,
        ];
        return $totalHours;
    }

    private function breakDuration($shift)
    {
        $breakStart = \Carbon\Carbon::parse($shift->break_start_time);
        $breakEnd = \Carbon\Carbon::parse($shift->break_end_time);
        if ($breakEnd->lt($breakStart)) {
            $breakEnd->addDay();
        }
        $breakDuration = abs($breakEnd->diffInMinutes($breakStart));

        return $breakDuration;
    }

    private function getWorkingHour($shift)
    {
        $start = \Carbon\Carbon::parse($shift->start_time);
        $end = \Carbon\Carbon::parse($shift->end_time);

        // Handle night shifts
        if ($shift->is_night_shift && $end->lt($start)) {
            $end->addDay();
        }
        $breakDuration = $this->breakDuration($shift);

        $totalMinutes = abs($end->diffInMinutes($start)) - $breakDuration;
        return round(max(0, $totalMinutes) / 60, 2);
    }

    private function calculateAttendanceData($clockIn, $clockOut, $breakHour, $shiftId, $employee, $userId)
    {
        $shift = Shift::where('id', $shiftId)->where('created_by', $userId)->first();
        
        // Step 1: Calculate total working hours
        $totalHourData = $this->calculateTotalHours($clockIn, $clockOut, $shift);
        $totalHour = $totalHourData['total_working_hours'];

        // Step 2: Calculate overtime
        $standardHours = ($shift && $this->getWorkingHour($shift) > 0) ? $this->getWorkingHour($shift) : 8;
        $overtimeHours = max(0, round($totalHour - $standardHours, 2));

        // Step 3: Calculate overtime amount
        $overtimeAmount = 0;
        if ($overtimeHours > 0 && $employee && $employee->rate_per_hour) {
            $overtimeAmount = round($overtimeHours * ($employee->rate_per_hour), 2);
        }

        // Step 4: Determine status
        $status = 'absent';
        if ($totalHour > 0) {
            $halfDayThreshold = $standardHours / 2;
            if ($totalHour >= $standardHours) {
                $status = 'present';
            } elseif ($totalHour >= $halfDayThreshold) {
                $status = 'half day';
            } else {
                $status = 'absent';
            }
        }

        return [
            'total_hour' => $totalHourData,
            'overtime_hours' => $overtimeHours,
            'overtime_amount' => $overtimeAmount,
            'status' => $status,
        ];
    }
}
