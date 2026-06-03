<?php

namespace Workdo\Hrm\Http\Controllers;

use App\Services\MozambiqueForeignWorkerQuotaService;
use App\Services\MozambiqueProbationPolicyService;
use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Workdo\Hrm\Http\Requests\StoreEmployeeDependentRequest;
use Workdo\Hrm\Http\Requests\UpdateEmployeeDependentRequest;
use Workdo\Hrm\Http\Requests\UpsertEmployeeForeignWorkerProfileRequest;
use Workdo\Hrm\Http\Requests\UpsertEmployeeProbationProfileRequest;
use Workdo\Hrm\Http\Requests\UpsertEmployeeSocialSecurityProfileRequest;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\EmployeeDependent;
use Workdo\Hrm\Models\EmployeeForeignWorkerProfile;
use Workdo\Hrm\Models\EmployeeProbationProfile;
use Workdo\Hrm\Models\EmployeeSocialSecurityProfile;

class EmployeeLegalProfileController extends Controller
{
    public function __construct(
        private readonly MozambiqueForeignWorkerQuotaService $foreignWorkerQuotaService,
        private readonly MozambiqueProbationPolicyService $probationPolicyService
    ) {}

    public function upsertSocialSecurity(UpsertEmployeeSocialSecurityProfileRequest $request, Employee $employee)
    {
        if (!$this->canManageEmployee($employee)) {
            return redirect()->back()->with('error', __('Permission denied'));
        }

        $validated = $request->validated();
        $profile = EmployeeSocialSecurityProfile::query()->firstOrNew(['employee_id' => $employee->id]);

        $profile->fill($validated);
        $profile->employee_id = $employee->id;
        $profile->creator_id = Auth::id();
        $profile->created_by = creatorId();
        $profile->save();

        return redirect()->back()->with('success', __('Employee INSS profile updated successfully.'));
    }

    public function upsertForeignWorker(UpsertEmployeeForeignWorkerProfileRequest $request, Employee $employee)
    {
        if (!$this->canManageEmployee($employee)) {
            return redirect()->back()->with('error', __('Permission denied'));
        }

        $validated = $request->validated();
        $profile = EmployeeForeignWorkerProfile::query()->firstOrNew(['employee_id' => $employee->id]);
        $alreadyForeignWorker = (bool) $profile->is_foreign_worker;
        $wantsForeignWorker = (bool) $validated['is_foreign_worker'];

        if ($wantsForeignWorker) {
            $quotaCheck = $this->foreignWorkerQuotaService->canEnableForeignWorker(
                creatorId(),
                (int) $employee->id,
                $alreadyForeignWorker
            );

            if (!$quotaCheck['allowed']) {
                return redirect()->back()->with('error', $quotaCheck['message'] ?? __('Foreign worker quota exceeded.'));
            }
        }

        if (!empty($validated['cessation_effective_date']) && empty($validated['cessation_notification_due_at'])) {
            $validated['cessation_notification_due_at'] = Carbon::parse($validated['cessation_effective_date'])
                ->addDays(5)
                ->toDateString();
        }

        if (!empty($validated['cessation_effective_date']) && !empty($validated['cessation_notified_at'])) {
            $notificationDeadline = Carbon::parse($validated['cessation_effective_date'])->addDays(5)->endOfDay();
            $notifiedAt = Carbon::parse($validated['cessation_notified_at'])->endOfDay();

            if ($notifiedAt->greaterThan($notificationDeadline)) {
                return redirect()->back()->withErrors([
                    'cessation_notified_at' => __('Cessation notification for foreign workers must be registered within 5 calendar days after cessation date.'),
                ]);
            }
        }

        $profile->fill($validated);
        $profile->employee_id = $employee->id;
        $profile->residency_status = $validated['residency_status'] ?? 'resident';
        $profile->creator_id = Auth::id();
        $profile->created_by = creatorId();
        $profile->save();

        return redirect()->back()->with('success', __('Employee foreign worker profile updated successfully.'));
    }

    public function upsertProbation(UpsertEmployeeProbationProfileRequest $request, Employee $employee)
    {
        if (!$this->canManageEmployee($employee)) {
            return redirect()->back()->with('error', __('Permission denied'));
        }

        $validated = $request->validated();
        $profile = EmployeeProbationProfile::query()->firstOrNew(['employee_id' => $employee->id]);

        $probationCategory = $validated['probation_category'];
        $legalMaxDays = $this->probationPolicyService->legalMaxDaysFor($probationCategory, creatorId());
        $startsAt = Carbon::parse($validated['starts_at'])->startOfDay();
        $expectedEndDate = !empty($validated['expected_end_at'])
            ? Carbon::parse($validated['expected_end_at'])->startOfDay()
            : Carbon::parse($this->probationPolicyService->calculateExpectedEndDate($validated['starts_at'], $probationCategory, creatorId()))->startOfDay();

        $legalMaxDate = $startsAt->copy()->addDays($legalMaxDays);
        if ($expectedEndDate->greaterThan($legalMaxDate)) {
            return redirect()->back()->withErrors([
                'expected_end_at' => __('Expected probation end date exceeds legal maximum (:days days) for selected category.', [
                    'days' => $legalMaxDays,
                ]),
            ]);
        }

        $profile->fill([
            ...$validated,
            'expected_end_at' => $expectedEndDate->toDateString(),
            'legal_max_days' => $legalMaxDays,
        ]);
        $profile->employee_id = $employee->id;
        $profile->creator_id = Auth::id();
        $profile->created_by = creatorId();
        $profile->save();

        return redirect()->back()->with('success', __('Employee probation profile updated successfully.'));
    }

    public function storeDependent(StoreEmployeeDependentRequest $request, Employee $employee)
    {
        if (!$this->canManageEmployee($employee)) {
            return redirect()->back()->with('error', __('Permission denied'));
        }

        $validated = $request->validated();
        EmployeeDependent::query()->create([
            ...$validated,
            'employee_id' => $employee->id,
            'is_student' => (bool) ($validated['is_student'] ?? false),
            'creator_id' => Auth::id(),
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Employee dependent created successfully.'));
    }

    public function updateDependent(UpdateEmployeeDependentRequest $request, Employee $employee, EmployeeDependent $dependent)
    {
        if (!$this->canManageEmployee($employee)) {
            return redirect()->back()->with('error', __('Permission denied'));
        }

        if (
            (int) $dependent->employee_id !== (int) $employee->id
            || (int) $dependent->created_by !== (int) creatorId()
        ) {
            return redirect()->back()->with('error', __('Dependent not found.'));
        }

        if ((bool) ($dependent->is_cancelled ?? false)) {
            return redirect()->back()->with('error', __('Cancelled dependents cannot be edited.'));
        }

        $validated = $request->validated();
        $dependent->update([
            ...$validated,
            'is_student' => (bool) ($validated['is_student'] ?? false),
        ]);

        return redirect()->back()->with('success', __('Employee dependent updated successfully.'));
    }

    public function destroyDependent(Employee $employee, EmployeeDependent $dependent)
    {
        if (!$this->canManageEmployee($employee)) {
            return redirect()->back()->with('error', __('Permission denied'));
        }

        if (
            (int) $dependent->employee_id !== (int) $employee->id
            || (int) $dependent->created_by !== (int) creatorId()
        ) {
            return redirect()->back()->with('error', __('Dependent not found.'));
        }

        if ((bool) ($dependent->is_cancelled ?? false)) {
            return redirect()->back()->with('error', __('Dependent is already cancelled.'));
        }

        $validated = request()->validate([
            'cancellation_reason' => 'required|string|min:5|max:1000',
        ]);

        $dependent->update([
            'is_cancelled' => true,
            'cancelled_at' => now(),
            'cancelled_by' => Auth::id(),
            'cancellation_reason' => trim((string) $validated['cancellation_reason']),
        ]);

        return redirect()->back()->with('success', __('Employee dependent cancelled successfully.'));
    }

    private function canManageEmployee(Employee $employee): bool
    {
        if (!Auth::user()->can('edit-employees')) {
            return false;
        }

        if (Auth::user()->can('manage-any-employees')) {
            return (int) $employee->created_by === (int) creatorId();
        }

        if (Auth::user()->can('manage-own-employees')) {
            return (int) $employee->creator_id === (int) Auth::id()
                || (int) $employee->user_id === (int) Auth::id();
        }

        return false;
    }
}
