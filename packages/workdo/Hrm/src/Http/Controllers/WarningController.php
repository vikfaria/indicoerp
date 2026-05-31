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
                        $q->where(function ($ownQuery): void {
                            $ownQuery
                                ->where('creator_id', Auth::id())
                                ->orWhere('warning_by', Auth::id())
                                ->orWhere(function ($employeeWarningQuery): void {
                                    $employeeWarningQuery
                                        ->where('employee_id', Auth::id())
                                        ->where('status', '=', 'approved');
                                });
                        });
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
            $warning->description = $validated['description'] ?? null;
            $warning->document = $validated['document'] ?? null;
            $warning->employee_id = $validated['employee_id'];
            $warning->warning_by = $validated['warning_by'];
            $warning->warning_type_id = $validated['warning_type_id'];
            $this->applyLegalWorkflowFields($warning, $validated);

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
            if (!$this->canAccessWarning($warning)) {
                return redirect()->route('hrm.warnings.index')->with('error', __('Permission denied'));
            }

            if ((bool) ($warning->is_cancelled ?? false)) {
                return redirect()->back()->with('error', __('Cancelled warnings cannot be edited.'));
            }

            $validated = $request->validated();



            $warning->subject = $validated['subject'];
            $warning->severity = $validated['severity'];
            $warning->warning_date = $validated['warning_date'];
            $warning->description = $validated['description'] ?? null;
            $warning->document = $validated['document'] ?? null;
            $warning->employee_id = $validated['employee_id'];
            $warning->warning_by = $validated['warning_by'];
            $warning->warning_type_id = $validated['warning_type_id'];
            $this->applyLegalWorkflowFields($warning, $validated);

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
            if (!$this->canAccessWarning($warning)) {
                return redirect()->route('hrm.warnings.index')->with('error', __('Permission denied'));
            }

            if ((bool) ($warning->is_cancelled ?? false)) {
                return redirect()->back()->with('error', __('Warning is already cancelled.'));
            }

            $validated = request()->validate([
                'cancellation_reason' => 'required|string|min:5|max:1000',
            ]);

            DestroyWarning::dispatch($warning);
            $warning->update([
                'is_cancelled' => true,
                'cancelled_at' => now(),
                'cancelled_by' => Auth::id(),
                'cancellation_reason' => trim((string) $validated['cancellation_reason']),
            ]);

            return redirect()->back()->with('success', __('Warning cancelled successfully.'));
        } else {
            return redirect()->route('hrm.warnings.index')->with('error', __('Permission denied'));
        }
    }

    public function response(Warning $warning)
    {
        if (Auth::user()->can('manage-warning-response')) {
            if (!$this->canAccessWarning($warning)) {
                return redirect()->route('hrm.warnings.index')->with('error', __('Permission denied'));
            }

            if ((bool) ($warning->is_cancelled ?? false)) {
                return redirect()->back()->with('error', __('Cancelled warnings cannot be processed.'));
            }

            $validated = request()->validate([
                'warning_status' => 'required|in:pending,approved,rejected',
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

    private function applyLegalWorkflowFields(Warning $warning, array $validated): void
    {
        $warning->note_of_culpa_issued_at = $validated['note_of_culpa_issued_at'] ?? null;
        $warning->note_of_culpa_delivered_at = $validated['note_of_culpa_delivered_at'] ?? null;
        $warning->worker_refused_note_of_culpa = (bool) ($validated['worker_refused_note_of_culpa'] ?? false);

        if ($warning->worker_refused_note_of_culpa) {
            $warning->refusal_witness_one_name = $validated['refusal_witness_one_name'] ?? null;
            $warning->refusal_witness_two_name = $validated['refusal_witness_two_name'] ?? null;
        } else {
            $warning->refusal_witness_one_name = null;
            $warning->refusal_witness_two_name = null;
        }

        $warning->response_deadline_at = $validated['response_deadline_at'] ?? null;
        $warning->decision_deadline_at = $validated['decision_deadline_at'] ?? null;
        $warning->disciplinary_sanction = $validated['disciplinary_sanction'] ?? null;
        $warning->disciplinary_decision_at = $validated['disciplinary_decision_at'] ?? null;
    }

    private function canAccessWarning(Warning $warning): bool
    {
        if ((int) $warning->created_by !== (int) creatorId()) {
            return false;
        }

        if (Auth::user()->can('manage-any-warnings')) {
            return true;
        }

        if ((int) $warning->creator_id === (int) Auth::id()) {
            return true;
        }

        if ((int) $warning->warning_by === (int) Auth::id()) {
            return true;
        }

        return (int) $warning->employee_id === (int) Auth::id()
            && (string) $warning->status === 'approved';
    }
}
