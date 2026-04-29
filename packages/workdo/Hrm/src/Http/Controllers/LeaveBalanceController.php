<?php

namespace Workdo\Hrm\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;
use Workdo\Hrm\Models\LeaveType;
use Workdo\Hrm\Models\LeaveApplication;
use Workdo\Hrm\Models\Employee;

class LeaveBalanceController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-leave-balance')) {
            $currentYear = date('Y');

            // Get employees with their leave balances
            $employees = User::whereIn('id', Employee::where('created_by', creatorId())->pluck('user_id'))
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-leave-balance')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-leave-balance')) {
                        $q->where('id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->get();

            $leaveTypes = LeaveType::where('created_by', creatorId())->get();

            $leaveBalances = [];

            foreach ($employees as $employee) {
                $employeeBalance = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'leave_types' => [],
                ];

                foreach ($leaveTypes as $leaveType) {
                    $usedLeaves = LeaveApplication::where('employee_id', $employee->id)->where('leave_type_id', $leaveType->id)->where('status', 'approved')->whereYear('start_date', $currentYear)->sum('total_days');

                    $employeeBalance['leave_types'][] = [
                        'leave_type_name' => $leaveType->name,
                        'leave_type_color' => $leaveType->color,
                        'total_days' => $leaveType->max_days_per_year,
                        'used_days' => $usedLeaves,
                        'available_days' => $leaveType->max_days_per_year - $usedLeaves,
                    ];
                }

                $leaveBalances[] = $employeeBalance;
            }

            return Inertia::render('Hrm/LeaveBalance/Index', [
                'leaveBalances' => $leaveBalances,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }
}
