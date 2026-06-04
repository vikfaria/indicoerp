<?php

namespace Workdo\Hrm\Http\Controllers;

use App\Services\MozambiqueTerminationSettlementService;
use App\Services\MozambiqueProbationCessationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
    public function __construct(
        private readonly MozambiqueTerminationSettlementService $settlementService,
        private readonly MozambiqueProbationCessationService $probationCessationService
    ) {}

    public function index()
    {
        if (Auth::user()->can('manage-terminations')) {
            $terminations = Termination::query()
                ->with(['employee', 'terminationType', 'approvedBy:id,name'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-terminations')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-terminations')) {
                        $q->where('created_by', creatorId())
                            ->where(function ($ownQuery): void {
                                $ownQuery->where('creator_id', Auth::id())
                                    ->orWhere('employee_id', Auth::id());
                            });
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

            $employeeProfile = $this->resolveScopedEmployee((int) $validated['employee_id']);
            if (!$employeeProfile) {
                return redirect()->back()
                    ->withErrors(['employee_id' => __('Selected employee is invalid for this company.')])
                    ->withInput();
            }

            if (!$this->terminationTypeBelongsToCompany((int) $validated['termination_type_id'])) {
                return redirect()->back()
                    ->withErrors(['termination_type_id' => __('Selected termination type is invalid for this company.')])
                    ->withInput();
            }

            $termination = new Termination();
            $termination->notice_date = $validated['notice_date'];
            $termination->termination_date = $validated['termination_date'];
            $termination->reason = $validated['reason'];
            $termination->description = $validated['description'] ?? null;
            $termination->document = $validated['document'] ?? null;
            $termination->employee_id = $validated['employee_id'];
            $termination->termination_type_id = $validated['termination_type_id'];
            $termination->status = 'pending';
            $this->applyOffboardingChecklist($termination, $validated);
            $this->applySettlement($termination, $validated, $employeeProfile, true);

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
            if (!$this->canAccessTermination($termination)) {
                return redirect()->route('hrm.terminations.index')->with('error', __('Permission denied'));
            }

            if ((bool) ($termination->is_cancelled ?? false)) {
                return redirect()->back()->with('error', __('Cancelled terminations cannot be edited.'));
            }

            $validated = $request->validated();

            $employeeProfile = $this->resolveScopedEmployee((int) $validated['employee_id']);
            if (!$employeeProfile) {
                return redirect()->back()
                    ->withErrors(['employee_id' => __('Selected employee is invalid for this company.')])
                    ->withInput();
            }

            if (!$this->terminationTypeBelongsToCompany((int) $validated['termination_type_id'])) {
                return redirect()->back()
                    ->withErrors(['termination_type_id' => __('Selected termination type is invalid for this company.')])
                    ->withInput();
            }

            $termination->notice_date = $validated['notice_date'];
            $termination->termination_date = $validated['termination_date'];
            $termination->reason = $validated['reason'];
            $termination->description = $validated['description'] ?? null;
            $termination->document = $validated['document'] ?? null;
            $termination->employee_id = $validated['employee_id'];
            $termination->termination_type_id = $validated['termination_type_id'];
            $this->applyOffboardingChecklist($termination, $validated);
            $this->applySettlement($termination, $validated, $employeeProfile, true);

            $termination->save();
            if ((string) $termination->status === 'approved') {
                $this->syncForeignWorkerCessationFromTermination($termination);
                $this->syncProbationCessationFromTermination($termination);
            }

            UpdateTermination::dispatch($request, $termination);

            return redirect()->back()->with('success', __('The termination details are updated successfully.'));
        } else {
            return redirect()->route('hrm.terminations.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(Termination $termination)
    {
        if (Auth::user()->can('delete-terminations')) {
            if (!$this->canAccessTermination($termination)) {
                return redirect()->route('hrm.terminations.index')->with('error', __('Permission denied'));
            }

            if ((bool) ($termination->is_cancelled ?? false)) {
                return redirect()->back()->with('error', __('Termination is already cancelled.'));
            }

            $validated = request()->validate([
                'cancellation_reason' => 'required|string|min:5|max:1000',
            ]);

            DestroyTermination::dispatch($termination);
            $termination->update([
                'is_cancelled' => true,
                'cancelled_at' => now(),
                'cancelled_by' => Auth::id(),
                'cancellation_reason' => trim((string) $validated['cancellation_reason']),
            ]);

            return redirect()->back()->with('success', __('Termination cancelled successfully.'));
        } else {
            return redirect()->route('hrm.terminations.index')->with('error', __('Permission denied'));
        }
    }

    public function updateStatus(Request $request, Termination $termination)
    {
        if (Auth::user()->can('manage-termination-status')) {
            if (!$this->canAccessTermination($termination)) {
                return redirect()->route('hrm.terminations.index')->with('error', __('Permission denied'));
            }

            if ((bool) ($termination->is_cancelled ?? false)) {
                return redirect()->back()->with('error', __('Cancelled terminations cannot be processed.'));
            }

            $validated = $request->validate([
                'status' => 'required|in:pending,approved,rejected'
            ]);

            $termination->status = $validated['status'];

            if ($validated['status'] === 'approved') {
                $termination->approved_by = Auth::id();
            }

            $termination->save();
            if ($validated['status'] === 'approved') {
                $this->syncForeignWorkerCessationFromTermination($termination);
                $this->syncProbationCessationFromTermination($termination);
            }
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

    private function applyOffboardingChecklist(Termination $termination, array $validated): void
    {
        $termination->offboarding_letter_delivered_at = $validated['offboarding_letter_delivered_at'] ?? null;
        $termination->offboarding_assets_returned_at = $validated['offboarding_assets_returned_at'] ?? null;
        $termination->offboarding_access_revoked_at = $validated['offboarding_access_revoked_at'] ?? null;
        $termination->offboarding_final_payment_at = $validated['offboarding_final_payment_at'] ?? null;
        $termination->offboarding_certificate_issued_at = $validated['offboarding_certificate_issued_at'] ?? null;
        $termination->offboarding_inss_notified_at = $validated['offboarding_inss_notified_at'] ?? null;
        $termination->offboarding_migration_notified_at = $validated['offboarding_migration_notified_at'] ?? null;
        $termination->offboarding_archive_completed_at = $validated['offboarding_archive_completed_at'] ?? null;
        $termination->offboarding_completed_at = $validated['offboarding_completed_at'] ?? null;
        $termination->offboarding_notes = $validated['offboarding_notes'] ?? null;
    }

    private function applySettlement(
        Termination $termination,
        array $validated,
        ?Employee $employeeProfile,
        bool $defaultApplyIndemnity
    ): void {
        $noticeDate = (string) ($validated['notice_date'] ?? $termination->notice_date?->toDateString());
        $effectiveDate = (string) ($validated['termination_date'] ?? $termination->termination_date?->toDateString());

        if ($noticeDate === '' || $effectiveDate === '') {
            return;
        }

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
            $termination->{$field} = $value;
        }
    }

    private function canAccessTermination(Termination $termination): bool
    {
        if ((int) $termination->created_by !== (int) creatorId()) {
            return false;
        }

        if (Auth::user()->can('manage-any-terminations')) {
            return true;
        }

        return (int) $termination->creator_id === (int) Auth::id()
            || (int) $termination->employee_id === (int) Auth::id();
    }

    private function resolveScopedEmployee(int $employeeUserId): ?Employee
    {
        return Employee::query()
            ->where('created_by', creatorId())
            ->where('user_id', $employeeUserId)
            ->first();
    }

    private function terminationTypeBelongsToCompany(int $terminationTypeId): bool
    {
        return TerminationType::query()
            ->where('created_by', creatorId())
            ->where('id', $terminationTypeId)
            ->exists();
    }

    private function syncForeignWorkerCessationFromTermination(Termination $termination): void
    {
        $employee = $this->resolveScopedEmployee((int) $termination->employee_id);
        if (!$employee) {
            return;
        }

        $profile = $employee->foreignWorkerProfile;
        if (!$profile || !(bool) $profile->is_foreign_worker) {
            return;
        }

        $effectiveDate = $termination->termination_date?->toDateString();
        if (!$effectiveDate) {
            return;
        }

        $profile->cessation_effective_date = $effectiveDate;
        $profile->cessation_notification_due_at = Carbon::parse($effectiveDate)->addDays(5)->toDateString();

        if ($termination->offboarding_migration_notified_at) {
            $profile->cessation_notified_at = Carbon::parse($termination->offboarding_migration_notified_at)->toDateString();
        }

        $profile->save();
    }

    private function syncProbationCessationFromTermination(Termination $termination): void
    {
        $employee = $this->resolveScopedEmployee((int) $termination->employee_id);
        if (!$employee) {
            return;
        }

        $this->probationCessationService->close(
            $employee,
            $termination->termination_date ?? now(),
            (string) ($termination->reason ?? ''),
            $termination->offboarding_notes ? sprintf(
                'Termination #%d approved with offboarding note: %s',
                (int) $termination->id,
                (string) $termination->offboarding_notes
            ) : sprintf('Termination #%d approved.', (int) $termination->id),
            'termination',
            optional($termination->terminationType)->termination_type
        );
    }
}
