<?php

namespace App\Services;

use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\EmployeeForeignWorkerProfile;

class MozambiqueForeignWorkerQuotaService
{
    public function evaluate(int $companyId, ?int $excludeEmployeeId = null): array
    {
        $totalWorkers = Employee::query()
            ->where('created_by', $companyId)
            ->count();

        $classification = $this->classificationFor($totalWorkers);
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

    private function classificationFor(int $totalWorkers): array
    {
        if ($totalWorkers <= 10) {
            return ['employer_type' => 'micro', 'max_percentage' => 15.0];
        }

        if ($totalWorkers <= 30) {
            return ['employer_type' => 'small', 'max_percentage' => 10.0];
        }

        if ($totalWorkers <= 100) {
            return ['employer_type' => 'medium', 'max_percentage' => 8.0];
        }

        return ['employer_type' => 'large', 'max_percentage' => 5.0];
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
