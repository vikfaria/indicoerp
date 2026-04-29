<?php

namespace Workdo\Hrm\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Attendance;
use Workdo\Hrm\Models\Shift;
use Workdo\Hrm\Models\LeaveApplication;
use Workdo\Hrm\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Workdo\Hrm\Models\IpRestrict;

class AttendanceApiController extends Controller
{
    use ApiResponseTrait;

    public function clockInOut(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:clockin,clockout',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        if ($request->type == 'clockin') {
            return $this->clockIn($request);
        } else {
            return $this->clockOut($request);
        }
    }

    private function clockIn(Request $request)
    {
        try {
            // Check IP restriction
            $setting = getCompanyAllSetting();
            if (isset($setting['ip_restrict']) && $setting['ip_restrict'] === 'on') {
                $userIp = request()->ip();
                $allowedIp = IpRestrict::where('ip', $userIp)
                    ->where('created_by', creatorId())
                    ->exists();
                
                if (!$allowedIp) {
                    return $this->errorResponse('This IP is not allowed to clock in & clock out.');
                }
            }
            $today      = now()->toDateString();
            $employeeId = Auth::id();
            $creatorId  = creatorId();

            // Validate working day, leave, and holiday
            $workingDays      = getCompanyAllSetting($creatorId)['working_days'] ?? '';
            $workingDaysArray = json_decode($workingDays, true) ?? [];
            $isWorkingDay     = in_array(now()->dayOfWeek, $workingDaysArray);

            $isOnLeave = LeaveApplication::where('created_by', $creatorId)
                ->where('employee_id', $employeeId)
                ->where('status', 'approved')
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today)
                ->exists();

            $isHoliday = Holiday::where('created_by', $creatorId)
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today)
                ->exists();

            if (!$isWorkingDay) {
                return $this->errorResponse('Attendance cannot be created for non-working days.');
            }
            if ($isOnLeave) {
                return $this->errorResponse('Employee is on leave for this date.');
            }
            if ($isHoliday) {
                return $this->errorResponse('Attendance cannot be created on holidays.');
            }
            // First check for any pending clock out and complete it
            $pendingClockOuts = Attendance::where('employee_id', $employeeId)
                ->whereNull('clock_out')
                ->where('created_by', $creatorId)
                ->get();

            if ($pendingClockOuts) {
                foreach ($pendingClockOuts as $pendingClockOut) {
                    $employee = Employee::where('user_id', $employeeId)->where('created_by', $creatorId)->first();
                    $shift    = $employee ? Shift::find($employee->shift) : null;

                    if ($shift) {
                        $clockInDate      = Carbon::parse($pendingClockOut->clock_in)->format('Y-m-d');
                        $shiftEndDateTime = Carbon::parse($clockInDate . ' ' . $shift->end_time);

                        // For night shifts, shift end is next day
                        if ($shift->end_time < $shift->start_time) {
                            $shiftEndDateTime->addDay();
                        }

                        // Auto complete previous attendance with shift end time
                        $calculatedData = $this->calculateAttendanceData(
                            $pendingClockOut->clock_in,
                            $shiftEndDateTime,
                            0,
                            $shift->id,
                            $employee
                        );

                        $pendingClockOut->update([
                            'clock_out'       => $shiftEndDateTime,
                            'total_hour'      => $calculatedData['total_hour']['total_working_hours'],
                            'break_hour'      => $calculatedData['total_hour']['total_break_hours'],
                            'overtime_hours'  => $calculatedData['overtime_hours'],
                            'overtime_amount' => $calculatedData['overtime_amount'],
                            'status'          => $calculatedData['status'],
                        ]);
                    }
                }
            }

            // Check if already clocked in today
            $existingAttendance = Attendance::where('employee_id', $employeeId)
                ->where('date', $today)
                ->where('created_by', $creatorId)
                ->first();
            if ($existingAttendance && $existingAttendance->clock_in) {
                return $this->errorResponse('You have already clocked in today.');
            }

            $clockInTime = now();

            if ($existingAttendance) {
                $existingAttendance->update(['clock_in' => $clockInTime]);
                $attendance = $existingAttendance;
            } else {
                $employee = Employee::where('user_id', $employeeId)->where('created_by', $creatorId)->first();
                $shift    = $employee ? $employee->shift : null;


                $attendance = Attendance::create([
                    'employee_id' => $employeeId,
                    'shift_id'    => $shift,
                    'date'        => $today,
                    'clock_in'    => $clockInTime,
                    'creator_id'  => Auth::id(),
                    'created_by'  => $creatorId,
                ]);
            }

            $data = [
                'is_clockin'    => 1,
                'attendance_id' => $attendance->id,
                'clock_in'      => $clockInTime->format('Y-m-d H:i:s'),
                'total_hours'   => "0.00",
            ];
            return $this->successResponse($data, 'Clocked in successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    private function clockOut(Request $request)
    {
        try {
            // Check IP restriction
            $setting = getCompanyAllSetting();
            if (isset($setting['ip_restrict']) && $setting['ip_restrict'] === 'on') {
                $userIp = request()->ip();
                $allowedIp = IpRestrict::where('ip', $userIp)
                    ->where('created_by', creatorId())
                    ->exists();
                
                if (!$allowedIp) {
                    return $this->errorResponse('This IP is not allowed to clock in & clock out.');
                }
            }
            $today      = now()->toDateString();
            $employeeId = Auth::id();
            $creatorId  = creatorId();

            $attendance = Attendance::where('employee_id', $employeeId)
                ->where('date', $today)
                ->where('created_by', $creatorId)
                ->first();

            // If no today's attendance, check for pending attendance from previous days
            if (!$attendance || !$attendance->clock_in) {
                $attendance = Attendance::where('employee_id', $employeeId)
                    ->whereNull('clock_out')
                    ->where('created_by', $creatorId)
                    ->orderBy('clock_in', 'desc')
                    ->first();
            }

            if (!$attendance || !$attendance->clock_in) {
                return $this->errorResponse('You must clock in first.');
            }

            if ($attendance->clock_out) {
                return $this->errorResponse('You have already clocked out today.');
            }

            $clockOutTime = now();
            $employee     = Employee::with('shift')->where('user_id', $employeeId)->where('created_by', $creatorId)->first();
            $shift        = $employee ? $employee->shift : null;

            // Calculate attendance data using existing logic
            $calculatedData = $this->calculateAttendanceData(
                $attendance->clock_in,
                $clockOutTime,
                0,  // break_hour
                $shift,
                $employee
            );

            $attendance->update([
                'clock_out'       => $clockOutTime,
                'total_hour'      => $calculatedData['total_hour']['total_working_hours'],
                'break_hour'      => $calculatedData['total_hour']['total_break_hours'],
                'overtime_hours'  => $calculatedData['overtime_hours'],
                'overtime_amount' => $calculatedData['overtime_amount'],
                'status'          => $calculatedData['status'],
            ]);
            $data = [
                'is_clockin'    => 0,
                'attendance_id' => $attendance->id,
                'clock_in'      => $attendance->clock_in,
                'clock_out'     => $clockOutTime->format('Y-m-d H:i:s'),
                'total_hours'   => $calculatedData['total_hour']['total_working_hours'] . ' hours',

            ];
            return $this->successResponse($data, 'Clocked out successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    // Attendance Calculation Functions (same as web controller)
    private function calculateTotalHours($clockIn, $clockOut, $shift)
    {
        if (!$clockIn || !$clockOut) {
            return 0;
        }

        $clockInTime  = Carbon::parse($clockIn);
        $clockOutTime = Carbon::parse($clockOut);

        // Handle next day clock out (night shifts)
        if ($clockOutTime->lt($clockInTime)) {
            $clockOutTime->addDay();
        }

        $totalMinutes = abs($clockOutTime->diffInMinutes($clockInTime));
        $breakMinutes = 0;
        if ($shift && $shift->break_start_time && $shift->break_end_time) {
            $breakStart = Carbon::parse($shift->break_start_time);
            $breakEnd   = Carbon::parse($shift->break_end_time);

            // Handle next day break times for night shifts
            if ($breakEnd->lt($breakStart)) {
                $breakEnd->addDay();
            }

            //  Only deduct break if employee worked through the break period
            if ($clockInTime->lte($breakStart) && $clockOutTime->gte($breakEnd)) {
                $breakMinutes = $this->breakDuration($shift);
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

        $workingMinutes  = max(0, $totalMinutes - $breakMinutes);
        $calculatedHours = round($workingMinutes / 60, 2);
        $totalBreakHour  = round($breakMinutes / 60, 2);
        $totalHours      = [
            'total_working_hours' => $calculatedHours ?? 0,
            'total_break_hours'   => $totalBreakHour ?? 0,
        ];
        return $totalHours;
    }

    private function breakDuration($shift)
    {
        $breakStart = Carbon::parse($shift->break_start_time);
        $breakEnd   = Carbon::parse($shift->break_end_time);
        if ($breakEnd->lt($breakStart)) {
            $breakEnd->addDay();
        }
        $breakDuration = abs($breakEnd->diffInMinutes($breakStart));

        return $breakDuration;
    }

    private function getWorkingHour($shift)
    {
        $start = Carbon::parse($shift->start_time);
        $end   = Carbon::parse($shift->end_time);

        // Handle night shifts
        if ($shift->is_night_shift && $end->lt($start)) {
            $end->addDay();
        }
        $breakDuration = $this->breakDuration($shift);

        $totalMinutes = abs($end->diffInMinutes($start)) - $breakDuration;
        return round(max(0, $totalMinutes) / 60, 2);
    }

    private function calculateAttendanceData($clockIn, $clockOut, $breakHour, $shift, $employee)
    {
        $shift = Shift::where('id', $shift)->where('created_by', creatorId())->first();
        // Step 1: Calculate total working hours
        $totalHourData = $this->calculateTotalHours($clockIn, $clockOut, $shift);
        $totalHour     = $totalHourData['total_working_hours'];
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
            'total_hour'      => $totalHourData,
            'overtime_hours'  => $overtimeHours,
            'overtime_amount' => $overtimeAmount,
            'status'          => $status,
        ];
    }

    public function history(Request $request)
    {
        try {
            $employeeId = Auth::id();
            $creatorId  = creatorId();

            $attendances = Attendance::where('employee_id', $employeeId)->where('created_by', $creatorId);

            if ($request->type == 'monthly' && !empty($request->month)) {
                $month      = $request->month;
                $year       = !empty($request->year) ? $request->year : date('Y');
                $start_date = date("$year-$month-01");
                $end_date   = date("$year-$month-t");
                $attendances->whereBetween('date', [$start_date, $end_date]);
            } else {
                $month      = date('m');
                $year       = date('Y');
                $start_date = date($year . '-' . $month . '-01');
                $end_date   = date($year . '-' . $month . '-t');
                $attendances->whereBetween('date', [$start_date, $end_date]);
            }

            $formattedData = [];
            $employee      = Employee::with('shift')->where('user_id', $employeeId)->where('created_by', $creatorId)->first();
            $shift         = $employee ? $employee->shift : null;

            foreach ($attendances->get() as $attendance) {
                $date = $attendance->date->format('Y-m-d');

                if (!$attendance->clock_out) {
                    $calculatedData  = $this->calculateAttendanceData(
                        $attendance->clock_in,
                        now(),
                        0,
                        $shift,
                        $employee
                    );
                    $totalTimeString = $calculatedData['total_hour']['total_working_hours'] . ' hours';
                } else {
                    $totalTimeString = $attendance->total_hour . ' hours';
                }

                $formattedData[] = [
                    'date'      => $date,
                    'id'        => $attendance->id,
                    'status'    => $attendance->status,
                    'clock_in'  => $attendance->clock_in,
                    'clock_out' => $attendance->clock_out,
                    'total_hours'     => $totalTimeString,
                ];
            }

            return $this->successResponse($formattedData, 'Attendance history retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }
}