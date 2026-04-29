<?php

namespace Workdo\Hrm\Http\Controllers;

use App\Models\User;
use Workdo\Hrm\Models\Promotion;
use Workdo\Hrm\Http\Requests\StorePromotionRequest;
use Workdo\Hrm\Http\Requests\UpdatePromotionRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;
use Workdo\Hrm\Models\Designation;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Events\CreatePromotion;
use Workdo\Hrm\Events\DestroyPromotion;
use Workdo\Hrm\Events\UpdatePromotion;

class PromotionController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-promotions')) {
            $promotions = Promotion::with([
                'employee:id,name',
                'previousBranch:id,branch_name',
                'previousDepartment:id,department_name',
                'previousDesignation:id,designation_name',
                'currentBranch:id,branch_name',
                'currentDepartment:id,department_name',
                'currentDesignation:id,designation_name',
                'approvedBy:id,name'
            ])->where(function ($q) {
                if (Auth::user()->can('manage-any-promotions')) {
                    $q->where('created_by', creatorId());
                } elseif (Auth::user()->can('manage-own-promotions')) {
                    $q->where('creator_id', Auth::id())->orWhere('employee_id', Auth::id())->where('status', 'approved');
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
                ->when(request('name'), function ($q) {
                    $q->whereHas('employee', function ($query) {
                        $query->where('name', 'like', '%' . request('name') . '%');
                    });
                })
                ->when(request('employee_id'), fn($q) => $q->where('employee_id', request('employee_id')))

                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('Hrm/Promotions/Index', [
                'promotions' => $promotions,
                'employees' => $this->getFilteredEmployees(),
                'branches' => Branch::where('created_by', creatorId())->select('id', 'branch_name')->get(),
                'departments' => Department::where('created_by', creatorId())->select('id', 'department_name', 'branch_id')->get(),
                'designations' => Designation::where('created_by', creatorId())->select('id', 'designation_name', 'department_id')->get(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StorePromotionRequest $request)
    {
        if (Auth::user()->can('create-promotions')) {
            $validated = $request->validated();

            // Get employee's current position from Employee table
            $employee = Employee::where('user_id', $validated['employee_id'])->first();

            $promotion = new Promotion();
            $promotion->employee_id = $validated['employee_id'];
            $promotion->previous_branch_id = $employee ? $employee->branch_id : null;
            $promotion->previous_department_id = $employee ? $employee->department_id : null;
            $promotion->previous_designation_id = $employee ? $employee->designation_id : null;
            $promotion->current_branch_id = $validated['current_branch_id'];
            $promotion->current_department_id = $validated['current_department_id'];
            $promotion->current_designation_id = $validated['current_designation_id'];
            $promotion->effective_date = $validated['effective_date'];
            $promotion->reason = $validated['reason'];
            $promotion->document = $validated['document'];
            $promotion->status = 'pending';

            $promotion->creator_id = Auth::id();
            $promotion->created_by = creatorId();
            $promotion->save();

            CreatePromotion::dispatch($request, $promotion);

            // Update employee table with new position
            if ($employee) {
                $employee->branch_id = $validated['current_branch_id'];
                $employee->department_id = $validated['current_department_id'];
                $employee->designation_id = $validated['current_designation_id'];
                $employee->save();
            }

            return redirect()->route('hrm.promotions.index')->with('success', __('The promotion has been created successfully.'));
        } else {
            return redirect()->route('hrm.promotions.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdatePromotionRequest $request, Promotion $promotion)
    {
        if (Auth::user()->can('edit-promotions')) {
            $validated = $request->validated();

            // Get employee's current position from Employee table for previous data
            $employee = Employee::where('user_id', $validated['employee_id'])->first();

            $promotion->employee_id = $validated['employee_id'];
            $promotion->previous_branch_id = $employee ? $employee->branch_id : null;
            $promotion->previous_department_id = $employee ? $employee->department_id : null;
            $promotion->previous_designation_id = $employee ? $employee->designation_id : null;
            $promotion->current_branch_id = $validated['current_branch_id'];
            $promotion->current_department_id = $validated['current_department_id'];
            $promotion->current_designation_id = $validated['current_designation_id'];
            $promotion->effective_date = $validated['effective_date'];
            $promotion->reason = $validated['reason'];
            $promotion->document = $validated['document'];
            $promotion->status = $validated['status'] ?? 'pending';

            $promotion->save();

            UpdatePromotion::dispatch($request, $promotion);

            // Update employee table with new position
            if ($employee) {
                $employee->branch_id = $validated['current_branch_id'];
                $employee->department_id = $validated['current_department_id'];
                $employee->designation_id = $validated['current_designation_id'];
                $employee->save();
            }

            return redirect()->back()->with('success', __('The promotion details are updated successfully.'));
        } else {
            return redirect()->route('hrm.promotions.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(Promotion $promotion)
    {
        if (Auth::user()->can('delete-promotions')) {
            DestroyPromotion::dispatch($promotion);
            $promotion->delete();

            return redirect()->back()->with('success', __('The promotion has been deleted.'));
        } else {
            return redirect()->route('hrm.promotions.index')->with('error', __('Permission denied'));
        }
    }

    public function updateStatus(Promotion $promotion)
    {
        if (Auth::user()->can('manage-promotions-status')) {
            $validated = request()->validate([
                'status' => 'required|in:pending,approved,rejected'
            ]);

            $promotion->status = $validated['status'];

            if ($validated['status'] === 'approved') {
                $promotion->approved_by = Auth::id();
            }

            $promotion->save();

            return redirect()->back()->with('success', __('Promotion status updated successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    private function getFilteredEmployees()
    {
        $employeeQuery = Employee::where('created_by', creatorId());

        if (Auth::user()->can('manage-own-promotions') && !Auth::user()->can('manage-any-promotions')) {
            $employeeQuery->where(function ($q) {
                $q->where('creator_id', Auth::id())->orWhere('user_id', Auth::id());
            });
        }

        return User::emp()->where('created_by', creatorId())
            ->whereIn('id', $employeeQuery->pluck('user_id'))
            ->select('id', 'name')->get();
    }
}
