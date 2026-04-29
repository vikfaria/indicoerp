<?php

namespace Workdo\Performance\Http\Controllers;

use Illuminate\Routing\Controller;
use Workdo\Performance\Models\PerformanceEmployeeGoal;
use Workdo\Performance\Models\PerformanceGoalType;
use Workdo\Hrm\Models\Employee;
use Workdo\Performance\Http\Requests\StoreEmployeeGoalRequest;
use Workdo\Performance\Http\Requests\UpdateEmployeeGoalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;
use Workdo\Performance\Events\CreateEmployeeGoal;
use Workdo\Performance\Events\UpdateEmployeeGoal;
use Workdo\Performance\Events\DestroyEmployeeGoal;

class EmployeeGoalController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-employee-goals')) {
            $goals = PerformanceEmployeeGoal::with(['employee', 'goalType'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-employee-goals')) {
                        $q->where('performance_employee_goals.created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-employee-goals')) {
                        $q->where(function ($query) {
                            $query->where('performance_employee_goals.creator_id', Auth::id())
                                ->orWhere('performance_employee_goals.employee_id', Auth::id());
                        });
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('title'), fn($q) => $q->where('performance_employee_goals.title', 'like', '%' . request('title') . '%'))
                ->when(request('employee_id'), fn($q) => $q->where('performance_employee_goals.employee_id', request('employee_id')))
                ->when(request('goal_type_id'), fn($q) => $q->where('performance_employee_goals.goal_type_id', request('goal_type_id')))
                ->when(request('status'), fn($q) => $q->where('performance_employee_goals.status', request('status')))
                ->when(request('sort'), function ($q) {
                    $sort = request('sort');
                    $direction = request('direction', 'asc');

                    if ($sort === 'goal_type') {
                        return $q->join('performance_goal_types', 'performance_employee_goals.goal_type_id', '=', 'performance_goal_types.id')
                            ->orderBy('performance_goal_types.name', $direction)
                            ->select('performance_employee_goals.*');
                    }

                    return $q->orderBy($sort, $direction);
                }, fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            $employees = $this->getFilteredEmployees();
            $goalTypes = $this->getFilteredGoalTypes();

            return Inertia::render('Performance/EmployeeGoals/Index', [
                'goals' => $goals,
                'employees' => $employees,
                'goalTypes' => $goalTypes,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreEmployeeGoalRequest $request)
    {
        if (Auth::user()->can('create-employee-goals')) {
            $validated = $request->validated();
            $employeeGoal = new PerformanceEmployeeGoal();
            $employeeGoal->employee_id = $validated['employee_id'];
            $employeeGoal->goal_type_id = $validated['goal_type_id'];
            $employeeGoal->title = $validated['title'];
            $employeeGoal->description = $validated['description'];
            $employeeGoal->start_date = $validated['start_date'];
            $employeeGoal->end_date = $validated['end_date'];
            $employeeGoal->target = $validated['target'];
            $employeeGoal->progress = $validated['progress'] ?? 0;
            $employeeGoal->status = $validated['status'] ?? 'not_started';
            $employeeGoal->creator_id = Auth::id();
            $employeeGoal->created_by = creatorId();
            $employeeGoal->save();

            CreateEmployeeGoal::dispatch($request, $employeeGoal);

            return back()->with('success', __('The employee goal has been created successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateEmployeeGoalRequest $request, PerformanceEmployeeGoal $employeeGoal)
    {
        if (Auth::user()->can('edit-employee-goals')) {
            if (!$this->canAccessGoal($employeeGoal)) {
                return back()->with('error', __('Permission denied'));
            }

            $validated = $request->validated();

            $employeeGoal->employee_id = $validated['employee_id'];
            $employeeGoal->goal_type_id = $validated['goal_type_id'];
            $employeeGoal->title = $validated['title'];
            $employeeGoal->description = $validated['description'];
            $employeeGoal->start_date = $validated['start_date'];
            $employeeGoal->end_date = $validated['end_date'];
            $employeeGoal->target = $validated['target'];
            $employeeGoal->progress = $validated['progress'];
            $employeeGoal->status = $validated['status'];
            $employeeGoal->save();

            UpdateEmployeeGoal::dispatch($request, $employeeGoal);

            return back()->with('success', __('The employee goal details are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(PerformanceEmployeeGoal $employeeGoal)
    {
        if (Auth::user()->can('delete-employee-goals')) {
            if (!$this->canAccessGoal($employeeGoal)) {
                return back()->with('error', __('Permission denied'));
            }

            DestroyEmployeeGoal::dispatch($employeeGoal);

            $employeeGoal->delete();

            return back()->with('success', __('The employee goal has been deleted.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    private function canAccessGoal(PerformanceEmployeeGoal $goal)
    {
        if (Auth::user()->can('manage-any-employee-goals')) {
            return $goal->created_by == creatorId();
        } elseif (Auth::user()->can('manage-own-employee-goals')) {
            return $goal->creator_id == Auth::id();
        } else {
            return false;
        }
    }

    private function getFilteredEmployees()
    {
        return User::emp()->where('created_by', creatorId())
            ->when(!Auth::user()->can('manage-any-users'), function ($q) {
                if (Auth::user()->can('manage-own-users')) {
                    $q->where('creator_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->select('id', 'name')->get();
    }

    private function getFilteredGoalTypes()
    {
        return PerformanceGoalType::where('created_by', creatorId())
            ->where('status', 'active')
            ->when(!Auth::user()->can('manage-any-goal-types'), function ($q) {
                if (Auth::user()->can('manage-own-goal-types')) {
                    $q->where('creator_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->select('id', 'name')->get();
    }
}
