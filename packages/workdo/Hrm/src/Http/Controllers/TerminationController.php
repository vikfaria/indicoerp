<?php

namespace Workdo\Hrm\Http\Controllers;

use Workdo\Hrm\Models\Termination;
use Workdo\Hrm\Http\Requests\StoreTerminationRequest;
use Workdo\Hrm\Http\Requests\UpdateTerminationRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\TerminationType;
use Workdo\Hrm\Events\CreateTermination;
use Workdo\Hrm\Events\DestroyTermination;
use Workdo\Hrm\Events\UpdateTermination;
use Workdo\Hrm\Events\UpdateTerminationStatus;

class TerminationController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-terminations')) {
            $terminations = Termination::query()
                ->with(['employee', 'terminationType', 'approvedBy:id,name'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-terminations')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-terminations')) {
                        $q->where('creator_id', Auth::id())->orWhere('employee_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('name'), function ($q) {
                    $q->whereHas('employee', function ($query) {
                        $query->where('name', 'like', '%' . request('name') . '%');
                    });
                })
                ->when(request('employee_id') && request('employee_id') !== 'all', fn($q) => $q->where('employee_id', request('employee_id')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('Hrm/Terminations/Index', [
                'terminations' => $terminations,
                'users' => $this->getFilteredEmployees(),
                'terminationtypes' => TerminationType::where('created_by', creatorId())->select('id', 'termination_type')->get(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreTerminationRequest $request)
    {
        if (Auth::user()->can('create-terminations')) {
            $validated = $request->validated();



            $termination = new Termination();
            $termination->notice_date = $validated['notice_date'];
            $termination->termination_date = $validated['termination_date'];
            $termination->reason = $validated['reason'];
            $termination->description = $validated['description'];
            $termination->document = $validated['document'];
            $termination->employee_id = $validated['employee_id'];
            $termination->termination_type_id = $validated['termination_type_id'];
            $termination->status = 'pending';

            $termination->creator_id = Auth::id();
            $termination->created_by = creatorId();
            $termination->save();

            CreateTermination::dispatch($request, $termination);

            return redirect()->route('hrm.terminations.index')->with('success', __('The termination has been created successfully.'));
        } else {
            return redirect()->route('hrm.terminations.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateTerminationRequest $request, Termination $termination)
    {
        if (Auth::user()->can('edit-terminations')) {
            $validated = $request->validated();



            $termination->notice_date = $validated['notice_date'];
            $termination->termination_date = $validated['termination_date'];
            $termination->reason = $validated['reason'];
            $termination->description = $validated['description'];
            $termination->document = $validated['document'];
            $termination->employee_id = $validated['employee_id'];
            $termination->termination_type_id = $validated['termination_type_id'];

            $termination->save();

            UpdateTermination::dispatch($request, $termination);

            return redirect()->back()->with('success', __('The termination details are updated successfully.'));
        } else {
            return redirect()->route('hrm.terminations.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(Termination $termination)
    {
        if (Auth::user()->can('delete-terminations')) {
            DestroyTermination::dispatch($termination);
            $termination->delete();

            return redirect()->back()->with('success', __('The termination has been deleted.'));
        } else {
            return redirect()->route('hrm.terminations.index')->with('error', __('Permission denied'));
        }
    }

    public function updateStatus(\Illuminate\Http\Request $request, Termination $termination)
    {
        if (Auth::user()->can('manage-termination-status')) {
            $validated = $request->validate([
                'status' => 'required|in:pending,approved,rejected'
            ]);

            $termination->status = $validated['status'];

            if ($validated['status'] === 'approved') {
                $termination->approved_by = Auth::id();
            }

            $termination->save();
            UpdateTerminationStatus::dispatch($request, $termination);

            return redirect()->back()->with('success', __('Termination status updated successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }    
    private function getFilteredEmployees()
    {
        $employeeQuery = Employee::where('created_by', creatorId());

        if (Auth::user()->can('manage-own-terminations') && !Auth::user()->can('manage-any-terminations')) {
            $employeeQuery->where(function ($q) {
                $q->where('creator_id', Auth::id())->orWhere('user_id', Auth::id());
            });
        }

        return User::emp()->where('created_by', creatorId())
            ->whereIn('id', $employeeQuery->pluck('user_id'))
            ->select('id', 'name')->get();
    }
}
