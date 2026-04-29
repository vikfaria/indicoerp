<?php

namespace Workdo\Hrm\Http\Controllers;

use Workdo\Hrm\Models\EmployeeTransfer;
use Workdo\Hrm\Http\Requests\StoreEmployeeTransferRequest;
use Workdo\Hrm\Http\Requests\UpdateEmployeeTransferRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;
use Workdo\Hrm\Models\Designation;
use Workdo\Hrm\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Workdo\Hrm\Events\CreateEmployeeTransfer;
use Workdo\Hrm\Events\DestroyEmployeeTransfer;
use Workdo\Hrm\Events\UpdateEmployeeTransfer;

class EmployeeTransferController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-employee-transfers')) {
            $employeetransfers = EmployeeTransfer::query()
                ->with(['employee:id,name', 'from_branch:id,branch_name', 'from_department:id,department_name', 'from_designation:id,designation_name', 'to_branch:id,branch_name', 'to_department:id,department_name', 'to_designation:id,designation_name', 'approved_by:id,name'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-employee-transfers')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-employee-transfers')) {
                        $q->where('creator_id', Auth::id())->orWhere('employee_id', Auth::id())->where('status', 'approved');
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('search'), function ($q) {
                    $q->where(function ($query) {
                        $query->whereHas('employee', function ($empQuery) {
                            $empQuery->where('name', 'like', '%' . request('search') . '%');
                        })
                            ->orWhere('reason', 'like', '%' . request('search') . '%');
                    });
                })
                ->when(request('employee_id') && request('employee_id') !== 'all', fn($q) => $q->where('employee_id', request('employee_id')))
                ->when(request('status') !== null && request('status') !== '', fn($q) => $q->where('status', request('status')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            // Get employees who exist in employees table
            $employees = User::whereIn('id', Employee::where('created_by', creatorId())->pluck('user_id'))
                ->select('id', 'name')
                ->get();

            return Inertia::render('Hrm/EmployeeTransfers/Index', [
                'employeetransfers' => $employeetransfers,
                'employees' => $this->getFilteredEmployees(),
                'branches' => Branch::where('created_by', creatorId())->select('id', 'branch_name')->get(),
                'departments' => Department::where('created_by', creatorId())->select('id', 'department_name', 'branch_id')->get(),
                'designations' => Designation::where('created_by', creatorId())->select('id', 'designation_name', 'department_id')->get(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreEmployeeTransferRequest $request)
    {
        if (Auth::user()->can('create-employee-transfers')) {
            $validated = $request->validated();

            // Get current employee details for from fields
            $employee = Employee::where('user_id', $validated['employee_id'])
                ->where('created_by', creatorId())
                ->first();

            $employeetransfer = new EmployeeTransfer();
            $employeetransfer->transfer_date = $validated['transfer_date'] ?? null;
            $employeetransfer->effective_date = $validated['effective_date'];
            $employeetransfer->reason = $validated['reason'] ?? null;
            $employeetransfer->document = $validated['document'] ?? null;
            $employeetransfer->employee_id = $validated['employee_id'];

            // Set from fields from current employee data
            if ($employee) {
                $employeetransfer->from_branch_id = $employee->branch_id;
                $employeetransfer->from_department_id = $employee->department_id;
                $employeetransfer->from_designation_id = $employee->designation_id;
            }

            // Set to fields from form
            $employeetransfer->to_branch_id = $validated['to_branch_id'];
            $employeetransfer->to_department_id = $validated['to_department_id'];
            $employeetransfer->to_designation_id = $validated['to_designation_id'];
            $employeetransfer->approved_by = null;

            $employeetransfer->creator_id = Auth::id();
            $employeetransfer->created_by = creatorId();
            $employeetransfer->save();

            CreateEmployeeTransfer::dispatch($request, $employeetransfer);

            return redirect()->route('hrm.employee-transfers.index')->with('success', __('The employee transfer has been created successfully.'));
        } else {
            return redirect()->route('hrm.employee-transfers.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateEmployeeTransferRequest $request, EmployeeTransfer $employeetransfer)
    {
        if (Auth::user()->can('edit-employee-transfers')) {
            $validated = $request->validated();

            $employeetransfer->transfer_date = $validated['transfer_date'] ?? null;
            $employeetransfer->effective_date = $validated['effective_date'];
            $employeetransfer->reason = $validated['reason'] ?? null;
            $employeetransfer->document = $validated['document'] ?? null;
            $employeetransfer->to_branch_id = $validated['to_branch_id'];
            $employeetransfer->to_department_id = $validated['to_department_id'];
            $employeetransfer->to_designation_id = $validated['to_designation_id'];

            $employeetransfer->save();

            UpdateEmployeeTransfer::dispatch($request, $employeetransfer);

            return redirect()->back()->with('success', __('The employee transfer details are updated successfully.'));
        } else {
            return redirect()->route('hrm.employee-transfers.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(EmployeeTransfer $employeetransfer)
    {
        if (Auth::user()->can('delete-employee-transfers')) {
            DestroyEmployeeTransfer::dispatch($employeetransfer);
            $employeetransfer->delete();

            return redirect()->back()->with('success', __('The employee transfer has been deleted.'));
        } else {
            return redirect()->route('hrm.employee-transfers.index')->with('error', __('Permission denied'));
        }
    }

    public function updateStatus(Request $request, EmployeeTransfer $employeetransfer)
    {
        if (Auth::user()->can('manage-employee-transfers-status')) {
            $validated = $request->validate([
                'status' => ['required', Rule::in(['pending', 'approved', 'in progress', 'rejected', 'cancelled'])]
            ]);

            $employeetransfer->status = $validated['status'];

            // If status is approved, update approved_by and transfer_date
            if ($validated['status'] === 'approved') {
                $employeetransfer->approved_by = Auth::id();
                $employeetransfer->transfer_date = now()->toDateString();

                // Update employee table with new branch, department, and designation
                $employee = Employee::where('user_id', $employeetransfer->employee_id)
                    ->where('created_by', creatorId())
                    ->first();

                if ($employee) {
                    $employee->branch_id = $employeetransfer->to_branch_id;
                    $employee->department_id = $employeetransfer->to_department_id;
                    $employee->designation_id = $employeetransfer->to_designation_id;
                    $employee->save();
                }
            }

            $employeetransfer->save();

            return redirect()->back()->with('success', __('Employee transfer status updated successfully.'));
        } else {
            return redirect()->route('hrm.employee-transfers.index')->with('error', __('Permission denied'));
        }
    }

    private function getFilteredEmployees()
    {
        $employeeQuery = Employee::where('created_by', creatorId());

        if (Auth::user()->can('manage-own-employee-transfers') && !Auth::user()->can('manage-any-employee-transfers')) {
            $employeeQuery->where(function ($q) {
                $q->where('creator_id', Auth::id())->orWhere('user_id', Auth::id());
            });
        }

        return User::emp()->where('created_by', creatorId())
            ->whereIn('id', $employeeQuery->pluck('user_id'))
            ->select('id', 'name')->get();
    }
}
