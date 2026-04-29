<?php

namespace Workdo\Hrm\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Attendance;
use Workdo\Hrm\Models\Announcement;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Workdo\Hrm\Models\Event;
use Workdo\Hrm\Models\Holiday;
use Workdo\Hrm\Models\LeaveApplication;
use Workdo\Hrm\Models\Shift;

class DashboardApiController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        try {
            $userId    = Auth::user()->id;
            $creatorId = creatorId();
            $date      = Carbon::today();

            $employee = Employee::where('user_id', $userId)->where('created_by', $creatorId)->first();
            $shift    = $employee ? Shift::find($employee->shift) : null;

            // Check for pending attendance (including night shifts)
            $pendingAttendance = Attendance::where('created_by', $creatorId)
                ->where('employee_id', $userId)
                ->whereNotNull('clock_in')
                ->whereNull('clock_out')
                ->orderBy('clock_in', 'desc')
                ->first();

            // Today's Attendance for Clock In/Out
            $todayAttendance = Attendance::where('created_by', $creatorId)
                ->where('employee_id', $userId)
                ->where('date', $date)
                ->first();

            // Check if clock in/out should be allowed
            $workingDays      = getCompanyAllSetting($creatorId)['working_days'] ?? '';
            $workingDaysArray = json_decode($workingDays, true) ?? [];
            $todayDayIndex    = $date->dayOfWeek;
            $isWorkingDay     = in_array($todayDayIndex, $workingDaysArray);

            $todayLeaves = LeaveApplication::where('created_by', $creatorId)
                ->where('employee_id', $userId)
                ->where('status', 'approved')
                ->where('start_date', '<=', $date)
                ->where('end_date', '>=', $date)
                ->get();

            $isOnLeave = $todayLeaves->count() > 0;

            $leaves = LeaveApplication::where('created_by', $creatorId)
                ->where('employee_id', $userId)
                ->where('status', 'approved')
                ->where('start_date', '<=', $date)
                ->where('end_date', '>=', $date)
                ->get()
                ->map(function ($leave) {
                    return [
                        'id'         => $leave->id,
                        'title'      => $leave->leave_type->name,
                        'start_date' => $leave->start_date->format('Y-m-d'),
                        'end_date'   => $leave->end_date->format('Y-m-d'),
                        'reason'     => $leave->reason ?? ''
                    ];
                });

            $todayHolidays = Holiday::where('created_by', $creatorId)
                ->where('start_date', '<=', $date)
                ->where('end_date', '>=', $date)
                ->get();

            $isHoliday = $todayHolidays->count() > 0;

            $holidays = Holiday::where('created_by', $creatorId)
                ->where('start_date', '<=', $date)
                ->where('end_date', '>=', $date)
                ->get()
                ->map(function ($holiday) {
                    return [
                        'id'         => $holiday->id,
                        'name'       => $holiday->name,
                        'start_date' => $holiday->start_date->format('Y-m-d'),
                        'end_date'   => $holiday->end_date->format('Y-m-d'),
                    ];
                });

            // Determine if user is currently clocked in (including night shifts)
            $isCurrentlyClockedIn = false;

            $activeAttendance = $todayAttendance;

            if ($pendingAttendance && $shift) {
                // Get shift duration in hours
                $shiftStart = Carbon::parse($shift->start_time);
                $shiftEnd   = Carbon::parse($shift->end_time);

                // Handle night shift duration
                if ($shiftEnd->lt($shiftStart)) {
                    $shiftEnd->addDay();
                }
                $shiftDurationHours = $shiftStart->diffInHours($shiftEnd);

                // Calculate shift end datetime from clock in time
                $clockInDateTime  = Carbon::parse($pendingAttendance->clock_in);
                $shiftEndDateTime = $clockInDateTime->copy()->addHours($shiftDurationHours);
                // Check if current date is within shift duration dates
                $now          = Carbon::now();
                $clockInDate  = $clockInDateTime->format('Y-m-d');
                $shiftEndDate = $shiftEndDateTime->format('Y-m-d');
                $nowDate      = $now->format('Y-m-d');

                // Allow clock out on clock in date or shift end date
                if ($nowDate >= $clockInDate && $nowDate <= $shiftEndDate) {
                    $isCurrentlyClockedIn = true;
                    $activeAttendance     = $pendingAttendance;
                }
            }


            $attendance = Attendance::where('employee_id', $userId)
                ->where('date', $date)
                ->where('created_by', $creatorId)
                ->orderBy('id', 'desc')
                ->first();


            $announcements = Announcement::where('created_by', $creatorId)
                ->where('status', 'active')
                ->where('start_date', '<=', $date)
                ->where('end_date', '>=', $date)
                ->get()
                ->map(function ($announcement) {
                    return [
                        "id"          => $announcement->id,
                        "title"       => $announcement->title,
                        "start_date"  => $announcement->start_date,
                        "end_date"    => $announcement->end_date,
                        "description" => $announcement->description,
                    ];
                });
            $shiftData =  [
                'id'         => $shift->id,
                'shift_name' => $shift->shift_name,
                'start_time' => $shift->start_time,
                'end_time'   => $shift->end_time,
            ];

            // Get working day names as key:value pairs
            $workingDayNames = [];
            $dayNames        = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            foreach ($workingDaysArray as $dayIndex) {
                if (isset($dayNames[$dayIndex])) {
                    $workingDayNames[$dayIndex] = $dayNames[$dayIndex];
                }
            }
            $data = [
                'is_clockin'     => $isCurrentlyClockedIn ? 1 : 0,
                'attendance_id'  => $isCurrentlyClockedIn ? $attendance->id : 0,
                'clock_in'       => $activeAttendance ? $activeAttendance->clock_in : null,
                'clock_out'      => $activeAttendance ? $activeAttendance->clock_out : null,
                'can_clock'      => $isWorkingDay && !$isOnLeave && !$isHoliday,
                'total_hour'     => $activeAttendance && $activeAttendance->total_hour ? $activeAttendance->total_hour . ' hours' : null,
                'is_holiday'     => $isHoliday,
                'is_on_leave'    => $isOnLeave,
                'is_working_day' => $isWorkingDay,
                'announcements'  => $announcements,
                'holidays'       => $holidays,
                'leaves'         => $leaves,
                'shift'          => $shiftData,
                'working_days'   => $workingDayNames
            ];

            return $this->successResponse($data, 'Dashboard data retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }
    public function getEvents(Request $request)
    {
        try {
            if (Auth::user()->can('manage-events')) {
                $month  = $request->month;
                $year   = $request->year;
                $events = Event::query()
                    ->with(['eventType', 'approvedBy', 'departments'])
                    ->where(function ($q) {
                        if (Auth::user()->can('manage-any-events')) {
                            $q->where('created_by', creatorId());
                        } elseif (Auth::user()->can('manage-own-events')) {
                            $employee = Employee::where('user_id', Auth::id())->first();
                            if ($employee && $employee->department_id) {
                                $q->whereHas('departments', function ($query) use ($employee) {
                                    $query->where('department_id', $employee->department_id)
                                        ->where('status', 'approved');
                                });
                            } else {
                                $q->whereRaw('1 = 0');
                            }
                        } else {
                            $q->whereRaw('1 = 0');
                        }
                    })
                    ->when($month && $year, function ($query) use ($month, $year) {
                        $query->where(function ($q) use ($month, $year) {
                            $q->whereMonth('start_date', $month)
                                ->whereYear('start_date', $year)
                                ->orWhere(function ($subQ) use ($month, $year) {
                                    $subQ->whereMonth('end_date', $month)
                                        ->whereYear('end_date', $year);
                                });
                        });
                    })
                    ->latest()
                    ->get()
                    ->map(function ($event) {
                        return [
                            'id'          => $event->id,
                            'title'       => $event->title,
                            'start_date'  => $event->start_date->format('Y-m-d'),
                            'end_date'    => $event->end_date->format('Y-m-d'),
                            'color'       => $event->color,
                            'description' => $event->description,
                            'approvedBy'  => $event->approvedBy,
                            'eventType'   => $event->eventType,
                            'departments' => $event->departments
                        ];
                    });

                return $this->successResponse($events, 'Events retrieved successfully');
            } else {
                return $this->errorResponse('Permission denied');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }
}
