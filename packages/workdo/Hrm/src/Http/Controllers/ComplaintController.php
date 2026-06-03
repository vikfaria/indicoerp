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
use Workdo\Hrm\Models\Warning;
use Workdo\Hrm\Models\WarningType;

class ComplaintController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-complaints')) {
            $currentUserId = (int) Auth::id();

            $complaints = Complaint::query()
                ->with(['employee', 'againstEmployee', 'complaintType', 'resolvedBy', 'handlingOwner'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-complaints')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-complaints')) {
                        $q->where(function ($ownQuery): void {
                            $ownQuery
                                ->where('creator_id', Auth::id())
                                ->orWhere('employee_id', Auth::id())
                                ->orWhere('against_employee_id', Auth::id())
                                ->orWhere('handling_owner_id', Auth::id())
                                ->orWhere('resolved_by', Auth::id())
                                ->orWhereJsonContains('confidential_access_user_ids', Auth::id());
                        });
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(!Auth::user()->can('manage-confidential-complaints'), function ($query) use ($currentUserId) {
                    $query->where(function ($confidentialQuery) use ($currentUserId): void {
                        $confidentialQuery
                            ->whereNull('is_confidential')
                            ->orWhere('is_confidential', false)
                            ->orWhere('creator_id', $currentUserId)
                            ->orWhere('employee_id', $currentUserId)
                            ->orWhere('against_employee_id', $currentUserId)
                            ->orWhere('handling_owner_id', $currentUserId)
                            ->orWhere('resolved_by', $currentUserId)
                            ->orWhereJsonContains('confidential_access_user_ids', $currentUserId);
                    });
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
                ->when(request('status') && request('status') !== 'all', function ($q) {
                    if (request('status') === 'cancelled') {
                        return $q->where('is_cancelled', true);
                    }

                    return $q->active()->where('status', request('status'));
                })
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
            $complaint->document = $validated['document'] ?? null;
            $complaint->status = 'pending';
            $this->applyCaseComplianceFields($complaint, $validated);
            $complaint->creator_id = Auth::id();
            $complaint->created_by = creatorId();
            $complaint->save();
            $this->ensureHarassmentDisciplinaryCase($complaint);

            CreateComplaint::dispatch($request, $complaint);

            return redirect()->route('hrm.complaints.index')->with('success', __('The complaint has been created successfully.'));
        } else {
            return redirect()->route('hrm.complaints.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateComplaintRequest $request, Complaint $complaint)
    {
        if (Auth::user()->can('edit-complaints')) {
            if (!$this->canAccessComplaint($complaint) || !$this->canViewConfidentialComplaint($complaint)) {
                return redirect()->route('hrm.complaints.index')->with('error', __('Permission denied'));
            }

            if ((bool) ($complaint->is_cancelled ?? false)) {
                return redirect()->back()->with('error', __('Cancelled complaints cannot be edited.'));
            }

            $validated = $request->validated();

            $complaint->employee_id = $validated['employee_id'] === 'other' ? null : $validated['employee_id'];
            $complaint->against_employee_id = $validated['against_employee_id'] === 'other' ? null : $validated['against_employee_id'];
            $complaint->complaint_type_id = $validated['complaint_type_id'];
            $complaint->subject = $validated['subject'];
            $complaint->description = $validated['description'];
            $complaint->complaint_date = $validated['complaint_date'];
            $complaint->document = $validated['document'] ?? null;
            $this->applyCaseComplianceFields($complaint, $validated);
            $complaint->save();
            $this->ensureHarassmentDisciplinaryCase($complaint);

            UpdateComplaint::dispatch($request, $complaint);

            return redirect()->back()->with('success', __('The complaint details are updated successfully.'));
        } else {
            return redirect()->route('hrm.complaints.index')->with('error', __('Permission denied'));
        }
    }

    public function updateStatus(Complaint $complaint)
    {
        if (Auth::user()->can('manage-complaint-status')) {
            if (!$this->canAccessComplaint($complaint) || !$this->canViewConfidentialComplaint($complaint)) {
                return redirect()->route('hrm.complaints.index')->with('error', __('Permission denied'));
            }

            if ((bool) ($complaint->is_cancelled ?? false)) {
                return redirect()->back()->with('error', __('Cancelled complaints cannot be processed.'));
            }

            $validated = request()->validate([
                'status' => 'required|in:pending,in review,assigned,in progress,resolved'
            ]);

            if (
                $complaint->is_harassment_report
                && in_array($validated['status'], ['assigned', 'in progress', 'resolved'], true)
                && !$complaint->handling_owner_id
            ) {
                return redirect()->back()->with('error', __('Assign a handling owner before progressing harassment reports.'));
            }

            $complaint->status = $validated['status'];
            $complaint->resolved_by = Auth::id();
            if ($validated['status'] === 'resolved') {
                $complaint->resolution_date = now()->toDateString();
                if (!$complaint->investigation_started_at) {
                    $complaint->investigation_started_at = now()->toDateString();
                }
                if (!$complaint->investigation_closed_at) {
                    $complaint->investigation_closed_at = now()->toDateString();
                }
            } elseif (in_array($validated['status'], ['in review', 'assigned', 'in progress'], true) && !$complaint->investigation_started_at) {
                $complaint->investigation_started_at = now()->toDateString();
            }

            $complaint->save();
            $this->ensureHarassmentDisciplinaryCase($complaint);

            return redirect()->back()->with('success', __('The complaint status has been updated successfully.'));
        } else {
            return redirect()->route('hrm.complaints.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(Complaint $complaint)
    {
        if (Auth::user()->can('delete-complaints')) {
            if (!$this->canAccessComplaint($complaint) || !$this->canViewConfidentialComplaint($complaint)) {
                return redirect()->route('hrm.complaints.index')->with('error', __('Permission denied'));
            }

            if ((bool) ($complaint->is_cancelled ?? false)) {
                return redirect()->back()->with('error', __('Complaint is already cancelled.'));
            }

            $validated = request()->validate([
                'cancellation_reason' => 'required|string|min:5|max:1000',
            ]);

            DestroyComplaint::dispatch($complaint);
            $complaint->update([
                'is_cancelled' => true,
                'cancelled_at' => now(),
                'cancelled_by' => Auth::id(),
                'cancellation_reason' => trim((string) $validated['cancellation_reason']),
            ]);

            return redirect()->back()->with('success', __('Complaint cancelled successfully.'));
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

    private function applyCaseComplianceFields(Complaint $complaint, array $validated): void
    {
        $isHarassmentReport = (bool) ($validated['is_harassment_report'] ?? false);
        $isConfidential = (bool) ($validated['is_confidential'] ?? false);

        if ($isHarassmentReport) {
            $isConfidential = true;
        }

        $complaint->is_confidential = $isConfidential;
        $complaint->is_harassment_report = $isHarassmentReport;
        $complaint->confidential_channel = $validated['confidential_channel'] ?? null;
        $requestedConfidentialityLevel = $validated['confidentiality_level'] ?? null;
        if ($isHarassmentReport && in_array($requestedConfidentialityLevel, [null, '', 'internal'], true)) {
            $requestedConfidentialityLevel = 'restricted';
        }

        $complaint->confidentiality_level = $requestedConfidentialityLevel ?? 'internal';
        $complaint->handling_owner_id = $validated['handling_owner_id'] ?? null;
        $complaint->investigation_started_at = $validated['investigation_started_at'] ?? null;
        $complaint->investigation_closed_at = $validated['investigation_closed_at'] ?? null;

        $requestedAccessUserIds = collect((array) ($validated['confidential_access_user_ids'] ?? []))
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique();

        $baseAccessUserIds = collect([
            (int) Auth::id(),
            (int) ($complaint->creator_id ?? 0),
            (int) ($validated['employee_id'] ?? 0),
            (int) ($validated['against_employee_id'] ?? 0),
            (int) ($validated['handling_owner_id'] ?? 0),
            (int) ($complaint->resolved_by ?? 0),
        ])->filter(static fn (int $id): bool => $id > 0)->unique();

        $complaint->confidential_access_user_ids = $isConfidential
            ? $requestedAccessUserIds->merge($baseAccessUserIds)->unique()->values()->all()
            : null;
    }

    private function canAccessComplaint(Complaint $complaint): bool
    {
        if ((int) $complaint->created_by !== (int) creatorId()) {
            return false;
        }

        if (Auth::user()->can('manage-any-complaints')) {
            return true;
        }

        $currentUserId = (int) Auth::id();

        if (in_array($currentUserId, $this->relatedComplaintUserIds($complaint), true)) {
            return true;
        }

        return in_array($currentUserId, $this->normalizedConfidentialAccessUserIds($complaint), true);
    }

    private function canViewConfidentialComplaint(Complaint $complaint): bool
    {
        if (!(bool) ($complaint->is_confidential ?? false)) {
            return true;
        }

        if (Auth::user()->can('manage-confidential-complaints')) {
            return true;
        }

        $currentUserId = (int) Auth::id();

        if (in_array($currentUserId, $this->relatedComplaintUserIds($complaint), true)) {
            return true;
        }

        return in_array($currentUserId, $this->normalizedConfidentialAccessUserIds($complaint), true);
    }

    /**
     * @return array<int, int>
     */
    private function relatedComplaintUserIds(Complaint $complaint): array
    {
        return array_values(array_unique(array_filter([
            (int) ($complaint->creator_id ?? 0),
            (int) ($complaint->employee_id ?? 0),
            (int) ($complaint->against_employee_id ?? 0),
            (int) ($complaint->handling_owner_id ?? 0),
            (int) ($complaint->resolved_by ?? 0),
        ], static fn (int $id): bool => $id > 0)));
    }

    /**
     * @return array<int, int>
     */
    private function normalizedConfidentialAccessUserIds(Complaint $complaint): array
    {
        return collect((array) ($complaint->confidential_access_user_ids ?? []))
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function ensureHarassmentDisciplinaryCase(Complaint $complaint): void
    {
        if (!(bool) ($complaint->is_harassment_report ?? false)) {
            return;
        }

        if ((bool) ($complaint->is_cancelled ?? false)) {
            return;
        }

        if (!empty($complaint->disciplinary_warning_id)) {
            return;
        }

        if (empty($complaint->handling_owner_id)) {
            return;
        }

        $companyId = (int) ($complaint->created_by ?? creatorId());
        $employeeId = (int) ($complaint->against_employee_id ?: $complaint->employee_id);

        if ($companyId <= 0 || $employeeId <= 0) {
            return;
        }

        $warningType = $this->resolveHarassmentWarningType($companyId);
        $subject = trim(sprintf('[%s #%d] %s', 'Harassment report', (int) $complaint->id, (string) $complaint->subject));
        $subject = mb_substr($subject, 0, 255);

        $description = trim((string) $complaint->description);
        if ($description === '') {
            $description = __('Complaint requires disciplinary follow-up.');
        }

        $warning = new Warning();
        $warning->subject = $subject;
        $warning->severity = 'high';
        $warning->warning_date = $complaint->complaint_date ?: now()->toDateString();
        $warning->description = $description;
        $warning->document = $complaint->document;
        $warning->employee_id = $employeeId;
        $warning->warning_by = (int) $complaint->handling_owner_id;
        $warning->warning_type_id = $warningType?->id;
        $warning->creator_id = Auth::id();
        $warning->created_by = $companyId;
        $warning->save();

        $complaint->forceFill([
            'disciplinary_warning_id' => $warning->id,
            'disciplinary_case_opened_at' => now()->toDateString(),
        ])->saveQuietly();
    }

    private function resolveHarassmentWarningType(int $companyId): ?WarningType
    {
        $existing = WarningType::query()
            ->where('created_by', $companyId)
            ->where('warning_type_name', 'Harassment Investigation')
            ->first();

        if ($existing) {
            return $existing;
        }

        return WarningType::query()->create([
            'warning_type_name' => 'Harassment Investigation',
            'creator_id' => Auth::id(),
            'created_by' => $companyId,
        ]);
    }
}
