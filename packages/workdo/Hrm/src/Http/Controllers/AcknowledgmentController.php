<?php

namespace Workdo\Hrm\Http\Controllers;

use Workdo\Hrm\Models\Acknowledgment;
use Workdo\Hrm\Http\Requests\StoreAcknowledgmentRequest;
use Workdo\Hrm\Http\Requests\UpdateAcknowledgmentRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\HrmDocument;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Workdo\Hrm\Events\CreateAcknowledgment;
use Workdo\Hrm\Events\DestroyAcknowledgment;
use Workdo\Hrm\Events\UpdateAcknowledgment;

class AcknowledgmentController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-acknowledgments')) {
            $acknowledgments = Acknowledgment::with(['employee', 'document', 'assignedBy:id,name'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-acknowledgments')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-acknowledgments')) {
                        $q->where('creator_id', Auth::id())->orWhere('employee_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('acknowledgment_note'), function ($q) {
                    $q->where(function ($query) {
                        $query->where('acknowledgment_note', 'like', '%' . request('acknowledgment_note') . '%')
                            ->orWhereHas('employee', function ($empQuery) {
                                $empQuery->where('name', 'like', '%' . request('acknowledgment_note') . '%');
                            })
                            ->orWhereHas('document', function ($docQuery) {
                                $docQuery->where('title', 'like', '%' . request('acknowledgment_note') . '%');
                            });
                    });
                })
                ->when(request('employee_id') && request('employee_id') !== '', fn($q) => $q->where('employee_id', request('employee_id')))
                ->when(request('document_id') && request('document_id') !== '', fn($q) => $q->where('document_id', request('document_id')))
                ->when(request('status') !== null && request('status') !== '', fn($q) => $q->where('status', request('status')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('Hrm/Acknowledgments/Index', [
                'acknowledgments' => $acknowledgments,
                'users' => User::whereIn('id', Employee::where('created_by', creatorId())->pluck('user_id'))->select('id', 'name')->get(),
                'hrmdocuments' => HrmDocument::where('created_by', creatorId())->where('status', 'approve')->select('id', 'title')->get(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreAcknowledgmentRequest $request)
    {
        if (Auth::user()->can('create-acknowledgments')) {
            $validated = $request->validated();



            $acknowledgment = new Acknowledgment();
            $acknowledgment->employee_id = $validated['employee_id'];
            $acknowledgment->document_id = $validated['document_id'];
            $acknowledgment->acknowledgment_note = $validated['acknowledgment_note'] ?? null;
            $acknowledgment->assigned_by = Auth::id();
            $acknowledgment->creator_id = Auth::id();
            $acknowledgment->created_by = creatorId();
            $acknowledgment->save();

            CreateAcknowledgment::dispatch($request, $acknowledgment);

            return redirect()->route('hrm.acknowledgments.index')->with('success', __('The acknowledgment has been created successfully.'));
        } else {
            return redirect()->route('hrm.acknowledgments.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateAcknowledgmentRequest $request, Acknowledgment $acknowledgment)
    {
        if (Auth::user()->can('edit-acknowledgments')) {
            $validated = $request->validated();



            $acknowledgment->employee_id = $validated['employee_id'];
            $acknowledgment->document_id = $validated['document_id'];
            $acknowledgment->acknowledgment_note = $validated['acknowledgment_note'] ?? null;

            $acknowledgment->save();

            UpdateAcknowledgment::dispatch($request, $acknowledgment);

            return redirect()->back()->with('success', __('The acknowledgment details are updated successfully.'));
        } else {
            return redirect()->route('hrm.acknowledgments.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(Acknowledgment $acknowledgment)
    {
        if (Auth::user()->can('delete-acknowledgments')) {
            DestroyAcknowledgment::dispatch($acknowledgment);
            $acknowledgment->delete();

            return redirect()->back()->with('success', __('The acknowledgment has been deleted.'));
        } else {
            return redirect()->route('hrm.acknowledgments.index')->with('error', __('Permission denied'));
        }
    }



    public function updateStatus(Request $request, Acknowledgment $acknowledgment)
    {
        if (Auth::user()->can('manage-acknowledgment-status')) {
            $validated = $request->validate([
                'status' => ['required', Rule::in(['pending', 'acknowledged'])]
            ]);

            $acknowledgment->status = $validated['status'];

            if ($validated['status'] === 'acknowledged') {
                $acknowledgment->acknowledged_at = now();
            }

            $acknowledgment->save();

            return redirect()->back()->with('success', __('Acknowledgment status updated successfully.'));
        } else {
            return redirect()->route('hrm.acknowledgments.index')->with('error', __('Permission denied'));
        }
    }
}
