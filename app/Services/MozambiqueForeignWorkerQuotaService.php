<?php

namespace App\Services;

use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\EmployeeForeignWorkerProfile;

class MozambiqueForeignWorkerQuotaService
{
    public function __construct(
        private readonly MozambiqueHrLegalSettingsService $legalSettingsService
    ) {}

    public function evaluate(int $companyId, ?int $excludeEmployeeId = null): array
    {
        $legalSettings = $this->legalSettingsService->getSettings($companyId);
        $quotaRules = $legalSettings['foreign_quota'] ?? [];

        $totalWorkers = Employee::query()
            ->where('created_by', $companyId)
            ->count();

        $classification = $this->classificationFor($totalWorkers, $quotaRules);
        $quotaSlots = $this->quotaSlotsFor($totalWorkers, $classification['max_percentage']);

        $currentForeignWorkers = EmployeeForeignWorkerProfile::query()
            ->where('created_by', $companyId)
            ->where('is_foreign_worker', true)
            ->when($excludeEmployeeId, fn ($query) => $query->where('employee_id', '!=', $excludeEmployeeId))
            ->count();

        $remainingSlots = max(0, $quotaSlots - $currentForeignWorkers);

        return [
            'employer_type' => $classification['employer_type'],
            'max_percentage' => $classification['max_percentage'],
            'total_workers' => $totalWorkers,
            'quota_slots' => $quotaSlots,
            'current_foreign_workers' => $currentForeignWorkers,
            'remaining_slots' => $remainingSlots,
            'is_exceeded' => $currentForeignWorkers > $quotaSlots,
        ];
    }

    public function canEnableForeignWorker(int $companyId, int $employeeId, bool $alreadyForeignWorker): array
    {
        $evaluation = $this->evaluate($companyId, $alreadyForeignWorker ? $employeeId : null);

        if ($alreadyForeignWorker) {
            return [
                'allowed' => true,
                'evaluation' => $evaluation,
            ];
        }

        $allowed = $evaluation['remaining_slots'] > 0;

        return [
            'allowed' => $allowed,
            'evaluation' => $evaluation,
            'message' => $allowed
                ? null
                : __('Foreign worker quota exceeded for current employer classification (:type).', [
                    'type' => $evaluation['employer_type'],
                ]),
        ];
    }

    private function classificationFor(int $totalWorkers, array $quotaRules): array
    {
        $microMaxWorkers = max(1, (int) ($quotaRules['micro_max_workers'] ?? 10));
        $smallMaxWorkers = max($microMaxWorkers + 1, (int) ($quotaRules['small_max_workers'] ?? 30));
        $mediumMaxWorkers = max($smallMaxWorkers + 1, (int) ($quotaRules['medium_max_workers'] ?? 100));

        if ($totalWorkers <= $microMaxWorkers) {
            return [
                'employer_type' => 'micro',
                'max_percentage' => max(0.0, min(100.0, (float) ($quotaRules['micro_quota_percent'] ?? 15.0))),
            ];
        }

        if ($totalWorkers <= $smallMaxWorkers) {
            return [
                'employer_type' => 'small',
                'max_percentage' => max(0.0, min(100.0, (float) ($quotaRules['small_quota_percent'] ?? 10.0))),
            ];
        }

        if ($totalWorkers <= $mediumMaxWorkers) {
            return [
                'employer_type' => 'medium',
                'max_percentage' => max(0.0, min(100.0, (float) ($quotaRules['medium_quota_percent'] ?? 8.0))),
            ];
        }

        return [
            'employer_type' => 'large',
            'max_percentage' => max(0.0, min(100.0, (float) ($quotaRules['large_quota_percent'] ?? 5.0))),
        ];
    }

    private function quotaSlotsFor(int $totalWorkers, float $maxPercentage): int
    {
        if ($totalWorkers <= 0) {
            return 0;
        }

        // Keep at least one slot when percentage applies to non-zero headcount.
        return max(1, (int) floor(($totalWorkers * $maxPercentage) / 100));
    }
}
