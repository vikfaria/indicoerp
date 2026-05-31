<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Workdo\Hrm\Models\Employee;
use Workdo\Training\Models\TrainingTask;
use Workdo\Training\Models\TrainingType;

class MozambiqueHrTrainingComplianceReportService
{
    public function buildDataset(int $companyId, ?string $referenceDate = null, int $windowDays = 30): array
    {
        $reportDate = $this->resolveReferenceDate($referenceDate);
        $windowDays = max(1, min(365, $windowDays));
        $windowEnd = $reportDate->copy()->addDays($windowDays)->endOfDay();

        $employees = Employee::query()
            ->where('created_by', $companyId)
            ->whereNotNull('user_id')
            ->with('user:id,name')
            ->orderBy('employee_id')
            ->orderBy('id')
            ->get([
                'id',
                'employee_id',
                'user_id',
                'tax_payer_id',
            ]);

        $employeeByUserId = $employees->keyBy(static fn (Employee $employee): int => (int) $employee->user_id);
        $employeeUserIds = $employeeByUserId->keys()->map(static fn ($id): int => (int) $id)->values();

        $mandatoryTrainingTypes = TrainingType::query()
            ->where('created_by', $companyId)
            ->where('is_mandatory', true)
            ->orderBy('name')
            ->orderBy('id')
            ->get([
                'id',
                'name',
                'compliance_code',
                'certificate_validity_days',
            ]);

        $latestCompletionByWorkerType = $this->resolveLatestCompletionByWorkerType(
            $companyId,
            $employeeUserIds,
            $mandatoryTrainingTypes
        );

        $rows = collect();
        $overdueWorkers = [];
        $expiringWorkers = [];

        foreach ($employeeByUserId as $employeeUserId => $employee) {
            $employeeUserId = (int) $employeeUserId;

            foreach ($mandatoryTrainingTypes as $trainingType) {
                $trainingTypeId = (int) $trainingType->id;
                $key = $employeeUserId . '|' . $trainingTypeId;
                $latestCompletion = $latestCompletionByWorkerType[$key] ?? null;
                $completionDate = $latestCompletion['completion_date'] ?? null;
                $validityDays = max(0, (int) ($trainingType->certificate_validity_days ?? 0));

                $status = 'overdue';
                $statusReason = 'missing_completion';
                $expiryDate = null;
                $daysUntilExpiry = null;

                if ($completionDate instanceof Carbon) {
                    if ($validityDays <= 0) {
                        $status = 'compliant';
                        $statusReason = 'no_expiry_configured';
                    } else {
                        $expiryDate = $completionDate->copy()->addDays($validityDays)->endOfDay();
                        $daysUntilExpiry = (int) $reportDate->copy()->startOfDay()->diffInDays($expiryDate->copy()->startOfDay(), false);

                        if ($expiryDate->lt($reportDate->copy()->startOfDay())) {
                            $status = 'overdue';
                            $statusReason = 'certificate_expired';
                        } elseif ($expiryDate->lte($windowEnd)) {
                            $status = 'expiring_soon';
                            $statusReason = 'certificate_expiring_in_window';
                        } else {
                            $status = 'compliant';
                            $statusReason = 'certificate_valid';
                        }
                    }
                }

                if ($status === 'overdue') {
                    $overdueWorkers[$employeeUserId] = true;
                }

                if ($status === 'expiring_soon') {
                    $expiringWorkers[$employeeUserId] = true;
                }

                $rows->push([
                    'report_date' => $reportDate->toDateString(),
                    'window_days' => $windowDays,
                    'employee_record_id' => (int) $employee->id,
                    'employee_internal_id' => (string) ($employee->employee_id ?? ''),
                    'employee_name' => (string) ($employee->user?->name ?? ('Employee #' . $employee->id)),
                    'employee_nuit' => (string) ($employee->tax_payer_id ?? ''),
                    'training_type_id' => $trainingTypeId,
                    'training_type_name' => (string) ($trainingType->name ?? ''),
                    'training_compliance_code' => (string) ($trainingType->compliance_code ?? ''),
                    'certificate_validity_days' => $validityDays > 0 ? $validityDays : null,
                    'last_training_id' => $latestCompletion['training_id'] ?? null,
                    'last_training_title' => (string) ($latestCompletion['training_title'] ?? ''),
                    'last_completion_date' => $completionDate?->toDateString(),
                    'certificate_expires_at' => $expiryDate?->toDateString(),
                    'days_until_expiry' => $daysUntilExpiry,
                    'compliance_status' => $status,
                    'status_reason' => $statusReason,
                ]);
            }
        }

        $rows = $rows
            ->sortBy([
                ['employee_internal_id', 'asc'],
                ['employee_name', 'asc'],
                ['training_type_name', 'asc'],
                ['training_type_id', 'asc'],
            ])
            ->values();

        return [
            'report_date' => $reportDate->toDateString(),
            'window_days' => $windowDays,
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'workers_evaluated' => $employeeByUserId->count(),
                'mandatory_training_types_total' => $mandatoryTrainingTypes->count(),
                'rows_total' => $rows->count(),
                'rows_overdue' => $rows->where('compliance_status', 'overdue')->count(),
                'rows_expiring_soon' => $rows->where('compliance_status', 'expiring_soon')->count(),
                'rows_compliant' => $rows->where('compliance_status', 'compliant')->count(),
                'workers_with_overdue_mandatory_training' => count($overdueWorkers),
                'workers_with_expiring_mandatory_training' => count($expiringWorkers),
            ],
            'rows' => $rows->all(),
        ];
    }

    private function resolveReferenceDate(?string $referenceDate): Carbon
    {
        if (is_string($referenceDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $referenceDate)) {
            return Carbon::parse($referenceDate)->startOfDay();
        }

        return now()->startOfDay();
    }

    private function resolveLatestCompletionByWorkerType(
        int $companyId,
        Collection $employeeUserIds,
        Collection $mandatoryTrainingTypes
    ): array {
        if ($employeeUserIds->isEmpty() || $mandatoryTrainingTypes->isEmpty()) {
            return [];
        }

        $mandatoryTypeIds = $mandatoryTrainingTypes
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $completedTrainingTasks = TrainingTask::query()
            ->where('created_by', $companyId)
            ->where('status', 'completed')
            ->whereIn('assigned_to', $employeeUserIds->all())
            ->whereHas('training', function ($query) use ($companyId, $mandatoryTypeIds): void {
                $query
                    ->where('created_by', $companyId)
                    ->whereIn('training_type_id', $mandatoryTypeIds->all());
            })
            ->with(['training:id,title,training_type_id,end_date'])
            ->get(['id', 'training_id', 'assigned_to', 'updated_at']);

        $latestCompletionByWorkerType = [];

        foreach ($completedTrainingTasks as $task) {
            $training = $task->training;
            if (!$training || !$task->assigned_to) {
                continue;
            }

            $trainingTypeId = (int) $training->training_type_id;
            $employeeUserId = (int) $task->assigned_to;
            $completionDate = $training->end_date
                ? Carbon::parse((string) $training->end_date)->startOfDay()
                : Carbon::parse((string) ($task->updated_at ?? now()))->startOfDay();

            $key = $employeeUserId . '|' . $trainingTypeId;

            if (
                !isset($latestCompletionByWorkerType[$key])
                || $completionDate->gt($latestCompletionByWorkerType[$key]['completion_date'])
            ) {
                $latestCompletionByWorkerType[$key] = [
                    'training_id' => (int) $training->id,
                    'training_title' => (string) ($training->title ?? ''),
                    'completion_date' => $completionDate,
                ];
            }
        }

        return $latestCompletionByWorkerType;
    }
}

