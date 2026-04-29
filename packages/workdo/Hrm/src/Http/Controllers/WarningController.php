<?php

namespace Workdo\Hrm\Http\Controllers;

use Workdo\Hrm\Models\Warning;
use Workdo\Hrm\Http\Requests\StoreWarningRequest;
use Workdo\Hrm\Http\Requests\UpdateWarningRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\WarningType;
use Workdo\Hrm\Events\CreateWarning;
use Workdo\Hrm\Events\DestroyWarning;
use Workdo\Hrm\Events\UpdateWarning;

class WarningController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-warnings')) {
            $warnings = Warning::query()
                ->with(['employee', 'warningBy', 'warningType'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-warnings')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-warnings')) {
                        $q->where('creator_id', Auth::id())->orWhere('employee_id', Auth::id())->where('status', '=', 'approved');
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('subject'), function ($q) {
                    $q->where(function ($query) {
                        $query->where('subject', 'like', '%' . request('subject') . '%')
                            ->orWhereHas('employee', function ($employeeQuery) {
                                $employeeQuery->where('name', 'like', '%' . request('subject') . '%');
                            });
                    });
                })
                ->when(request('employee_id') && request('employee_id') !== 'all', fn($q) => $q->where('employee_id', request('employee_id')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('Hrm/Warnings/Index', [
                'warnings' => $warnings,
                'users' => $this->getFilteredEmployees(),
                'allUsers' => User::where('created_by', creatorId())->select('id', 'name')->get(),
                'warningtypes' => WarningType::where('created_by', creatorId())->select('id', 'warning_type_name')->get(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreWarningRequest $request)
    {
        if (Auth::user()->can('create-warnings')) {
            $validated = $request->validated();



            $warning = new Warning();
            $warning->subject = $validated['subject'];
            $warning->severity = $validated['severity'];
            $warning->warning_date = $validated['warning_date'];
            $warning->description = $validated['description'];
            $warning->document = $validated['document'];
            $warning->employee_id = $validated['employee_id'];
            $warning->warning_by = $validated['warning_by'];
            $warning->warning_type_id = $validated['warning_type_id'];

            $warning->creator_id = Auth::id();
            $warning->created_by = creatorId();
            $warning->save();

            CreateWarning::dispatch($request, $warning);

            return redirect()->route('hrm.warnings.index')->with('success', __('The warning has been created successfully.'));
        } else {
            return redirect()->route('hrm.warnings.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateWarningRequest $request, Warning $warning)
    {
        if (Auth::user()->can('edit-warnings')) {
            $validated = $request->validated();



            $warning->subject = $validated['subject'];
            $warning->severity = $validated['severity'];
            $warning->warning_date = $validated['warning_date'];
            $warning->description = $validated['description'];
            $warning->document = $validated['document'];
            $warning->employee_id = $validated['employee_id'];
            $warning->warning_by = $validated['warning_by'];
            $warning->warning_type_id = $validated['warning_type_id'];

            $warning->save();

            UpdateWarning::dispatch($request, $warning);

            return redirect()->back()->with('success', __('The warning details are updated successfully.'));
        } else {
            return redirect()->route('hrm.warnings.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(Warning $warning)
    {
        if (Auth::user()->can('delete-warnings')) {
            DestroyWarning::dispatch($warning);
            $warning->delete();

            return redirect()->back()->with('success', __('The warning has been deleted.'));
        } else {
            return redirect()->route('hrm.warnings.index')->with('error', __('Permission denied'));
        }
    }

    public function response(Warning $warning)
    {
        if (Auth::user()->can('manage-warning-response')) {
            $validated = request()->validate([
                'warning_status' => 'required|string',
                'employee_response' => 'nullable|string',
            ]);

            $warning->status = $validated['warning_status'];
            $warning->employee_response = $validated['employee_response'];
            $warning->save();

            return redirect()->back()->with('success', __('Warning response updated successfully.'));
        } else {
            return redirect()->route('hrm.warnings.index')->with('error', __('Permission denied'));
        }
    }

    private function getFilteredEmployees()
    {
        $employeeQuery = Employee::where('created_by', creatorId());

        if (Auth::user()->can('manage-own-warnings') && !Auth::user()->can('manage-any-warnings')) {
            $employeeQuery->where(function ($q) {
                $q->where('creator_id', Auth::id())->orWhere('user_id', Auth::id());
            });
        }

        return User::emp()->where('created_by', creatorId())
            ->whereIn('id', $employeeQuery->pluck('user_id'))
            ->select('id', 'name')->get();
    }
}
