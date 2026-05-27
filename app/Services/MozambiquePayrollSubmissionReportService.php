<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Workdo\Hrm\Models\Payroll;
use Workdo\Hrm\Models\PayrollEntry;

class MozambiquePayrollSubmissionReportService
{
    public function __construct(private readonly MozambiquePayrollTaxService $payrollTaxService)
    {
    }

    public function buildModelo19Dataset(int $companyId, ?string $referencePeriod = null): array
    {
        [$period, $periodStart, $periodEnd] = $this->resolvePeriod($referencePeriod);
        $payrolls = $this->resolveCompletedPayrolls($companyId, $periodStart, $periodEnd);

        $entries = $this->resolvePayrollEntries($companyId, $payrolls->pluck('id'));

        $rows = $entries->map(function (PayrollEntry $entry) use ($period, $companyId): array {
            $employeeName = $entry->employee?->user?->name ?? '-';
            $employeeNuit = $entry->employee?->tax_payer_id ?? '';
            $residencyStatus = strtolower((string) ($entry->employee?->foreignWorkerProfile?->residency_status ?? 'resident'));
            if (!in_array($residencyStatus, ['resident', 'non_resident'], true)) {
                $residencyStatus = 'resident';
            }

            $eligibleDependentsCount = $this->countEligibleDependents($entry);
            $irpsBreakdown = $this->payrollTaxService->calculateIrps(
                (float) ($entry->taxable_income ?? 0),
                $companyId,
                optional($entry->payroll?->pay_date)->toDateString(),
                [
                    'residency_status' => $residencyStatus,
                    'eligible_dependents_count' => $eligibleDependentsCount,
                ]
            );

            return [
                'reference_period' => $period,
                'payroll_id' => $entry->payroll?->id,
                'payroll_title' => $entry->payroll?->title ?? '-',
                'pay_date' => optional($entry->payroll?->pay_date)->format('Y-m-d'),
                'employee_name' => $employeeName,
                'employee_nuit' => $employeeNuit,
                'residency_status' => $residencyStatus,
                'eligible_dependents_count' => $eligibleDependentsCount,
                'gross_pay' => (float) ($entry->gross_pay ?? 0),
                'taxable_income' => (float) ($entry->taxable_income ?? 0),
                'dependent_deduction_total' => (float) ($irpsBreakdown['dependent_deduction_total'] ?? 0),
                'adjusted_taxable_income' => (float) ($irpsBreakdown['adjusted_taxable_income'] ?? 0),
                'irps_rule' => (string) ($irpsBreakdown['rule'] ?? 'unknown'),
                'irps_rate_percent' => (float) ($irpsBreakdown['rate_percent'] ?? 0),
                'irps_amount' => (float) ($entry->irps_amount ?? 0),
                'net_pay' => (float) ($entry->net_pay ?? 0),
            ];
        })->values();

        return [
            'reference_period' => $period,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'submission_due_date' => $periodStart->copy()->addMonthNoOverflow()->day(20)->toDateString(),
            'summary' => [
                'payroll_runs' => $payrolls->count(),
                'workers' => $rows->pluck('employee_name')->filter()->unique()->count(),
                'eligible_dependents_total' => (int) $rows->sum('eligible_dependents_count'),
                'gross_pay_total' => round((float) $rows->sum('gross_pay'), 2),
                'taxable_income_total' => round((float) $rows->sum('taxable_income'), 2),
                'dependent_deduction_total' => round((float) $rows->sum('dependent_deduction_total'), 2),
                'adjusted_taxable_income_total' => round((float) $rows->sum('adjusted_taxable_income'), 2),
                'irps_total' => round((float) $rows->sum('irps_amount'), 2),
                'net_pay_total' => round((float) $rows->sum('net_pay'), 2),
            ],
            'rows' => $rows->all(),
        ];
    }

    public function buildInssDataset(int $companyId, ?string $referencePeriod = null): array
    {
        [$period, $periodStart, $periodEnd] = $this->resolvePeriod($referencePeriod);
        $payrolls = $this->resolveCompletedPayrolls($companyId, $periodStart, $periodEnd);

        $entries = $this->resolvePayrollEntries($companyId, $payrolls->pluck('id'));

        $rows = $entries->map(function (PayrollEntry $entry) use ($period): array {
            $employeeName = $entry->employee?->user?->name ?? '-';
            $employeeNuit = $entry->employee?->tax_payer_id ?? '';
            $base = (float) ($entry->gross_pay ?? 0);
            $employeeAmount = (float) ($entry->inss_employee_amount ?? 0);
            $employerAmount = (float) ($entry->inss_employer_amount ?? 0);

            return [
                'reference_period' => $period,
                'payroll_id' => $entry->payroll?->id,
                'payroll_title' => $entry->payroll?->title ?? '-',
                'pay_date' => optional($entry->payroll?->pay_date)->format('Y-m-d'),
                'employee_name' => $employeeName,
                'employee_nuit' => $employeeNuit,
                'contributive_base' => $base,
                'employee_rate_percent' => (float) ($entry->inss_employee_rate ?? 0),
                'employee_contribution' => $employeeAmount,
                'employer_rate_percent' => (float) ($entry->inss_employer_rate ?? 0),
                'employer_contribution' => $employerAmount,
                'total_contribution' => round($employeeAmount + $employerAmount, 2),
            ];
        })->values();

        return [
            'reference_period' => $period,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'submission_due_date' => $periodStart->copy()->addMonthNoOverflow()->day(10)->toDateString(),
            'summary' => [
                'payroll_runs' => $payrolls->count(),
                'workers' => $rows->pluck('employee_name')->filter()->unique()->count(),
                'contributive_base_total' => round((float) $rows->sum('contributive_base'), 2),
                'employee_contribution_total' => round((float) $rows->sum('employee_contribution'), 2),
                'employer_contribution_total' => round((float) $rows->sum('employer_contribution'), 2),
                'total_contribution' => round((float) $rows->sum('total_contribution'), 2),
            ],
            'rows' => $rows->all(),
        ];
    }

    public function buildBankPaymentDataset(int $companyId, ?string $referencePeriod = null): array
    {
        [$period, $periodStart, $periodEnd] = $this->resolvePeriod($referencePeriod);
        $payrolls = $this->resolveCompletedPayrolls($companyId, $periodStart, $periodEnd);
        $entries = $this->resolvePayrollEntries($companyId, $payrolls->pluck('id'));

        $rows = $entries->map(function (PayrollEntry $entry) use ($period): array {
            $employee = $entry->employee;

            return [
                'reference_period' => $period,
                'payment_reference' => sprintf('PAY-%s-%06d', str_replace('-', '', $period), (int) $entry->id),
                'payroll_id' => $entry->payroll?->id,
                'payroll_title' => $entry->payroll?->title ?? '-',
                'pay_date' => optional($entry->payroll?->pay_date)->format('Y-m-d'),
                'employee_name' => $employee?->user?->name ?? '-',
                'employee_nuit' => $employee?->tax_payer_id ?? '',
                'account_holder_name' => $employee?->account_holder_name ?? ($employee?->user?->name ?? ''),
                'bank_name' => $employee?->bank_name ?? '',
                'bank_branch' => $employee?->bank_branch ?? '',
                'bank_identifier_code' => $employee?->bank_identifier_code ?? '',
                'account_number' => $employee?->account_number ?? '',
                'currency' => 'MZN',
                'net_pay' => (float) ($entry->net_pay ?? 0),
                'payroll_entry_status' => (string) ($entry->status ?? ''),
            ];
        })->values();

        return [
            'reference_period' => $period,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'summary' => [
                'payroll_runs' => $payrolls->count(),
                'payment_rows' => $rows->count(),
                'workers' => $rows->pluck('employee_name')->filter()->unique()->count(),
                'net_pay_total' => round((float) $rows->sum('net_pay'), 2),
            ],
            'rows' => $rows->all(),
        ];
    }

    private function resolveCompletedPayrolls(int $companyId, Carbon $periodStart, Carbon $periodEnd): Collection
    {
        return Payroll::query()
            ->where('created_by', $companyId)
            ->where('status', 'completed')
            ->where(function ($query) use ($periodStart, $periodEnd): void {
                $query
                    ->where(function ($dateQuery) use ($periodStart, $periodEnd): void {
                        $dateQuery
                            ->whereDate('pay_period_end', '>=', $periodStart->toDateString())
                            ->whereDate('pay_period_end', '<=', $periodEnd->toDateString());
                    })
                    ->orWhere(function ($dateQuery) use ($periodStart, $periodEnd): void {
                        $dateQuery
                            ->whereDate('pay_date', '>=', $periodStart->toDateString())
                            ->whereDate('pay_date', '<=', $periodEnd->toDateString());
                    });
            })
            ->get([
                'id',
                'title',
                'pay_period_end',
                'pay_date',
            ]);
    }

    private function resolvePayrollEntries(int $companyId, Collection $payrollIds): Collection
    {
        if ($payrollIds->isEmpty()) {
            return collect();
        }

        return PayrollEntry::query()
            ->where('created_by', $companyId)
            ->whereIn('payroll_id', $payrollIds->all())
            ->where(function ($query): void {
                $query->whereNull('is_cancelled')->orWhere('is_cancelled', false);
            })
            ->with([
                'payroll:id,title,pay_date',
                'employee:id,user_id,tax_payer_id,account_holder_name,bank_name,bank_branch,bank_identifier_code,account_number',
                'employee.user:id,name',
                'employee.foreignWorkerProfile:id,employee_id,residency_status',
                'employee.dependents:id,employee_id,is_tax_eligible,valid_until',
            ])
            ->orderBy('payroll_id')
            ->orderBy('employee_id')
            ->get([
                'id',
                'payroll_id',
                'employee_id',
                'gross_pay',
                'taxable_income',
                'irps_amount',
                'net_pay',
                'inss_employee_rate',
                'inss_employee_amount',
                'inss_employer_rate',
                'inss_employer_amount',
                'created_by',
            ]);
    }

    private function resolvePeriod(?string $referencePeriod): array
    {
        $period = $referencePeriod && preg_match('/^\d{4}-\d{2}$/', $referencePeriod)
            ? $referencePeriod
            : now()->format('Y-m');

        $periodStart = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        return [$period, $periodStart, $periodEnd];
    }

    private function countEligibleDependents(PayrollEntry $entry): int
    {
        $payDate = $entry->payroll?->pay_date
            ? Carbon::parse((string) $entry->payroll->pay_date)->startOfDay()
            : now()->startOfDay();

        return collect($entry->employee?->dependents ?? [])
            ->filter(function ($dependent) use ($payDate): bool {
                if (!$dependent || !$dependent->is_tax_eligible) {
                    return false;
                }

                if ($dependent->valid_until === null) {
                    return true;
                }

                return Carbon::parse((string) $dependent->valid_until)
                    ->endOfDay()
                    ->greaterThanOrEqualTo($payDate);
            })
            ->count();
    }
}
