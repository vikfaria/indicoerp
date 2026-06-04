<?php

namespace Workdo\Hrm\Http\Controllers;

use App\Services\MozambiqueTerminationSettlementService;
use App\Services\MozambiqueProbationCessationService;
use App\Models\User;
use Carbon\Carbon;
use Workdo\Hrm\Models\Resignation;
use Workdo\Hrm\Http\Requests\StoreResignationRequest;
use Workdo\Hrm\Http\Requests\UpdateResignationRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Workdo\Hrm\Events\UpdateResignaionStatus;
use Workdo\Hrm\Models\Employee;

class ResignationController extends Controller
{
    public function __construct(
        private readonly MozambiqueTerminationSettlementService $settlementService,
        private readonly MozambiqueProbationCessationService $probationCessationService
    ) {}

    public function index()
    {
        if (Auth::user()->can('manage-resignations')) {
            $resignations = Resignation::with([
                'employee:id,name',
                'approvedBy:id,name',
                'cancelledBy:id,name',
            ])->where(function ($q) {
                if (Auth::user()->can('manage-any-resignations')) {
                    $q->where('created_by', creatorId());
                } elseif (Auth::user()->can('manage-own-resignations')) {
                    $q->where('created_by', creatorId())
                        ->where(function ($ownQuery): void {
                            $ownQuery->where('creator_id', Auth::id())
                                ->orWhere('employee_id', Auth::id());
                        });
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
                ->active()
                ->when(request('name'), function ($q) {
                    $q->whereHas('employee', function ($query) {
                        $query->where('name', 'like', '%' . request('name') . '%');
                    });
                })
                ->when(request('employee_id'), fn($q) => $q->where('employee_id', request('employee_id')))

                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('Hrm/Resignations/Index', [
                'resignations' => $resignations,
                'employees' => $this->getFilteredEmployees(),
                'users' => User::where('created_by', creatorId())->select('id', 'name')->get(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreResignationRequest $request)
    {
        if (Auth::user()->can('create-resignations')) {
            $validated = $request->validated();
            $resignation = new Resignation();
            $resignation->employee_id = $validated['employee_id'];
            $resignation->last_working_date = $validated['last_working_date'];
            $resignation->reason = $validated['reason'];
            $resignation->description = $validated['description'] ?? null;
            $resignation->document = $validated['document'] ?? null;
            $this->applySettlement($resignation, $validated, now()->toDateString(), false);

            $resignation->creator_id = Auth::id();
            $resignation->created_by = creatorId();
            $resignation->save();

            return redirect()->route('hrm.resignations.index')->with('success', __('The resignation has been created successfully.'));
        } else {
            return redirect()->route('hrm.resignations.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateResignationRequest $request, Resignation $resignation)
    {
        if (Auth::user()->can('edit-resignations')) {
            if (!$this->canAccessResignation($resignation)) {
                return redirect()->route('hrm.resignations.index')->with('error', __('Permission denied'));
            }

            if ((bool) ($resignation->is_cancelled ?? false)) {
                return redirect()->back()->with('error', __('Cancelled resignations cannot be edited.'));
            }

            $validated = $request->validated();



            $resignation->employee_id = $validated['employee_id'];
            $resignation->last_working_date = $validated['last_working_date'];
            $resignation->reason = $validated['reason'];
            $resignation->description = $validated['description'] ?? null;
            $resignation->document = $validated['document'] ?? null;
            $noticeDate = optional($resignation->created_at)->toDateString() ?? now()->toDateString();
            $this->applySettlement($resignation, $validated, $noticeDate, false);

            $resignation->save();
            if ((string) $resignation->status === 'accepted') {
                $this->syncForeignWorkerCessationFromResignation($resignation);
                $this->syncProbationCessationFromResignation($resignation);
            }

            return redirect()->back()->with('success', __('The resignation details are updated successfully.'));
        } else {
            return redirect()->route('hrm.resignations.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(Resignation $resignation)
    {
        if (Auth::user()->can('delete-resignations')) {
            if (!$this->canAccessResignation($resignation)) {
                return redirect()->route('hrm.resignations.index')->with('error', __('Permission denied'));
            }

            if ((bool) ($resignation->is_cancelled ?? false)) {
                return redirect()->back()->with('error', __('Resignation is already cancelled.'));
            }

            $validated = request()->validate([
                'cancellation_reason' => 'required|string|min:5|max:1000',
            ]);

            $resignation->update([
                'is_cancelled' => true,
                'cancelled_at' => now(),
                'cancelled_by' => Auth::id(),
                'cancellation_reason' => trim((string) $validated['cancellation_reason']),
            ]);

            return redirect()->back()->with('success', __('Resignation cancelled successfully.'));
        } else {
            return redirect()->route('hrm.resignations.index')->with('error', __('Permission denied'));
        }
    }

    public function updateStatus(Request $request, Resignation $resignation, $status)
    {
        if (Auth::user()->can('manage-resignation-status')) {
            if (!$this->canAccessResignation($resignation)) {
                return redirect()->route('hrm.resignations.index')->with('error', __('Permission denied'));
            }

            if ((bool) ($resignation->is_cancelled ?? false)) {
                return redirect()->back()->with('error', __('Cancelled resignations cannot be processed.'));
            }

             if (!in_array((string) $status, ['pending', 'accepted', 'rejected'], true)) {
                 return redirect()->back()->with('error', __('Invalid resignation status.'));
             }

            $resignation->status = $status;
            $resignation->approved_by = Auth::id();
            $resignation->save();
            if ($status === 'accepted') {
                $this->syncForeignWorkerCessationFromResignation($resignation);
                $this->syncProbationCessationFromResignation($resignation);
            }
            UpdateResignaionStatus::dispatch($request, $resignation);

            return redirect()->back()->with('success', __('The resignation status has been updated.'));
        } else {
            return redirect()->route('hrm.resignations.index')->with('error', __('Permission denied'));
        }
    }

    private function getFilteredUsers()
    {
        return User::emp()->where('created_by', creatorId())
            ->when(!Auth::user()->can('manage-any-resignations'), function ($q) {
                if (Auth::user()->can('manage-own-resignations')) {
                    $q->where('creator_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->select('id', 'name')->get();
    }

    private function canAccessResignation(Resignation $resignation): bool
    {
        if ((int) $resignation->created_by !== (int) creatorId()) {
            return false;
        }

        if (Auth::user()->can('manage-any-resignations')) {
            return true;
        }

        return (int) $resignation->creator_id === (int) Auth::id()
            || (int) $resignation->employee_id === (int) Auth::id();
    }

    private function applySettlement(
        Resignation $resignation,
        array $validated,
        string $noticeDate,
        bool $defaultApplyIndemnity
    ): void {
        $effectiveDate = (string) ($validated['last_working_date'] ?? $resignation->last_working_date?->toDateString());

        if ($effectiveDate === '') {
            return;
        }

        $employeeProfile = $this->resolveScopedEmployee((int) $validated['employee_id']);
        $companyId = (int) creatorId();
        $settlement = $this->settlementService->build(
            $employeeProfile,
            $noticeDate,
            $effectiveDate,
            $validated,
            $defaultApplyIndemnity,
            $companyId
        );

        foreach ($settlement as $field => $value) {
            $resignation->{$field} = $value;
        }
    }


    private function getFilteredEmployees()
    {
        $employeeQuery = Employee::where('created_by', creatorId());

        if (Auth::user()->can('manage-own-resignations') && !Auth::user()->can('manage-any-resignations')) {
            $employeeQuery->where(function ($q) {
                $q->where('creator_id', Auth::id())->orWhere('user_id', Auth::id());
            });
        }

        return User::emp()->where('created_by', creatorId())
            ->whereIn('id', $employeeQuery->pluck('user_id'))
            ->select('id', 'name')->get();
    }

    private function resolveScopedEmployee(int $employeeUserId): ?Employee
    {
        return Employee::query()
            ->where('created_by', creatorId())
            ->where('user_id', $employeeUserId)
            ->first();
    }

    private function syncForeignWorkerCessationFromResignation(Resignation $resignation): void
    {
        $employee = $this->resolveScopedEmployee((int) $resignation->employee_id);
        if (!$employee) {
            return;
        }

        $profile = $employee->foreignWorkerProfile;
        if (!$profile || !(bool) $profile->is_foreign_worker) {
            return;
        }

        $effectiveDate = $resignation->last_working_date?->toDateString();
        if (!$effectiveDate) {
            return;
        }

        $profile->cessation_effective_date = $effectiveDate;
        $profile->cessation_notification_due_at = Carbon::parse($effectiveDate)->addDays(5)->toDateString();
        $profile->save();
    }

    private function syncProbationCessationFromResignation(Resignation $resignation): void
    {
        $employee = $this->resolveScopedEmployee((int) $resignation->employee_id);
        if (!$employee) {
            return;
        }

        $this->probationCessationService->close(
            $employee,
            $resignation->last_working_date ?? now(),
            (string) ($resignation->reason ?? ''),
            $resignation->description ? sprintf(
                'Resignation #%d accepted with description: %s',
                (int) $resignation->id,
                (string) $resignation->description
            ) : sprintf('Resignation #%d accepted.', (int) $resignation->id),
            'resignation',
            null
        );
    }
}
