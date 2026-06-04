<?php

namespace Workdo\Hrm\Http\Controllers;

use App\Services\MozambiqueLabourComplianceService;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;
use Workdo\Hrm\Models\LeaveType;
use Workdo\Hrm\Models\LeaveApplication;
use Workdo\Hrm\Models\Employee;

class LeaveBalanceController extends Controller
{
    public function __construct(private readonly MozambiqueLabourComplianceService $labourComplianceService)
    {
    }

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
                    $approvedLeaves = LeaveApplication::where('employee_id', $employee->id)
                        ->active()
                        ->where('leave_type_id', $leaveType->id)
                        ->where('status', 'approved')
                        ->whereYear('start_date', $currentYear)
                        ->sum('total_days');
                    $pendingLeaves = LeaveApplication::where('employee_id', $employee->id)
                        ->active()
                        ->where('leave_type_id', $leaveType->id)
                        ->where('status', 'pending')
                        ->whereYear('start_date', $currentYear)
                        ->sum('total_days');
                    $entitlement = $this->labourComplianceService->calculateLeaveEntitlementLimit(
                        creatorId(),
                        (int) $employee->id,
                        $leaveType,
                        (int) $currentYear
                    );
                    $totalDays = (int) ($entitlement['final_entitlement_days'] ?? 0);
                    $usedLeaves = (float) $approvedLeaves + (float) $pendingLeaves;
                    $availableLeaves = $totalDays - $usedLeaves;

                    $employeeBalance['leave_types'][] = [
                        'leave_type_name' => $leaveType->name,
                        'leave_type_color' => $leaveType->color,
                        'total_days' => $totalDays,
                        'base_entitlement_days' => (int) ($entitlement['base_entitlement_days'] ?? $totalDays),
                        'absence_penalty_days' => (int) ($entitlement['absence_penalty_days'] ?? 0),
                        'unjustified_absence_days' => (int) ($entitlement['unjustified_absence_days'] ?? 0),
                        'approved_days' => (float) $approvedLeaves,
                        'pending_days' => (float) $pendingLeaves,
                        'used_days' => $usedLeaves,
                        'available_days' => $availableLeaves,
                        'service_year_index' => $entitlement['service_year_index'] ?? null,
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
