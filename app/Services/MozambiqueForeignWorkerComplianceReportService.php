<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Workdo\Contract\Models\Contract;
use Workdo\Hrm\Models\EmployeeForeignWorkerProfile;

class MozambiqueForeignWorkerComplianceReportService
{
    public function __construct(
        private readonly MozambiqueForeignWorkerQuotaService $foreignWorkerQuotaService
    ) {}

    public function buildDataset(int $companyId, ?string $referenceDate = null, int $windowDays = 30): array
    {
        $reportDate = $this->resolveReportDate($referenceDate);
        $windowDays = $this->normalizeWindowDays($windowDays);
        $windowEnd = $reportDate->copy()->addDays($windowDays);

        $profiles = EmployeeForeignWorkerProfile::query()
            ->where('created_by', $companyId)
            ->where('is_foreign_worker', true)
            ->with([
                'employee:id,employee_id,user_id,tax_payer_id',
                'employee.user:id,name',
            ])
            ->orderBy('employee_id')
            ->get();

        $userIds = $profiles
            ->pluck('employee.user_id')
            ->filter()
            ->map(static fn ($userId): int => (int) $userId)
            ->unique()
            ->values()
            ->all();

        $contractsByUserId = $this->resolveContractsByUser($companyId, $userIds);

        $rows = $profiles->map(function (EmployeeForeignWorkerProfile $profile) use ($contractsByUserId, $reportDate, $windowEnd): array {
            $employee = $profile->employee;
            $contract = $contractsByUserId->get((int) ($employee?->user_id ?? 0));

            $passportStatus = $this->resolveExpiringStatus($profile->passport_expires_at, $reportDate, $windowEnd);
            $visaStatus = $this->resolveExpiringStatus($profile->visa_expires_at, $reportDate, $windowEnd);
            $workAuthorizationStatus = $this->resolveExpiringStatus($profile->work_authorization_expires_at, $reportDate, $windowEnd);

            $contractEndDate = $contract?->end_date ? Carbon::parse((string) $contract->end_date)->startOfDay() : null;
            $contractStatus = $this->resolveContractStatus($contractEndDate, $reportDate, $windowEnd, $contract !== null);
            $contractExpiringInWindow = $contractStatus === 'expiring_soon';

            $cessationEffectiveDate = $profile->cessation_effective_date
                ? Carbon::parse((string) $profile->cessation_effective_date)->startOfDay()
                : null;

            $cessationNotificationDueDate = $profile->cessation_notification_due_at
                ? Carbon::parse((string) $profile->cessation_notification_due_at)->startOfDay()
                : ($cessationEffectiveDate?->copy()->addDays(5));

            $cessationNotifiedAt = $profile->cessation_notified_at
                ? Carbon::parse((string) $profile->cessation_notified_at)->startOfDay()
                : null;

            $migrationNotificationStatus = $this->resolveMigrationStatus(
                $cessationEffectiveDate,
                $cessationNotificationDueDate,
                $cessationNotifiedAt,
                $reportDate
            );

            return [
                'employee_name' => $employee?->user?->name ?? '-',
                'employee_internal_id' => $employee?->employee_id ?? '',
                'employee_nuit' => $employee?->tax_payer_id ?? '',
                'nationality' => (string) ($profile->nationality ?? ''),
                'residency_status' => (string) ($profile->residency_status ?? ''),
                'hiring_regime' => (string) ($profile->hiring_regime ?? ''),
                'work_province' => (string) ($profile->work_province ?? ''),
                'passport_number' => (string) ($profile->passport_number ?? ''),
                'passport_expires_at' => $profile->passport_expires_at?->toDateString(),
                'passport_status' => $passportStatus,
                'visa_type' => (string) ($profile->visa_type ?? ''),
                'visa_expires_at' => $profile->visa_expires_at?->toDateString(),
                'visa_status' => $visaStatus,
                'work_authorization_number' => (string) ($profile->work_authorization_number ?? ''),
                'work_authorization_expires_at' => $profile->work_authorization_expires_at?->toDateString(),
                'work_authorization_status' => $workAuthorizationStatus,
                'contract_number' => (string) ($contract?->contract_number ?? ''),
                'contract_end_date' => $contractEndDate?->toDateString(),
                'contract_status' => $contractStatus,
                'contract_expiring_in_window' => $contractExpiringInWindow,
                'cessation_effective_date' => $cessationEffectiveDate?->toDateString(),
                'cessation_notification_due_at' => $cessationNotificationDueDate?->toDateString(),
                'cessation_notified_at' => $cessationNotifiedAt?->toDateString(),
                'migration_notification_status' => $migrationNotificationStatus,
            ];
        })->values();

        $quota = $this->foreignWorkerQuotaService->evaluate($companyId);

        return [
            'report_date' => $reportDate->toDateString(),
            'window_days' => $windowDays,
            'quota' => $quota,
            'summary' => [
                'total_foreign_workers' => $rows->count(),
                'work_authorizations_expiring' => $rows->where('work_authorization_status', 'expiring_soon')->count(),
                'work_authorizations_expired' => $rows->where('work_authorization_status', 'expired')->count(),
                'visas_expiring' => $rows->where('visa_status', 'expiring_soon')->count(),
                'visas_expired' => $rows->where('visa_status', 'expired')->count(),
                'contracts_expiring' => $rows->where('contract_expiring_in_window', true)->count(),
                'contracts_expired' => $rows->where('contract_status', 'expired')->count(),
                'migration_notifications_pending' => $rows
                    ->whereIn('migration_notification_status', ['pending', 'overdue'])
                    ->count(),
                'migration_notifications_overdue' => $rows
                    ->where('migration_notification_status', 'overdue')
                    ->count(),
            ],
            'rows' => $rows->all(),
        ];
    }

    private function resolveContractsByUser(int $companyId, array $userIds): Collection
    {
        if (empty($userIds)) {
            return collect();
        }

        $contracts = Contract::query()
            ->where('created_by', $companyId)
            ->where('source_type', 'contract')
            ->where('is_labour_contract', true)
            ->whereIn('user_id', $userIds)
            ->get([
                'id',
                'user_id',
                'contract_number',
                'start_date',
                'end_date',
                'status',
            ]);

        return $contracts
            ->groupBy('user_id')
            ->map(static function (Collection $group): ?Contract {
                return $group->sort(function (Contract $left, Contract $right): int {
                    $leftScore = $left->end_date
                        ? Carbon::parse((string) $left->end_date)->startOfDay()->timestamp
                        : -1;
                    $rightScore = $right->end_date
                        ? Carbon::parse((string) $right->end_date)->startOfDay()->timestamp
                        : -1;

                    if ($leftScore === $rightScore) {
                        return (int) $right->id <=> (int) $left->id;
                    }

                    return $rightScore <=> $leftScore;
                })->first();
            });
    }

    private function resolveExpiringStatus(mixed $dateValue, Carbon $reportDate, Carbon $windowEnd): string
    {
        if ($dateValue === null || $dateValue === '') {
            return 'missing';
        }

        $date = Carbon::parse((string) $dateValue)->startOfDay();

        if ($date->lt($reportDate)) {
            return 'expired';
        }

        if ($date->lte($windowEnd)) {
            return 'expiring_soon';
        }

        return 'valid';
    }

    private function resolveContractStatus(?Carbon $contractEndDate, Carbon $reportDate, Carbon $windowEnd, bool $hasContract): string
    {
        if (!$hasContract) {
            return 'missing';
        }

        if ($contractEndDate === null) {
            return 'open_ended';
        }

        if ($contractEndDate->lt($reportDate)) {
            return 'expired';
        }

        if ($contractEndDate->lte($windowEnd)) {
            return 'expiring_soon';
        }

        return 'active';
    }

    private function resolveMigrationStatus(
        ?Carbon $cessationEffectiveDate,
        ?Carbon $notificationDueDate,
        ?Carbon $notifiedAt,
        Carbon $reportDate
    ): string {
        if ($notifiedAt !== null) {
            return 'notified';
        }

        if ($cessationEffectiveDate === null && $notificationDueDate === null) {
            return 'not_required';
        }

        if ($notificationDueDate === null) {
            return 'pending';
        }

        if ($notificationDueDate->lt($reportDate)) {
            return 'overdue';
        }

        return 'pending';
    }

    private function resolveReportDate(?string $referenceDate): Carbon
    {
        if ($referenceDate !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $referenceDate) === 1) {
            return Carbon::createFromFormat('Y-m-d', $referenceDate)->startOfDay();
        }

        return Carbon::today();
    }

    private function normalizeWindowDays(int $windowDays): int
    {
        return max(1, min($windowDays, 180));
    }
}
