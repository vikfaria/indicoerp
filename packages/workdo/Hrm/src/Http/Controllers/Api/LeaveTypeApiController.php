<?php

namespace Workdo\Hrm\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Workdo\Hrm\Models\LeaveType;
use Workdo\Hrm\Models\LeaveApplication;

class LeaveTypeApiController extends Controller
{
    use ApiResponseTrait;

    public function index()
    {
        try {

            $leavetypes = LeaveType::where('created_by', creatorId())
                ->latest()
                ->get()
                ->transform(function ($leavetype) {
                    $currentYear = date('Y');
                    $employeeId  = Auth::id();
                    $baseQuery   = LeaveApplication::where('employee_id', $employeeId)->where('leave_type_id', $leavetype->id)->whereYear('start_date', $currentYear);

                    if (request('exclude_id')) {
                        $baseQuery->where('id', '!=', request('exclude_id'));
                    }

                    $approvedLeaves  = (int) (clone $baseQuery)->where('status', 'approved')->sum('total_days');
                    $pendingLeaves   = (int) (clone $baseQuery)->where('status', 'pending')->sum('total_days');
                    $usedLeaves      = $approvedLeaves + $pendingLeaves;
                    $availableLeaves = $leavetype->max_days_per_year - $usedLeaves;
                    $leave_balance   = [
                        'total_leaves'     => $leavetype->max_days_per_year,
                        'approved_leaves'  => $approvedLeaves,
                        'pending_leaves'   => $pendingLeaves,
                        'used_leaves'      => $usedLeaves,
                        'available_leaves' => $availableLeaves,
                    ];

                    return [
                        'id'            => $leavetype->id,
                        'name'          => $leavetype->name,
                        'description'   => $leavetype->description,
                        'leave_balance' => $leave_balance,
                    ];
                });
            return $this->successResponse($leavetypes, 'Leave types retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }
}
