<?php

namespace App\Services;

use Carbon\Carbon;
use Workdo\Hrm\Models\Complaint;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Warning;

class MozambiqueHrDisciplinaryReportService
{
    private const COMPLAINT_PENDING_STATUSES = ['pending', 'in review', 'assigned', 'in progress'];

    public function buildDataset(int $companyId, ?string $referencePeriod = null): array
    {
        [$period, $periodStart, $periodEnd] = $this->resolvePeriod($referencePeriod);
        $today = Carbon::today();
        $employeeNuitLookup = $this->employeeNuitLookup($companyId);

        $warnings = Warning::query()
            ->with(['employee:id,name', 'warningBy:id,name', 'warningType:id,warning_type_name'])
            ->where('created_by', $companyId)
            ->active()
            ->where(function ($query) use ($periodStart, $periodEnd): void {
                $query
                    ->whereBetween('warning_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                    ->orWhere(function ($fallbackQuery) use ($periodStart, $periodEnd): void {
                        $fallbackQuery
                            ->whereNull('warning_date')
                            ->whereDate('created_at', '>=', $periodStart->toDateString())
                            ->whereDate('created_at', '<=', $periodEnd->toDateString());
                    });
            })
            ->orderBy('warning_date')
            ->orderBy('id')
            ->get();

        $complaints = Complaint::query()
            ->with([
                'employee:id,name',
                'againstEmployee:id,name',
                'complaintType:id,complaint_type',
                'handlingOwner:id,name',
                'resolvedBy:id,name',
            ])
            ->where('created_by', $companyId)
            ->active()
            ->whereDate('complaint_date', '>=', $periodStart->toDateString())
            ->whereDate('complaint_date', '<=', $periodEnd->toDateString())
            ->orderBy('complaint_date')
            ->orderBy('id')
            ->get();

        $warningRows = $warnings->map(function (Warning $warning) use ($period, $today, $employeeNuitLookup): array {
            $responseDeadlineOverdue = $warning->response_deadline_at !== null
                && Carbon::parse($warning->response_deadline_at)->lt($today)
                && $warning->status === 'pending';

            $decisionDeadlineOverdue = $warning->decision_deadline_at !== null
                && Carbon::parse($warning->decision_deadline_at)->lt($today)
                && blank($warning->disciplinary_sanction);

            return [
                'reference_period' => $period,
                'case_type' => 'disciplinary_warning',
                'case_id' => (int) $warning->id,
                'case_reference' => sprintf('WARN-%06d', (int) $warning->id),
                'case_opened_at' => optional($warning->warning_date ?? $warning->created_at)->format('Y-m-d'),
                'employee_name' => (string) ($warning->employee?->name ?? '-'),
                'employee_nuit' => (string) ($employeeNuitLookup[(int) ($warning->employee_id ?? 0)] ?? ''),
                'against_employee_name' => '',
                'category_name' => (string) ($warning->warningType?->warning_type_name ?? ''),
                'severity' => (string) ($warning->severity ?? ''),
                'status' => (string) ($warning->status ?? ''),
                'is_harassment_case' => false,
                'is_confidential' => false,
                'confidentiality_level' => '',
                'assigned_owner' => '',
                'warning_issued_by' => (string) ($warning->warningBy?->name ?? ''),
                'disciplinary_sanction' => (string) ($warning->disciplinary_sanction ?? ''),
                'response_deadline_at' => optional($warning->response_deadline_at)->format('Y-m-d'),
                'decision_deadline_at' => optional($warning->decision_deadline_at)->format('Y-m-d'),
                'decision_or_resolution_date' => optional($warning->disciplinary_decision_at)->format('Y-m-d'),
                'response_deadline_overdue' => $responseDeadlineOverdue,
                'decision_deadline_overdue' => $decisionDeadlineOverdue,
                'subject' => (string) ($warning->subject ?? ''),
                'description' => (string) ($warning->description ?? ''),
            ];
        });

        $complaintRows = $complaints->map(function (Complaint $complaint) use ($period, $employeeNuitLookup): array {
            $isConfidential = (bool) ($complaint->is_confidential ?? false);

            return [
                'reference_period' => $period,
                'case_type' => 'complaint',
                'case_id' => (int) $complaint->id,
                'case_reference' => sprintf('COMP-%06d', (int) $complaint->id),
                'case_opened_at' => optional($complaint->complaint_date ?? $complaint->created_at)->format('Y-m-d'),
                'employee_name' => (string) ($complaint->employee?->name ?? '-'),
                'employee_nuit' => (string) ($employeeNuitLookup[(int) ($complaint->employee_id ?? 0)] ?? ''),
                'against_employee_name' => (string) ($complaint->againstEmployee?->name ?? ''),
                'category_name' => (string) ($complaint->complaintType?->complaint_type ?? ''),
                'severity' => '',
                'status' => (string) ($complaint->status ?? ''),
                'is_harassment_case' => (bool) ($complaint->is_harassment_report ?? false),
                'is_confidential' => $isConfidential,
                'confidentiality_level' => (string) ($complaint->confidentiality_level ?? ''),
                'assigned_owner' => (string) ($complaint->handlingOwner?->name ?? ''),
                'warning_issued_by' => '',
                'disciplinary_sanction' => '',
                'response_deadline_at' => '',
                'decision_deadline_at' => '',
                'decision_or_resolution_date' => optional($complaint->resolution_date)->format('Y-m-d'),
                'response_deadline_overdue' => false,
                'decision_deadline_overdue' => false,
                'subject' => (string) ($complaint->subject ?? ''),
                'description' => (string) ($complaint->description ?? ''),
            ];
        });

        $rows = $warningRows
            ->concat($complaintRows)
            ->sortBy([
                ['case_opened_at', 'asc'],
                ['case_type', 'asc'],
                ['case_id', 'asc'],
            ])
            ->values();

        $harassmentCases = $complaints->where('is_harassment_report', true);

        $workersWithCases = $warnings
            ->pluck('employee_id')
            ->concat($complaints->pluck('employee_id'))
            ->filter()
            ->unique()
            ->count();

        return [
            'reference_period' => $period,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'cases_total' => $rows->count(),
                'disciplinary_cases_total' => $warnings->count(),
                'disciplinary_cases_pending' => $warnings->where('status', 'pending')->count(),
                'disciplinary_cases_concluded' => $warnings->filter(fn (Warning $warning): bool => !blank($warning->disciplinary_sanction))->count(),
                'sanctions_applied' => $warnings->filter(fn (Warning $warning): bool => !blank($warning->disciplinary_sanction))->count(),
                'harassment_cases_total' => $harassmentCases->count(),
                'harassment_cases_pending' => $harassmentCases->whereIn('status', self::COMPLAINT_PENDING_STATUSES)->count(),
                'harassment_cases_without_owner' => $harassmentCases
                    ->whereIn('status', self::COMPLAINT_PENDING_STATUSES)
                    ->filter(fn (Complaint $complaint): bool => blank($complaint->handling_owner_id))
                    ->count(),
                'deadlines_overdue' => $rows->filter(
                    fn (array $row): bool => (bool) ($row['response_deadline_overdue'] ?? false) || (bool) ($row['decision_deadline_overdue'] ?? false)
                )->count(),
                'workers_with_cases' => $workersWithCases,
            ],
            'rows' => $rows->all(),
        ];
    }

    private function resolvePeriod(?string $referencePeriod): array
    {
        $period = $referencePeriod;
        if (!is_string($period) || !preg_match('/^\d{4}-\d{2}$/', $period)) {
            $period = now()->format('Y-m');
        }

        $periodStart = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        return [$period, $periodStart, $periodEnd];
    }

    private function employeeNuitLookup(int $companyId): array
    {
        return Employee::query()
            ->where('created_by', $companyId)
            ->whereNotNull('user_id')
            ->pluck('tax_payer_id', 'user_id')
            ->map(static fn ($nuit): string => (string) ($nuit ?? ''))
            ->all();
    }
}
