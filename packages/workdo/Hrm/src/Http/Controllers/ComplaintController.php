<?php

namespace Workdo\Hrm\Http\Controllers;

use Workdo\Hrm\Models\Complaint;
use Workdo\Hrm\Models\ComplaintType;
use Workdo\Hrm\Http\Requests\StoreComplaintRequest;
use Workdo\Hrm\Http\Requests\UpdateComplaintRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Events\CreateComplaint;
use Workdo\Hrm\Events\DestroyComplaint;
use Workdo\Hrm\Events\UpdateComplaint;

class ComplaintController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-complaints')) {
            $complaints = Complaint::query()
                ->with(['employee', 'againstEmployee', 'complaintType', 'resolvedBy'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-complaints')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-complaints')) {
                        $q->where('creator_id', Auth::id())->orWhere('employee_id', Auth::id())->orWhere('against_employee_id', Auth::id());
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
                ->when(request('complaint_type_id') && request('complaint_type_id') !== 'all', fn($q) => $q->where('complaint_type_id', request('complaint_type_id')))
                ->when(request('status') && request('status') !== 'all', fn($q) => $q->where('status', request('status')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('Hrm/Complaints/Index', [
                'complaints' => $complaints,
                'employees' => $this->getFilteredEmployees(),
                'allEmployees' =>  User::emp()
                    ->where('created_by', creatorId())
                    ->whereIn('id', Employee::where('created_by', creatorId())->pluck('user_id'))
                    ->select('id', 'name')
                    ->get(),
                'complaintTypes' => ComplaintType::where('created_by', creatorId())->select('id', 'complaint_type')->get(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreComplaintRequest $request)
    {
        if (Auth::user()->can('create-complaints')) {
            $validated = $request->validated();

            $complaint = new Complaint();
            $complaint->employee_id = $validated['employee_id'] === 'other' ? null : $validated['employee_id'];
            $complaint->against_employee_id = $validated['against_employee_id'] === 'other' ? null : $validated['against_employee_id'];
            $complaint->complaint_type_id = $validated['complaint_type_id'];
            $complaint->subject = $validated['subject'];
            $complaint->description = $validated['description'];
            $complaint->complaint_date = $validated['complaint_date'];
            $complaint->document = $validated['document'];
            $complaint->status = 'Pending';
            $complaint->creator_id = Auth::id();
            $complaint->created_by = creatorId();
            $complaint->save();

            CreateComplaint::dispatch($request, $complaint);

            return redirect()->route('hrm.complaints.index')->with('success', __('The complaint has been created successfully.'));
        } else {
            return redirect()->route('hrm.complaints.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateComplaintRequest $request, Complaint $complaint)
    {
        if (Auth::user()->can('edit-complaints')) {
            $validated = $request->validated();

            $complaint->employee_id = $validated['employee_id'] === 'other' ? null : $validated['employee_id'];
            $complaint->against_employee_id = $validated['against_employee_id'] === 'other' ? null : $validated['against_employee_id'];
            $complaint->complaint_type_id = $validated['complaint_type_id'];
            $complaint->subject = $validated['subject'];
            $complaint->description = $validated['description'];
            $complaint->complaint_date = $validated['complaint_date'];
            $complaint->document = $validated['document'];
            $complaint->save();

            UpdateComplaint::dispatch($request, $complaint);

            return redirect()->back()->with('success', __('The complaint details are updated successfully.'));
        } else {
            return redirect()->route('hrm.complaints.index')->with('error', __('Permission denied'));
        }
    }

    public function updateStatus(Complaint $complaint)
    {
        if (Auth::user()->can('manage-complaint-status')) {
            $validated = request()->validate([
                'status' => 'required|in:pending,in review,assigned,in progress,resolved'
            ]);

            $complaint->status = $validated['status'];
            $complaint->resolved_by = Auth::id();
            if ($validated['status'] === 'resolved') {
                $complaint->resolution_date = now()->toDateString();
            }

            $complaint->save();

            return redirect()->back()->with('success', __('The complaint status has been updated successfully.'));
        } else {
            return redirect()->route('hrm.complaints.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(Complaint $complaint)
    {
        if (Auth::user()->can('delete-complaints')) {
            DestroyComplaint::dispatch($complaint);
            $complaint->delete();

            return redirect()->back()->with('success', __('The complaint has been deleted.'));
        } else {
            return redirect()->route('hrm.complaints.index')->with('error', __('Permission denied'));
        }
    }

    private function getFilteredEmployees()
    {
        $employeeQuery = Employee::where('created_by', creatorId());

        if (Auth::user()->can('manage-own-complaints') && !Auth::user()->can('manage-any-complaints')) {
            $employeeQuery->where(function ($q) {
                $q->where('creator_id', Auth::id())->orWhere('user_id', Auth::id());
            });
        }

        return User::emp()->where('created_by', creatorId())
            ->whereIn('id', $employeeQuery->pluck('user_id'))
            ->select('id', 'name')->get();
    }

    private function getAllEmployees() {}
}
