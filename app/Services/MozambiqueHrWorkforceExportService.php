<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Workdo\Contract\Models\Contract;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\EmployeeForeignWorkerProfile;
use Workdo\Hrm\Models\EmployeeProbationProfile;
use Workdo\Hrm\Models\EmployeeSocialSecurityProfile;
use Workdo\Hrm\Models\LeaveApplication;
use Workdo\Hrm\Models\PayrollEntry;

class MozambiqueHrWorkforceExportService
{
    public function __construct(
        private readonly MozambiqueProbationPolicyService $probationPolicyService
    ) {}

    public function buildDataset(int $companyId, ?string $referenceDate = null): array
    {
        $asOfDate = $this->resolveReferenceDate($referenceDate);

        $employees = Employee::query()
            ->where('created_by', $companyId)
            ->with([
                'user:id,name',
                'branch:id,branch_name',
                'department:id,department_name',
                'designation:id,designation_name',
                'dependents:id,employee_id,is_tax_eligible',
                'socialSecurityProfile:id,employee_id,inss_number,registration_date,registration_status',
                'foreignWorkerProfile:id,employee_id,is_foreign_worker,nationality,residency_status,passport_number,passport_expires_at,visa_type,visa_expires_at,work_authorization_number,work_authorization_expires_at,hiring_regime,work_province,mozambique_entry_date,cessation_effective_date,cessation_notification_due_at,cessation_notified_at',
                'probationProfile:id,employee_id,probation_category,starts_at,expected_end_at,decision_status,decision_date,evaluation_status',
            ])
            ->orderBy('employee_id')
            ->orderBy('id')
            ->get([
                'id',
                'employee_id',
                'user_id',
                'date_of_joining',
                'employment_type',
                'tax_payer_id',
                'basic_salary',
                'branch_id',
                'department_id',
                'designation_id',
                'created_by',
            ]);

        $employeeUserIds = $employees->pluck('user_id')
            ->filter(static fn ($id): bool => (int) $id > 0)
            ->map(static fn ($id): int => (int) $id)
            ->values();

        $leaveStatsByUser = $this->resolveApprovedLeaveStatsByUser($companyId, $employeeUserIds, $asOfDate);
        $latestPayrollByUser = $this->resolveLatestPayrollByUser($companyId, $employeeUserIds, $asOfDate);
        $contractsByUser = $this->resolveLabourContractsByUser($companyId, $employeeUserIds, $asOfDate);

        $rows = $employees->map(function (Employee $employee) use ($asOfDate, $leaveStatsByUser, $latestPayrollByUser, $contractsByUser): array {
            $dependents = $employee->dependents ?? collect();
            $socialSecurityProfile = $employee->socialSecurityProfile;
            $foreignProfile = $employee->foreignWorkerProfile;
            $probationProfile = $employee->probationProfile;
            $contract = $contractsByUser->get((int) $employee->user_id);
            $leaveStats = $leaveStatsByUser->get((int) $employee->user_id, [
                'approved_days_current_year' => 0.0,
                'approved_days_total' => 0.0,
            ]);
            $latestPayroll = $latestPayrollByUser->get((int) $employee->user_id, [
                'latest_pay_date' => null,
                'latest_gross_pay' => 0.0,
                'latest_net_pay' => 0.0,
                'latest_irps_amount' => 0.0,
                'latest_inss_employee_amount' => 0.0,
                'latest_inss_employer_amount' => 0.0,
            ]);

            return [
                'reference_date' => $asOfDate->toDateString(),
                'employee_record_id' => $employee->id,
                'employee_internal_id' => $employee->employee_id,
                'employee_name' => $employee->user?->name ?? ('Employee #' . $employee->id),
                'employment_type' => $employee->employment_type ?? '',
                'date_of_joining' => optional($employee->date_of_joining)->toDateString(),
                'branch' => $employee->branch?->branch_name ?? '',
                'department' => $employee->department?->department_name ?? '',
                'designation' => $employee->designation?->designation_name ?? '',
                'employee_nuit' => $employee->tax_payer_id ?? '',
                'basic_salary' => (float) ($employee->basic_salary ?? 0),
                'inss_number' => $socialSecurityProfile?->inss_number ?? '',
                'inss_registration_status' => $socialSecurityProfile?->registration_status ?? 'not_registered',
                'inss_registration_date' => optional($socialSecurityProfile?->registration_date)->toDateString(),
                'dependents_total' => $dependents->count(),
                'dependents_tax_eligible' => $dependents->where('is_tax_eligible', true)->count(),
                'is_foreign_worker' => (bool) ($foreignProfile?->is_foreign_worker ?? false),
                'nationality' => $foreignProfile?->nationality ?? '',
                'residency_status' => $foreignProfile?->residency_status ?? 'resident',
                'hiring_regime' => $foreignProfile?->hiring_regime ?? '',
                'work_province' => $foreignProfile?->work_province ?? '',
                'passport_number' => $foreignProfile?->passport_number ?? '',
                'passport_expires_at' => optional($foreignProfile?->passport_expires_at)->toDateString(),
                'visa_type' => $foreignProfile?->visa_type ?? '',
                'visa_expires_at' => optional($foreignProfile?->visa_expires_at)->toDateString(),
                'work_authorization_number' => $foreignProfile?->work_authorization_number ?? '',
                'work_authorization_expires_at' => optional($foreignProfile?->work_authorization_expires_at)->toDateString(),
                'mozambique_entry_date' => optional($foreignProfile?->mozambique_entry_date)->toDateString(),
                'cessation_effective_date' => optional($foreignProfile?->cessation_effective_date)->toDateString(),
                'cessation_notification_due_at' => optional($foreignProfile?->cessation_notification_due_at)->toDateString(),
                'cessation_notified_at' => optional($foreignProfile?->cessation_notified_at)->toDateString(),
                'probation_category' => $probationProfile?->probation_category ?? '',
                'probation_starts_at' => optional($probationProfile?->starts_at)->toDateString(),
                'probation_expected_end_at' => optional($probationProfile?->expected_end_at)->toDateString(),
                'probation_evaluation_status' => $probationProfile?->evaluation_status ?? '',
                'probation_decision_status' => $probationProfile?->decision_status ?? '',
                'probation_decision_date' => optional($probationProfile?->decision_date)->toDateString(),
                'contract_number' => $contract['contract_number'] ?? '',
                'contract_status' => $contract['status'] ?? '',
                'contract_start_date' => $contract['start_date'] ?? '',
                'contract_end_date' => $contract['end_date'] ?? '',
                'contract_legal_type' => $contract['legal_contract_type'] ?? '',
                'contract_fixed_term_justification' => $contract['fixed_term_justification'] ?? '',
                'contract_presumed_indefinite_risk' => (bool) ($contract['presumed_indefinite_risk'] ?? false),
                'approved_leave_days_current_year' => (float) ($leaveStats['approved_days_current_year'] ?? 0),
                'approved_leave_days_total' => (float) ($leaveStats['approved_days_total'] ?? 0),
                'latest_pay_date' => $latestPayroll['latest_pay_date'] ?? null,
                'latest_gross_pay' => (float) ($latestPayroll['latest_gross_pay'] ?? 0),
                'latest_net_pay' => (float) ($latestPayroll['latest_net_pay'] ?? 0),
                'latest_irps_amount' => (float) ($latestPayroll['latest_irps_amount'] ?? 0),
                'latest_inss_employee_amount' => (float) ($latestPayroll['latest_inss_employee_amount'] ?? 0),
                'latest_inss_employer_amount' => (float) ($latestPayroll['latest_inss_employer_amount'] ?? 0),
            ];
        })->values();

        return [
            'reference_date' => $asOfDate->toDateString(),
            'summary' => [
                'workers_total' => $rows->count(),
                'workers_with_inss' => $rows->where('inss_number', '!=', '')->count(),
                'workers_without_inss' => $rows->where('inss_number', '')->count(),
                'foreign_workers' => $rows->where('is_foreign_worker', true)->count(),
                'dependents_total' => (int) $rows->sum('dependents_total'),
                'tax_eligible_dependents_total' => (int) $rows->sum('dependents_tax_eligible'),
                'workers_with_active_labour_contract' => $rows->filter(function (array $row) use ($asOfDate): bool {
                    $startDate = $row['contract_start_date'] ? Carbon::parse($row['contract_start_date'])->startOfDay() : null;
                    $endDate = $row['contract_end_date'] ? Carbon::parse($row['contract_end_date'])->endOfDay() : null;

                    if (!$startDate) {
                        return false;
                    }

                    if ($startDate->greaterThan($asOfDate)) {
                        return false;
                    }

                    return !$endDate || !$endDate->lessThan($asOfDate);
                })->count(),
                'contract_risk_indefinite_presumption' => $rows->where('contract_presumed_indefinite_risk', true)->count(),
            ],
            'rows' => $rows->all(),
        ];
    }

    public function importCsv(int $companyId, string $filePath, ?int $actorUserId = null): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [
                'processed' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [['line' => 0, 'message' => __('Unable to open CSV file.')]],
            ];
        }

        $header = fgetcsv($handle);
        if (!$header || !is_array($header)) {
            fclose($handle);

            return [
                'processed' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [['line' => 0, 'message' => __('CSV header is missing or invalid.')]],
            ];
        }

        $normalizedHeader = $this->normalizeHeader($header);
        $line = 1;
        $processed = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            $line++;

            if (!is_array($row) || $this->isCsvRowEmpty($row)) {
                continue;
            }

            $processed++;
            $data = $this->mapRowToAssociative($normalizedHeader, $row);
            $employee = $this->findEmployeeForImport($companyId, $data);

            if (!$employee) {
                $skipped++;
                $errors[] = ['line' => $line, 'message' => __('Employee not found for supplied identifiers.')];
                continue;
            }

            try {
                $this->applyEmployeeCoreUpdates($employee, $data);
                $this->applySocialSecurityProfileUpdates($employee, $data, $companyId, $actorUserId);
                $this->applyForeignWorkerProfileUpdates($employee, $data, $companyId, $actorUserId);
                $this->applyProbationProfileUpdates($employee, $data, $companyId, $actorUserId);
                $updated++;
            } catch (\Throwable $exception) {
                $skipped++;
                $errors[] = ['line' => $line, 'message' => $exception->getMessage()];
            }
        }

        fclose($handle);

        return [
            'processed' => $processed,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => array_slice($errors, 0, 50),
        ];
    }

    private function resolveReferenceDate(?string $referenceDate): Carbon
    {
        if ($referenceDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $referenceDate)) {
            return Carbon::parse($referenceDate)->startOfDay();
        }

        return now()->startOfDay();
    }

    private function resolveApprovedLeaveStatsByUser(int $companyId, Collection $employeeUserIds, Carbon $referenceDate): Collection
    {
        if ($employeeUserIds->isEmpty() || !Schema::hasTable('leave_applications')) {
            return collect();
        }

        $approvedLeaves = LeaveApplication::query()
            ->where('created_by', $companyId)
            ->where('status', 'approved')
            ->whereIn('employee_id', $employeeUserIds->all())
            ->get([
                'employee_id',
                'start_date',
                'total_days',
            ]);

        return $approvedLeaves
            ->groupBy(static fn (LeaveApplication $leave): int => (int) $leave->employee_id)
            ->map(function (Collection $rows) use ($referenceDate): array {
                $totalDays = round((float) $rows->sum(fn (LeaveApplication $leave): float => (float) ($leave->total_days ?? 0)), 2);
                $currentYearDays = round((float) $rows
                    ->filter(function (LeaveApplication $leave) use ($referenceDate): bool {
                        $startDate = $leave->start_date;

                        return $startDate
                            && (int) $startDate->year === (int) $referenceDate->year;
                    })
                    ->sum(fn (LeaveApplication $leave): float => (float) ($leave->total_days ?? 0)), 2);

                return [
                    'approved_days_current_year' => $currentYearDays,
                    'approved_days_total' => $totalDays,
                ];
            });
    }

    private function resolveLatestPayrollByUser(int $companyId, Collection $employeeUserIds, Carbon $referenceDate): Collection
    {
        if ($employeeUserIds->isEmpty() || !Schema::hasTable('payroll_entries') || !Schema::hasTable('payrolls')) {
            return collect();
        }

        $entries = PayrollEntry::query()
            ->join('payrolls', 'payrolls.id', '=', 'payroll_entries.payroll_id')
            ->where('payroll_entries.created_by', $companyId)
            ->where('payrolls.created_by', $companyId)
            ->where('payrolls.status', 'completed')
            ->whereDate('payrolls.pay_date', '<=', $referenceDate->toDateString())
            ->whereIn('payroll_entries.employee_id', $employeeUserIds->all())
            ->where(function ($query): void {
                $query->whereNull('payroll_entries.is_cancelled')->orWhere('payroll_entries.is_cancelled', false);
            })
            ->orderByDesc('payrolls.pay_date')
            ->orderByDesc('payroll_entries.id')
            ->get([
                'payroll_entries.employee_id',
                'payroll_entries.gross_pay',
                'payroll_entries.net_pay',
                'payroll_entries.irps_amount',
                'payroll_entries.inss_employee_amount',
                'payroll_entries.inss_employer_amount',
                'payrolls.pay_date',
            ]);

        return $entries
            ->groupBy(static fn ($entry): int => (int) $entry->employee_id)
            ->map(function (Collection $items): array {
                $latest = $items->first();

                return [
                    'latest_pay_date' => optional($latest->pay_date)->format('Y-m-d'),
                    'latest_gross_pay' => (float) ($latest->gross_pay ?? 0),
                    'latest_net_pay' => (float) ($latest->net_pay ?? 0),
                    'latest_irps_amount' => (float) ($latest->irps_amount ?? 0),
                    'latest_inss_employee_amount' => (float) ($latest->inss_employee_amount ?? 0),
                    'latest_inss_employer_amount' => (float) ($latest->inss_employer_amount ?? 0),
                ];
            });
    }

    private function resolveLabourContractsByUser(int $companyId, Collection $employeeUserIds, Carbon $referenceDate): Collection
    {
        if ($employeeUserIds->isEmpty() || !Schema::hasTable('contracts')) {
            return collect();
        }

        $query = Contract::query()
            ->where('created_by', $companyId)
            ->whereIn('user_id', $employeeUserIds->all())
            ->where('source_type', 'contract')
            ->orderByDesc('start_date')
            ->orderByDesc('id');

        if (Schema::hasColumn('contracts', 'is_labour_contract')) {
            $query->where('is_labour_contract', true);
        }

        $contracts = $query->get([
            'id',
            'user_id',
            'contract_number',
            'status',
            'start_date',
            'end_date',
            'legal_contract_type',
            'fixed_term_justification',
            'is_labour_contract',
        ]);

        return $contracts
            ->groupBy(static fn (Contract $contract): int => (int) $contract->user_id)
            ->map(function (Collection $items) use ($referenceDate): array {
                $current = $items->first(function (Contract $contract) use ($referenceDate): bool {
                    $startDate = $contract->start_date ? Carbon::parse($contract->start_date)->startOfDay() : null;
                    $endDate = $contract->end_date ? Carbon::parse($contract->end_date)->endOfDay() : null;

                    if (!$startDate || $startDate->greaterThan($referenceDate)) {
                        return false;
                    }

                    return !$endDate || !$endDate->lessThan($referenceDate);
                });

                $contract = $current ?? $items->first();
                if (!$contract) {
                    return [];
                }

                return [
                    'contract_number' => $contract->contract_number,
                    'status' => (string) $contract->status,
                    'start_date' => optional($contract->start_date)->format('Y-m-d'),
                    'end_date' => optional($contract->end_date)->format('Y-m-d'),
                    'legal_contract_type' => (string) ($contract->legal_contract_type ?? ''),
                    'fixed_term_justification' => (string) ($contract->fixed_term_justification ?? ''),
                    'presumed_indefinite_risk' => (bool) ($contract->presumed_indefinite_risk ?? false),
                ];
            });
    }

    private function normalizeHeader(array $header): array
    {
        return collect($header)
            ->map(function (mixed $value, int $index): string {
                $text = trim((string) $value);
                if ($index === 0) {
                    $text = preg_replace('/^\xEF\xBB\xBF/u', '', $text) ?? $text;
                }

                return $this->normalizeHeaderKey($text);
            })
            ->values()
            ->all();
    }

    private function normalizeHeaderKey(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = str_replace(['(', ')', '/', '-', '.'], ' ', $normalized);
        $normalized = preg_replace('/\s+/', '_', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^a-z0-9_]/', '', $normalized) ?? $normalized;

        return trim($normalized, '_');
    }

    private function mapRowToAssociative(array $header, array $row): array
    {
        $result = [];
        foreach ($header as $index => $key) {
            if ($key === '') {
                continue;
            }
            $result[$key] = trim((string) ($row[$index] ?? ''));
        }

        return $result;
    }

    private function isCsvRowEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function findEmployeeForImport(int $companyId, array $data): ?Employee
    {
        $employeeRecordId = (int) ($data['employee_record_id'] ?? 0);
        if ($employeeRecordId > 0) {
            return Employee::query()
                ->where('created_by', $companyId)
                ->where('id', $employeeRecordId)
                ->first();
        }

        $employeeInternalId = trim((string) ($data['employee_internal_id'] ?? ''));
        if ($employeeInternalId !== '') {
            $employee = Employee::query()
                ->where('created_by', $companyId)
                ->where('employee_id', $employeeInternalId)
                ->first();

            if ($employee) {
                return $employee;
            }
        }

        $employeeNuit = trim((string) ($data['employee_nuit'] ?? ''));
        if ($employeeNuit !== '') {
            return Employee::query()
                ->where('created_by', $companyId)
                ->where('tax_payer_id', $employeeNuit)
                ->first();
        }

        return null;
    }

    private function applyEmployeeCoreUpdates(Employee $employee, array $data): void
    {
        $updates = [];

        if (!empty($data['employment_type'])) {
            $updates['employment_type'] = $data['employment_type'];
        }

        if (!empty($data['employee_nuit'])) {
            $updates['tax_payer_id'] = $data['employee_nuit'];
        }

        if (array_key_exists('basic_salary', $data) && $data['basic_salary'] !== '') {
            $updates['basic_salary'] = (float) $data['basic_salary'];
        }

        if (!empty($updates)) {
            $employee->update($updates);
        }
    }

    private function applySocialSecurityProfileUpdates(Employee $employee, array $data, int $companyId, ?int $actorUserId): void
    {
        $inssNumber = trim((string) ($data['inss_number'] ?? ''));
        $registrationStatus = trim((string) ($data['inss_status'] ?? ''));
        $registrationDate = $this->toDateOrNull($data['inss_registration_date'] ?? null);

        if ($inssNumber === '' && $registrationStatus === '' && $registrationDate === null) {
            return;
        }

        $profile = EmployeeSocialSecurityProfile::query()->firstOrNew([
            'employee_id' => $employee->id,
        ]);

        $profile->fill([
            'inss_number' => $inssNumber !== '' ? $inssNumber : $profile->inss_number,
            'registration_status' => $registrationStatus !== '' ? $registrationStatus : ($profile->registration_status ?? 'pending'),
            'registration_date' => $registrationDate,
            'creator_id' => $actorUserId ?: $profile->creator_id ?: $employee->creator_id,
            'created_by' => $companyId,
        ]);
        $profile->save();
    }

    private function applyForeignWorkerProfileUpdates(Employee $employee, array $data, int $companyId, ?int $actorUserId): void
    {
        $foreignFields = [
            'is_foreign_worker',
            'nationality',
            'residency_status',
            'hiring_regime',
            'work_province',
            'passport_number',
            'passport_expires_at',
            'visa_type',
            'visa_expires_at',
            'work_authorization_number',
            'work_authorization_expires_at',
            'mozambique_entry_date',
            'cessation_effective_date',
            'cessation_notification_due_date',
            'cessation_notified_at',
        ];

        $hasPayload = collect($foreignFields)->contains(function (string $field) use ($data): bool {
            return array_key_exists($field, $data) && trim((string) $data[$field]) !== '';
        });

        if (!$hasPayload) {
            return;
        }

        $profile = EmployeeForeignWorkerProfile::query()->firstOrNew([
            'employee_id' => $employee->id,
        ]);

        $isForeignWorker = $this->toBoolOrNull($data['is_foreign_worker'] ?? null);
        $profile->fill([
            'is_foreign_worker' => $isForeignWorker ?? (bool) ($profile->is_foreign_worker ?? false),
            'nationality' => $this->stringOrNull($data['nationality'] ?? null),
            'residency_status' => $this->stringOrNull($data['residency_status'] ?? null) ?: ($profile->residency_status ?? 'resident'),
            'hiring_regime' => $this->stringOrNull($data['hiring_regime'] ?? null),
            'work_province' => $this->stringOrNull($data['work_province'] ?? null),
            'passport_number' => $this->stringOrNull($data['passport_number'] ?? null),
            'passport_expires_at' => $this->toDateOrNull($data['passport_expires_at'] ?? null),
            'visa_type' => $this->stringOrNull($data['visa_type'] ?? null),
            'visa_expires_at' => $this->toDateOrNull($data['visa_expires_at'] ?? null),
            'work_authorization_number' => $this->stringOrNull($data['work_authorization_number'] ?? null),
            'work_authorization_expires_at' => $this->toDateOrNull($data['work_authorization_expires_at'] ?? null),
            'mozambique_entry_date' => $this->toDateOrNull($data['mozambique_entry_date'] ?? null),
            'cessation_effective_date' => $this->toDateOrNull($data['cessation_effective_date'] ?? null),
            'cessation_notification_due_at' => $this->toDateOrNull($data['cessation_notification_due_date'] ?? null),
            'cessation_notified_at' => $this->toDateOrNull($data['cessation_notified_at'] ?? null),
            'creator_id' => $actorUserId ?: $profile->creator_id ?: $employee->creator_id,
            'created_by' => $companyId,
        ]);
        $profile->save();
    }

    private function applyProbationProfileUpdates(Employee $employee, array $data, int $companyId, ?int $actorUserId): void
    {
        $probationFields = [
            'probation_category',
            'probation_starts_at',
            'probation_expected_end',
            'probation_evaluation',
            'probation_decision',
            'probation_decision_date',
        ];

        $hasPayload = collect($probationFields)->contains(function (string $field) use ($data): bool {
            return array_key_exists($field, $data) && trim((string) $data[$field]) !== '';
        });

        if (!$hasPayload) {
            return;
        }

        $category = $this->stringOrNull($data['probation_category'] ?? null) ?: 'general';
        $startsAt = $this->toDateOrNull($data['probation_starts_at'] ?? null);
        $expectedEndAt = $this->toDateOrNull($data['probation_expected_end'] ?? null);

        if (!$startsAt) {
            return;
        }

        if (!$expectedEndAt) {
            $expectedEndAt = $this->probationPolicyService->calculateExpectedEndDate($startsAt, $category, $companyId);
        }

        $profile = EmployeeProbationProfile::query()->firstOrNew([
            'employee_id' => $employee->id,
        ]);

        $profile->fill([
            'probation_category' => $category,
            'starts_at' => $startsAt,
            'expected_end_at' => $expectedEndAt,
            'legal_max_days' => $this->probationPolicyService->legalMaxDaysFor($category, $companyId),
            'evaluation_status' => $this->stringOrNull($data['probation_evaluation'] ?? null) ?: ($profile->evaluation_status ?? 'pending'),
            'decision_status' => $this->stringOrNull($data['probation_decision'] ?? null) ?: ($profile->decision_status ?? 'ongoing'),
            'decision_date' => $this->toDateOrNull($data['probation_decision_date'] ?? null),
            'creator_id' => $actorUserId ?: $profile->creator_id ?: $employee->creator_id,
            'created_by' => $companyId,
        ]);
        $profile->save();
    }

    private function toDateOrNull(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function toBoolOrNull(?string $value): ?bool
    {
        $value = strtolower(trim((string) $value));
        if ($value === '') {
            return null;
        }

        if (in_array($value, ['1', 'true', 'yes', 'sim'], true)) {
            return true;
        }

        if (in_array($value, ['0', 'false', 'no', 'nao', 'não'], true)) {
            return false;
        }

        return null;
    }

    private function stringOrNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
