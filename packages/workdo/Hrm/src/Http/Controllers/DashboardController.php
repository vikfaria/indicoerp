<?php

namespace Workdo\Hrm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Hrm\Models\HrmItem;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Attendance;
use Workdo\Hrm\Models\LeaveApplication;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;
use Workdo\Hrm\Models\Promotion;
use Workdo\Hrm\Models\Termination;
use Carbon\Carbon;
use Workdo\Hrm\Models\Announcement;
use Workdo\Hrm\Models\Award;
use Workdo\Hrm\Models\Complaint;
use Workdo\Hrm\Models\Event;
use Workdo\Hrm\Models\Holiday;
use Workdo\Hrm\Models\Shift;
use Workdo\Hrm\Models\Warning;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-hrm-dashboard')) {
            $user = Auth::user();

            switch ($user->type) {
                case 'company':
                    return $this->companyDashboard($request);
                case 'hr':
                    return $this->companyDashboard($request);
                default:
                    return $this->employeeDashboard($request);
            }
        }
        return back()->with('error', __('Permission denied'));
    }


    private function companyDashboard(Request $request)
    {
        $creatorId = creatorId();
        $today = Carbon::today();

        // Total Employees
        $totalEmployees = Employee::where('created_by', $creatorId)->count();

        // Present Today (employees with attendance today)
        $presentToday = Attendance::where('created_by', $creatorId)
            ->where('date', $today)
            ->whereNotNull('clock_in')
            ->distinct('employee_id')
            ->count();

        // Absent Today (employees with attendance status 'absent' today)
        $absentToday = Attendance::where('created_by', $creatorId)
            ->where('date', $today)
            ->where('status', 'absent')
            ->distinct('employee_id')
            ->count();

        // On Leave (approved leave applications for today)
        $onLeave = LeaveApplication::where('created_by', $creatorId)
            ->where('status', 'approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->count();

        // Yesterday's absent count for comparison
        $yesterday = Carbon::yesterday();

        $absentYesterday = Attendance::where('created_by', $creatorId)
            ->where('date', $yesterday)
            ->where('status', 'absent')
            ->distinct('employee_id')
            ->count();

        // Pending leave applications (current month)
        $pendingLeaves = LeaveApplication::where('created_by', $creatorId)
            ->where('status', 'pending')
            ->whereMonth('start_date', $today->month)
            ->whereYear('start_date', $today->year)
            ->count();

        // Total Branches
        $totalBranches = Branch::where('created_by', $creatorId)->count();

        // Total Departments
        $totalDepartments = Department::where('created_by', $creatorId)->count();

        // Total Promotions (current month)
        $totalPromotions = Promotion::where('created_by', $creatorId)
            ->whereMonth('effective_date', $today->month)
            ->whereYear('effective_date', $today->year)
            ->count();

        // Terminations (current month with accepted status)
        $terminations = Termination::where('created_by', $creatorId)
            ->where('status', 'approved')
            ->whereMonth('termination_date', $today->month)
            ->whereYear('termination_date', $today->year)
            ->count();

        $isDemo = config('app.is_demo');

        // Department Distribution (employee count per department)
        $departments = Department::where('created_by', $creatorId)
            ->with('branch')
            ->withCount(['employees' => function ($query) use ($creatorId) {
                $query->where('created_by', $creatorId);
            }])
            ->get();

        $departmentDistribution = $departments->map(function ($dept) use ($isDemo) {
            $count = $dept->employees_count;
            if ($isDemo && $count == 0) {
                $count = rand(5, 20);
            }
            return [
                'name' => $dept->department_name . ' (' . ($dept->branch->branch_name ?? 'Unknown') . ')',
                'value' => $count
            ];
        });


        // Employees Without Attendance Today
        $attendedEmployeeIds = Attendance::where('created_by', $creatorId)
            ->where('date', $today)
            ->pluck('employee_id')
            ->toArray();

        $absentEmployees = Employee::where('created_by', $creatorId)
            ->whereNotIn('user_id', $attendedEmployeeIds)
            ->with(['user', 'department'])
            ->get();

        // Employees on Leave Today
        $leavesToday = LeaveApplication::with('employee')->where('created_by', $creatorId)
            ->where('status', 'approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->with(['employee', 'leave_type'])
            ->get();

        if ($isDemo && $leavesToday->isEmpty()) {
            $leaveTypes = ['Medical Leave', 'Annual Leave', 'Casual Leave'];
            $leaveCount = min(rand(2, 5), $absentEmployees->count());
            $demoLeaves = $absentEmployees->random($leaveCount);
            
            $employeesOnLeaveToday = $demoLeaves->map(function ($employee) use ($leaveTypes) {
                return [
                    'name' => $employee->user->name ?? 'Unknown',
                    'profile' => $employee->user->avatar ?? '',
                    'leave_type' => $leaveTypes[array_rand($leaveTypes)],
                    'days' => rand(1, 5)
                ];
            });
        } else {
            $employeesOnLeaveToday = $leavesToday->map(function ($leave) {
                return [
                    'name' => $leave->employee->name ?? 'Unknown',
                    'profile' => $leave->employee->avatar ?? '',
                    'leave_type' => $leave->leave_type->name ?? 'Unknown',
                    'days' => $leave->total_days
                ];
            });
        }

        $employeesWithoutAttendance = $absentEmployees->map(function ($employee) {
            return [
                'employee_id' => $employee->employee_id ?? 'Unknown',
                'profile' =>  $employee->user->avatar ?? '',
                'name' => $employee->user->name ?? 'Unknown',
                'department' => $employee->department->department_name ?? 'Unknown'
            ];
        });

        // Events and Holidays for Calendar
        $dbEvents = Event::where('created_by', $creatorId)
            ->where('status', 'approved')
            ->with('eventType')
            ->get();

        $dbHolidays = Holiday::where('created_by', $creatorId)->get();

        if ($isDemo) {
            $calendarEvents = collect([
                // Previous Month
                ['id' => 101, 'title' => 'Project Kickoff', 'startDate' => Carbon::now()->subMonth()->day(5)->format('Y-m-d'), 'endDate' => Carbon::now()->subMonth()->day(5)->format('Y-m-d'), 'time' => '10:00 AM', 'description' => 'New project start meeting', 'type' => 'event', 'color' => '#3b82f6'],
                ['id' => 102, 'title' => 'Monthly Review', 'startDate' => Carbon::now()->subMonth()->day(25)->format('Y-m-d'), 'endDate' => Carbon::now()->subMonth()->day(25)->format('Y-m-d'), 'time' => '03:00 PM', 'description' => 'Last month performance review', 'type' => 'event', 'color' => '#8b5cf6'],
                ['id' => 103, 'title' => 'Company Anniversary', 'startDate' => Carbon::now()->subMonth()->day(12)->format('Y-m-d'), 'endDate' => Carbon::now()->subMonth()->day(12)->format('Y-m-d'), 'time' => '', 'description' => 'Celebration day', 'type' => 'holiday', 'color' => '#ef4444'],

                // Current Month
                ['id' => 1, 'title' => 'Team Brainstorming', 'startDate' => Carbon::now()->day(8)->format('Y-m-d'), 'endDate' => Carbon::now()->day(8)->format('Y-m-d'), 'time' => '11:00 AM', 'description' => 'Strategy planning', 'type' => 'event', 'color' => '#10b77f'],
                ['id' => 2, 'title' => 'Technical Interview', 'startDate' => Carbon::now()->day(15)->format('Y-m-d'), 'endDate' => Carbon::now()->day(15)->format('Y-m-d'), 'time' => '02:00 PM', 'description' => 'Hiring session', 'type' => 'event', 'color' => '#8b5cf6'],
                ['id' => 3, 'title' => 'Staff Birthday', 'startDate' => Carbon::now()->day(21)->format('Y-m-d'), 'endDate' => Carbon::now()->day(21)->format('Y-m-d'), 'time' => '', 'description' => 'Office cake cutting', 'type' => 'holiday', 'color' => '#ef4444'],

                // Next Month
                ['id' => 201, 'title' => 'Budgeting Session', 'startDate' => Carbon::now()->addMonth()->day(10)->format('Y-m-d'), 'endDate' => Carbon::now()->addMonth()->day(10)->format('Y-m-d'), 'time' => '09:00 AM', 'description' => 'Quarterly budget discussion', 'type' => 'event', 'color' => '#f59e0b'],
                ['id' => 202, 'title' => 'Client Workshop', 'startDate' => Carbon::now()->addMonth()->day(18)->format('Y-m-d'), 'endDate' => Carbon::now()->addMonth()->day(18)->format('Y-m-d'), 'time' => '01:00 PM', 'description' => 'Feedback and training', 'type' => 'event', 'color' => '#3b82f6'],
                ['id' => 203, 'title' => 'Gazetted Holiday', 'startDate' => Carbon::now()->addMonth()->day(25)->format('Y-m-d'), 'endDate' => Carbon::now()->addMonth()->day(25)->format('Y-m-d'), 'time' => '', 'description' => 'Government holiday', 'type' => 'holiday', 'color' => '#ef4444'],
            ]);
        } else {
            $events = $dbEvents->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'startDate' => $event->start_date,
                    'endDate' => $event->end_date,
                    'time' => $event->start_time ?? '',
                    'description' => $event->description ?? '',
                    'type' => $event->eventType->event_type ?? 'event',
                    'color' => $event->color ?? '#3b82f6'
                ];
            });

            $holidays = $dbHolidays->map(function ($holiday) {
                return [
                    'id' => $holiday->id,
                    'title' => $holiday->name,
                    'startDate' => $holiday->start_date,
                    'endDate' => $holiday->end_date,
                    'time' => '',
                    'description' => $holiday->description ?? '',
                    'type' => 'holiday',
                    'color' => '#ef4444'
                ];
            });

            $calendarEvents = $events->merge($holidays);
        }

        // Recent Leave Applications
        $recentLeaveApplications = LeaveApplication::where('created_by', $creatorId)
            ->with(['employee', 'leave_type'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'employee_name' => $leave->employee->name ?? 'Unknown',
                    'leave_type' => $leave->leave_type->name ?? 'Unknown',
                    'start_date' => $leave->start_date,
                    'end_date' => $leave->end_date,
                    'total_days' => $leave->total_days,
                    'status' => $leave->status,
                    'created_at' => $leave->created_at
                ];
            });

        // Recent Announcements (active between today's date)
        $recentAnnouncements = Announcement::where('created_by', $creatorId)
            ->where('status', 'active')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->latest()
            ->get()
            ->map(function ($announcement) {
                return [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'description' => $announcement->description ?? '',
                    'created_at' => $announcement->created_at
                ];
            });

        return Inertia::render('Hrm/Dashboard/company-dashboard', [
            'stats' => [
                'total_employees' => $totalEmployees,
                'present_today' => $isDemo ? rand(8, 10) : $presentToday,
                'absent_today' => $isDemo ? rand(0, 2) : $absentToday,
                'absent_yesterday' => $isDemo ? rand(0, 3) : $absentYesterday,
                'on_leave' => $isDemo ? rand(1, 5) : $onLeave,
                'pending_leaves' => $isDemo ? rand(1, 4) : $pendingLeaves,
                'total_branches' => $totalBranches,
                'total_departments' => $totalDepartments,
                'total_promotions' => $isDemo ? rand(1, 3) : $totalPromotions,
                'terminations' => $isDemo ? rand(0, 1) : $terminations,
                'department_distribution' => $departmentDistribution,
                'calendar_events' => $calendarEvents,
                'recent_leave_applications' => $recentLeaveApplications,
                'recent_announcements' => $recentAnnouncements,
                'employees_on_leave_today' => $employeesOnLeaveToday,
                'employees_without_attendance' => $employeesWithoutAttendance,
            ],
            'message' => __('HRM Dashboard - Complete overview of your workforce.')
        ]);
    }

    private function employeeDashboard(Request $request)
    {
        $userId = Auth::id();
        $creatorId = creatorId();
        $today = Carbon::today();
        $currentYear = $today->year;
        $currentMonth = $today->month;

        // My Attendance (this month)
        $myAttendance = Attendance::where('created_by', $creatorId)
            ->where('employee_id', $userId)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->whereNotNull('clock_in')
            ->count();

        $isDemo = config('app.is_demo');

        // Total Approved Leave (this year)
        $totalApprovedLeaveYear = LeaveApplication::where('created_by', $creatorId)
            ->where('employee_id', $userId)
            ->where('status', 'approved')
            ->whereYear('start_date', $currentYear)
            ->sum('total_days');

        // Total Approved Leave (this month)
        $totalApprovedLeaveMonth = LeaveApplication::where('created_by', $creatorId)
            ->where('employee_id', $userId)
            ->where('status', 'approved')
            ->whereMonth('start_date', $currentMonth)
            ->whereYear('start_date', $currentYear)
            ->sum('total_days');

        // Pending Requests
        $pendingRequests = LeaveApplication::where('created_by', $creatorId)
            ->where('employee_id', $userId)
            ->where('status', 'pending')
            ->whereMonth('start_date', $currentMonth)
            ->whereYear('start_date', $currentYear)
            ->count();

        // Total Absent Days (this month)
        $totalAbsentDays = Attendance::where('created_by', $creatorId)
            ->where('employee_id', $userId)
            ->where('status', 'absent')
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->count();

        // Total Awards (this month)
        $totalAwards = Award::where('created_by', $creatorId)
            ->where('employee_id', $userId)
            ->whereMonth('award_date', $currentMonth)
            ->whereYear('award_date', $currentYear)
            ->count();

        // Total Warnings (this year)
        $totalWarnings = Warning::where('created_by', $creatorId)
            ->where('employee_id', $userId)
            ->whereYear('warning_date', $currentYear)
            ->count();

        // Total Complaints (this year)
        $totalComplaints = Complaint::where('created_by', $creatorId)
            ->where('employee_id', $userId)
            ->whereYear('complaint_date', $currentYear)
            ->count();

        // Events and Holidays for Calendar
        $dbEvents = Event::where('created_by', $creatorId)
            ->where('status', 'approved')
            ->with('eventType')
            ->get();

        $dbHolidays = Holiday::where('created_by', $creatorId)->get();

        if ($isDemo) {
            $calendarEvents = collect([
                // Previous Month
                ['id' => 101, 'title' => 'Project Kickoff', 'startDate' => Carbon::now()->subMonth()->day(5)->format('Y-m-d'), 'endDate' => Carbon::now()->subMonth()->day(5)->format('Y-m-d'), 'time' => '10:00 AM', 'description' => 'New project start meeting', 'type' => 'event', 'color' => '#3b82f6'],
                ['id' => 102, 'title' => 'Monthly Review', 'startDate' => Carbon::now()->subMonth()->day(25)->format('Y-m-d'), 'endDate' => Carbon::now()->subMonth()->day(25)->format('Y-m-d'), 'time' => '03:00 PM', 'description' => 'Last month performance review', 'type' => 'event', 'color' => '#8b5cf6'],
                ['id' => 103, 'title' => 'Company Anniversary', 'startDate' => Carbon::now()->subMonth()->day(12)->format('Y-m-d'), 'endDate' => Carbon::now()->subMonth()->day(12)->format('Y-m-d'), 'time' => '', 'description' => 'Celebration day', 'type' => 'holiday', 'color' => '#ef4444'],

                // Current Month
                ['id' => 1, 'title' => 'Team Brainstorming', 'startDate' => Carbon::now()->day(8)->format('Y-m-d'), 'endDate' => Carbon::now()->day(8)->format('Y-m-d'), 'time' => '11:00 AM', 'description' => 'Strategy planning', 'type' => 'event', 'color' => '#10b77f'],
                ['id' => 2, 'title' => 'Technical Interview', 'startDate' => Carbon::now()->day(15)->format('Y-m-d'), 'endDate' => Carbon::now()->day(15)->format('Y-m-d'), 'time' => '02:00 PM', 'description' => 'Hiring session', 'type' => 'event', 'color' => '#8b5cf6'],
                ['id' => 3, 'title' => 'Staff Birthday', 'startDate' => Carbon::now()->day(21)->format('Y-m-d'), 'endDate' => Carbon::now()->day(21)->format('Y-m-d'), 'time' => '', 'description' => 'Office cake cutting', 'type' => 'holiday', 'color' => '#ef4444'],

                // Next Month
                ['id' => 201, 'title' => 'Budgeting Session', 'startDate' => Carbon::now()->addMonth()->day(10)->format('Y-m-d'), 'endDate' => Carbon::now()->addMonth()->day(10)->format('Y-m-d'), 'time' => '09:00 AM', 'description' => 'Quarterly budget discussion', 'type' => 'event', 'color' => '#f59e0b'],
                ['id' => 202, 'title' => 'Client Workshop', 'startDate' => Carbon::now()->addMonth()->day(18)->format('Y-m-d'), 'endDate' => Carbon::now()->addMonth()->day(18)->format('Y-m-d'), 'time' => '01:00 PM', 'description' => 'Feedback and training', 'type' => 'event', 'color' => '#3b82f6'],
                ['id' => 203, 'title' => 'Gazetted Holiday', 'startDate' => Carbon::now()->addMonth()->day(25)->format('Y-m-d'), 'endDate' => Carbon::now()->addMonth()->day(25)->format('Y-m-d'), 'time' => '', 'description' => 'Government holiday', 'type' => 'holiday', 'color' => '#ef4444'],
            ]);
        } else {
            $events = $dbEvents->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'startDate' => $event->start_date,
                    'endDate' => $event->end_date,
                    'time' => $event->start_time ?? '',
                    'description' => $event->description ?? '',
                    'type' => $event->eventType->event_type ?? 'event',
                    'color' => $event->color ?? '#3b82f6'
                ];
            });

            $holidays = $dbHolidays->map(function ($holiday) {
                return [
                    'id' => $holiday->id,
                    'title' => $holiday->name,
                    'startDate' => $holiday->start_date,
                    'endDate' => $holiday->end_date,
                    'time' => '',
                    'description' => $holiday->description ?? '',
                    'type' => 'holiday',
                    'color' => '#ef4444'
                ];
            });

            $calendarEvents = $events->merge($holidays);
        }

        // Recent Announcements (active between today's date)
        $recentAnnouncements = Announcement::where('created_by', $creatorId)
            ->where('status', 'active')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->latest()
            ->get()
            ->map(function ($announcement) {
                return [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'description' => $announcement->description ?? '',
                    'created_at' => $announcement->created_at
                ];
            });

        // Recent Leave Applications for Employee
        $recentLeaveApplications = LeaveApplication::where('created_by', $creatorId)
            ->where('employee_id', $userId)
            ->with('leave_type')
            ->latest()
            ->get()
            ->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'leave_type' => $leave->leave_type->name ?? 'Unknown',
                    'start_date' => $leave->start_date,
                    'end_date' => $leave->end_date,
                    'total_days' => $leave->total_days,
                    'status' => $leave->status,
                    'created_at' => $leave->created_at
                ];
            });

        // Recent Awards for Employee
        $recentAwards = Award::where('created_by', $creatorId)
            ->where('employee_id', $userId)
            ->with('awardType')
            ->latest()
            ->get()
            ->map(function ($award) {
                return [
                    'id' => $award->id,
                    'award_type' => $award->awardType->name ?? 'Award',
                    'award_date' => $award->award_date,
                    'created_at' => $award->created_at
                ];
            });

        // Recent Warnings for Employee
        $recentWarnings = Warning::where('created_by', $creatorId)
            ->where('employee_id', $userId)
            ->with('warningType')
            ->latest()
            ->get()
            ->map(function ($warning) {
                return [
                    'id' => $warning->id,
                    'warning_type' => $warning->warningType->name ?? 'Warning',
                    'warning_date' => $warning->warning_date,
                    'created_at' => $warning->created_at
                ];
            });

        // Get employee shift information
        $employee = Employee::where('user_id', $userId)->where('created_by', $creatorId)->first();
        $shift = $employee ? Shift::find($employee->shift) : null;
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
            ->where('date', $today)
            ->first();

        // Check if clock in/out should be allowed
        $workingDays = getCompanyAllSetting($creatorId)['working_days'] ?? '';
        $workingDaysArray = json_decode($workingDays, true) ?? [];
        $todayDayIndex = $today->dayOfWeek;
        $isWorkingDay = in_array($todayDayIndex, $workingDaysArray);
        
        $isOnLeave = LeaveApplication::where('created_by', $creatorId)
            ->where('employee_id', $userId)
            ->where('status', 'approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->exists();
            
        $isHoliday = Holiday::where('created_by', $creatorId)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->exists();

        // Determine if user is currently clocked in (including night shifts)
        $isCurrentlyClockedIn = false;

        $activeAttendance = $todayAttendance;

        if ($pendingAttendance && $shift) {
            // Get shift duration in hours
            $shiftStart = Carbon::parse($shift->start_time);
            $shiftEnd = Carbon::parse($shift->end_time);
            
            // Handle night shift duration
            if ($shiftEnd->lt($shiftStart)) {
                $shiftEnd->addDay();
            }
            $shiftDurationHours = $shiftStart->diffInHours($shiftEnd);
            
            // Calculate shift end datetime from clock in time
            $clockInDateTime = Carbon::parse($pendingAttendance->clock_in);
            $shiftEndDateTime = $clockInDateTime->copy()->addHours($shiftDurationHours);
            // Check if current date is within shift duration dates
            $now = Carbon::now();
            $clockInDate = $clockInDateTime->format('Y-m-d');
            $shiftEndDate = $shiftEndDateTime->format('Y-m-d');
            $nowDate = $now->format('Y-m-d');
            // Allow clock out on clock in date or shift end date
            if ($nowDate >= $clockInDate && $nowDate <= $shiftEndDate) {
                $isCurrentlyClockedIn = true;
                $activeAttendance = $pendingAttendance;
            }
        }
        $attendanceData = [
            'is_clocked_in' => $isCurrentlyClockedIn,
            'clock_in_time' => $activeAttendance ? $activeAttendance->clock_in : null,
            'clock_out_time' => $activeAttendance ? $activeAttendance->clock_out : null,
            'total_working_hours' => $activeAttendance && $activeAttendance->total_hour ? $activeAttendance->total_hour . ' hours' : null,
            'can_clock' => $isWorkingDay && !$isOnLeave && !$isHoliday,
            'shift_start_time' => $shift ? $shift->start_time : null,
            'shift_end_time' => $shift ? $shift->end_time : null,
            'is_on_leave' => $isOnLeave,
            'is_holiday' => $isHoliday,
            'is_non_working_day' => !$isWorkingDay,
        ];

        // Recent Attendance Records
        $recentAttendance = Attendance::where('created_by', $creatorId)
            ->where('employee_id', $userId)
            ->orderBy('date', 'desc')
            ->take(5)
            ->get()
            ->map(function ($attendance) {
                return [
                    'date' => $attendance->date,
                    'clock_in' => $attendance->clock_in,
                    'clock_out' => $attendance->clock_out,
                    'status' => $attendance->status,
                    'total_hour' => $attendance->total_hour,
                ];
            });

        return Inertia::render('Hrm/Dashboard/employee-dashboard', [
            'stats' => [
                'my_attendance' => $isDemo ? rand(15, 22) : $myAttendance,
                'total_approved_leave_year' => $isDemo ? rand(5, 12) : $totalApprovedLeaveYear,
                'total_approved_leave_month' => $isDemo ? rand(0, 3) : $totalApprovedLeaveMonth,
                'pending_requests' => $isDemo ? rand(0, 2) : $pendingRequests,
                'total_absent_days' => $isDemo ? rand(0, 2) : $totalAbsentDays,
                'total_awards' => $isDemo ? rand(0, 2) : $totalAwards,
                'total_warnings' => $isDemo ? rand(0, 1) : $totalWarnings,
                'total_complaints' => $isDemo ? rand(0, 1) : $totalComplaints,
                'calendar_events' => $calendarEvents,
                'recent_announcements' => $recentAnnouncements,
                'recent_leave_applications' => $recentLeaveApplications,
                'recent_awards' => $recentAwards,
                'recent_warnings' => $recentWarnings,
                'attendance_data' => $attendanceData,
                'recent_attendance' => $recentAttendance,
            ],
            'message' => __('Employee Dashboard - Your personal workspace.')
        ]);
    }
}
