<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Workdo\Account\Models\JournalEntry;
use Workdo\Hrm\Models\Payroll;
use Workdo\Hrm\Models\PayrollEntry;

class MozambiquePayrollAccountingExportService
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $timesheetAllocationCache = [];

    /**
     * @var array<int, array{project_name:string, client_name:string}>
     */
    private array $projectClientCache = [];

    public function __construct(
        private readonly PayrollCostCenterAllocatorService $costCenterAllocatorService
    ) {}

    public function buildCostAllocationDataset(int $companyId, ?string $referencePeriod = null): array
    {
        [$period, $periodStart, $periodEnd] = $this->resolvePeriod($referencePeriod);

        $entries = $this->resolvePayrollEntriesForPeriod($companyId, $periodStart, $periodEnd);

        $rows = $entries->map(function (PayrollEntry $entry) use ($period, $companyId): array {
            $costCenter = $this->costCenterAllocatorService->resolveCostCenterForEntry($entry, $companyId);
            $departmentName = $entry->employee?->department?->department_name;
            $branchName = $entry->employee?->branch?->branch_name;
            $designationName = $entry->employee?->designation?->designation_name;
            $allocationDimensions = $this->resolveAllocationDimensions($entry, $companyId);

            return [
                'reference_period' => $period,
                'payroll_id' => $entry->payroll_id,
                'payroll_title' => $entry->payroll?->title ?? '-',
                'pay_date' => optional($entry->payroll?->pay_date)->format('Y-m-d'),
                'employee_name' => $entry->employee?->user?->name ?? '-',
                'employee_nuit' => $entry->employee?->tax_payer_id ?? '',
                'branch' => $branchName ?? '',
                'department' => $departmentName ?? '',
                'designation' => $designationName ?? '',
                'business_unit' => $departmentName ?? '',
                'worksite' => $branchName ?? '',
                'cost_center_code' => $costCenter?->code ?? '',
                'cost_center_name' => $costCenter?->name ?? '',
                'project_name' => $allocationDimensions['project_name'],
                'client_name' => $allocationDimensions['client_name'],
                'allocation_source' => $allocationDimensions['allocation_source'],
                'allocation_minutes' => $allocationDimensions['allocation_minutes'],
                'gross_pay' => (float) ($entry->gross_pay ?? 0),
                'total_allowances' => (float) ($entry->total_allowances ?? 0),
                'total_manual_overtimes' => (float) ($entry->total_manual_overtimes ?? 0),
                'total_deductions' => (float) ($entry->total_deductions ?? 0),
                'total_loans' => (float) ($entry->total_loans ?? 0),
                'irps_amount' => (float) ($entry->irps_amount ?? 0),
                'inss_employee_amount' => (float) ($entry->inss_employee_amount ?? 0),
                'inss_employer_amount' => (float) ($entry->inss_employer_amount ?? 0),
                'net_pay' => (float) ($entry->net_pay ?? 0),
            ];
        })->values();

        return [
            'reference_period' => $period,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'summary' => [
                'rows' => $rows->count(),
                'gross_pay_total' => round((float) $rows->sum('gross_pay'), 2),
                'net_pay_total' => round((float) $rows->sum('net_pay'), 2),
                'irps_total' => round((float) $rows->sum('irps_amount'), 2),
                'inss_employee_total' => round((float) $rows->sum('inss_employee_amount'), 2),
                'inss_employer_total' => round((float) $rows->sum('inss_employer_amount'), 2),
            ],
            'rows' => $rows->all(),
        ];
    }

    public function buildJournalLinesDataset(int $companyId, ?string $referencePeriod = null): array
    {
        [$period, $periodStart, $periodEnd] = $this->resolvePeriod($referencePeriod);

        $journals = JournalEntry::query()
            ->where('created_by', $companyId)
            ->where('reference_type', 'payroll')
            ->whereDate('journal_date', '>=', $periodStart->toDateString())
            ->whereDate('journal_date', '<=', $periodEnd->toDateString())
            ->with([
                'accountingJournal:id,code,name',
                'items.account:id,account_code,account_name',
                'items.costCenter:id,code,name',
            ])
            ->orderBy('journal_date')
            ->orderBy('id')
            ->get([
                'id',
                'journal_number',
                'journal_date',
                'reference_id',
                'total_debit',
                'total_credit',
                'accounting_journal_id',
                'created_by',
            ]);

        $referenceIds = $journals->pluck('reference_id')->filter()->values();

        $payrollEntries = PayrollEntry::query()
            ->where('created_by', $companyId)
            ->whereIn('id', $referenceIds->all())
            ->where(function ($query): void {
                $query->whereNull('is_cancelled')->orWhere('is_cancelled', false);
            })
            ->with([
                'payroll:id,title,pay_date',
                'employee:id,user_id',
                'employee.user:id,name',
            ])
            ->get([
                'id',
                'payroll_id',
                'employee_id',
                'created_by',
            ])
            ->keyBy('id');

        $rows = collect();

        foreach ($journals as $journal) {
            $entryMeta = $payrollEntries->get((int) $journal->reference_id);
            $lineNumber = 1;

            foreach ($journal->items as $item) {
                $rows->push([
                    'reference_period' => $period,
                    'journal_id' => $journal->id,
                    'journal_number' => $journal->journal_number,
                    'journal_date' => optional($journal->journal_date)->format('Y-m-d'),
                    'journal_code' => $journal->accountingJournal?->code ?? '',
                    'journal_name' => $journal->accountingJournal?->name ?? '',
                    'line_number' => $lineNumber++,
                    'payroll_entry_id' => $journal->reference_id,
                    'payroll_id' => $entryMeta?->payroll_id,
                    'payroll_title' => $entryMeta?->payroll?->title ?? '',
                    'employee_name' => $entryMeta?->employee?->user?->name ?? '',
                    'account_code' => $item->account?->account_code ?? '',
                    'account_name' => $item->account?->account_name ?? '',
                    'cost_center_code' => $item->costCenter?->code ?? '',
                    'cost_center_name' => $item->costCenter?->name ?? '',
                    'debit_amount' => (float) ($item->debit_amount ?? 0),
                    'credit_amount' => (float) ($item->credit_amount ?? 0),
                    'description' => (string) ($item->description ?? ''),
                ]);
            }
        }

        return [
            'reference_period' => $period,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'summary' => [
                'journals' => $journals->count(),
                'lines' => $rows->count(),
                'total_debit' => round((float) $rows->sum('debit_amount'), 2),
                'total_credit' => round((float) $rows->sum('credit_amount'), 2),
            ],
            'rows' => $rows->all(),
        ];
    }

    public function buildMonthlyPayrollSummaryDataset(int $companyId, ?string $referencePeriod = null): array
    {
        [$period, $periodStart, $periodEnd] = $this->resolvePeriod($referencePeriod);

        $payrolls = Payroll::query()
            ->where('created_by', $companyId)
            ->where('status', 'completed')
            ->whereNull('cancelled_at')
            ->where(function ($query) use ($periodStart, $periodEnd): void {
                $query
                    ->where(function ($q) use ($periodStart, $periodEnd): void {
                        $q->whereDate('pay_period_end', '>=', $periodStart->toDateString())
                            ->whereDate('pay_period_end', '<=', $periodEnd->toDateString());
                    })
                    ->orWhere(function ($q) use ($periodStart, $periodEnd): void {
                        $q->whereDate('pay_date', '>=', $periodStart->toDateString())
                            ->whereDate('pay_date', '<=', $periodEnd->toDateString());
                    });
            })
            ->with([
                'activePayrollEntries:id,payroll_id,gross_pay,total_allowances,total_manual_overtimes,total_deductions,total_loans,irps_amount,inss_employee_amount,inss_employer_amount,net_pay',
            ])
            ->withCount(['activePayrollEntries as active_entries_count'])
            ->orderBy('pay_date')
            ->orderBy('id')
            ->get([
                'id',
                'title',
                'pay_period_start',
                'pay_period_end',
                'pay_date',
                'is_payroll_paid',
                'employee_count',
                'total_gross_pay',
                'total_deductions',
                'total_net_pay',
                'total_irps',
                'total_inss_employee',
                'total_inss_employer',
                'created_by',
            ]);

        $rows = $payrolls->map(function (Payroll $payroll) use ($period): array {
            $entries = $payroll->activePayrollEntries;

            $grossFromEntries = round((float) $entries->sum('gross_pay'), 2);
            $deductionsFromEntries = round((float) $entries->sum('total_deductions'), 2);
            $netFromEntries = round((float) $entries->sum('net_pay'), 2);
            $irpsFromEntries = round((float) $entries->sum('irps_amount'), 2);
            $inssEmployeeFromEntries = round((float) $entries->sum('inss_employee_amount'), 2);
            $inssEmployerFromEntries = round((float) $entries->sum('inss_employer_amount'), 2);
            $allowancesFromEntries = round((float) $entries->sum('total_allowances'), 2);
            $manualOvertimeFromEntries = round((float) $entries->sum('total_manual_overtimes'), 2);
            $loansFromEntries = round((float) $entries->sum('total_loans'), 2);

            return [
                'reference_period' => $period,
                'payroll_id' => (int) $payroll->id,
                'payroll_title' => (string) ($payroll->title ?? ''),
                'pay_period_start' => optional($payroll->pay_period_start)->format('Y-m-d'),
                'pay_period_end' => optional($payroll->pay_period_end)->format('Y-m-d'),
                'pay_date' => optional($payroll->pay_date)->format('Y-m-d'),
                'payment_status' => (string) ($payroll->is_payroll_paid ?? ''),
                'active_entries_count' => (int) ($payroll->active_entries_count ?? 0),
                'employee_count' => (int) ($payroll->employee_count ?? 0),
                'gross_pay_total' => $grossFromEntries > 0 ? $grossFromEntries : round((float) ($payroll->total_gross_pay ?? 0), 2),
                'deductions_total' => $deductionsFromEntries > 0 ? $deductionsFromEntries : round((float) ($payroll->total_deductions ?? 0), 2),
                'net_pay_total' => $netFromEntries > 0 ? $netFromEntries : round((float) ($payroll->total_net_pay ?? 0), 2),
                'irps_total' => $irpsFromEntries > 0 ? $irpsFromEntries : round((float) ($payroll->total_irps ?? 0), 2),
                'inss_employee_total' => $inssEmployeeFromEntries > 0 ? $inssEmployeeFromEntries : round((float) ($payroll->total_inss_employee ?? 0), 2),
                'inss_employer_total' => $inssEmployerFromEntries > 0 ? $inssEmployerFromEntries : round((float) ($payroll->total_inss_employer ?? 0), 2),
                'allowances_total' => $allowancesFromEntries,
                'manual_overtime_total' => $manualOvertimeFromEntries,
                'loans_total' => $loansFromEntries,
            ];
        })->values();

        return [
            'reference_period' => $period,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'summary' => [
                'payroll_runs' => $rows->count(),
                'active_entries' => (int) $rows->sum('active_entries_count'),
                'employee_count_total' => (int) $rows->sum('employee_count'),
                'gross_pay_total' => round((float) $rows->sum('gross_pay_total'), 2),
                'deductions_total' => round((float) $rows->sum('deductions_total'), 2),
                'net_pay_total' => round((float) $rows->sum('net_pay_total'), 2),
                'irps_total' => round((float) $rows->sum('irps_total'), 2),
                'inss_employee_total' => round((float) $rows->sum('inss_employee_total'), 2),
                'inss_employer_total' => round((float) $rows->sum('inss_employer_total'), 2),
                'allowances_total' => round((float) $rows->sum('allowances_total'), 2),
                'manual_overtime_total' => round((float) $rows->sum('manual_overtime_total'), 2),
                'loans_total' => round((float) $rows->sum('loans_total'), 2),
            ],
            'rows' => $rows->all(),
        ];
    }

    private function resolvePayrollEntriesForPeriod(int $companyId, Carbon $periodStart, Carbon $periodEnd): Collection
    {
        return PayrollEntry::query()
            ->where('created_by', $companyId)
            ->where(function ($query): void {
                $query->whereNull('is_cancelled')->orWhere('is_cancelled', false);
            })
            ->whereHas('payroll', function ($query) use ($companyId, $periodStart, $periodEnd): void {
                $query
                    ->where('created_by', $companyId)
                    ->where('status', 'completed')
                    ->where(function ($dateQuery) use ($periodStart, $periodEnd): void {
                        $dateQuery
                            ->where(function ($q) use ($periodStart, $periodEnd): void {
                                $q->whereDate('pay_period_end', '>=', $periodStart->toDateString())
                                    ->whereDate('pay_period_end', '<=', $periodEnd->toDateString());
                            })
                            ->orWhere(function ($q) use ($periodStart, $periodEnd): void {
                                $q->whereDate('pay_date', '>=', $periodStart->toDateString())
                                    ->whereDate('pay_date', '<=', $periodEnd->toDateString());
                            });
                    });
            })
            ->with([
                'payroll:id,title,pay_date,pay_period_start,pay_period_end',
                'employee:id,user_id,tax_payer_id,department_id,branch_id,designation_id',
                'employee.user:id,name',
                'employee.department:id,department_name',
                'employee.branch:id,branch_name',
                'employee.designation:id,designation_name',
            ])
            ->orderBy('payroll_id')
            ->orderBy('employee_id')
            ->get([
                'id',
                'payroll_id',
                'employee_id',
                'gross_pay',
                'total_allowances',
                'total_manual_overtimes',
                'total_deductions',
                'total_loans',
                'irps_amount',
                'inss_employee_amount',
                'inss_employer_amount',
                'net_pay',
                'created_by',
            ]);
    }

    private function resolvePeriod(?string $referencePeriod): array
    {
        $period = $referencePeriod && preg_match('/^\d{4}-\d{2}$/', $referencePeriod)
            ? $referencePeriod
            : now()->format('Y-m');

        $periodStart = Carbon::createFromFormat('Y-m-d', $period . '-01')->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        return [$period, $periodStart, $periodEnd];
    }

    /**
     * Resolve additional allocation dimensions (project/client) from timesheets when available.
     *
     * @return array{project_name:string, client_name:string, allocation_source:string, allocation_minutes:int}
     */
    private function resolveAllocationDimensions(PayrollEntry $entry, int $companyId): array
    {
        $default = [
            'project_name' => '',
            'client_name' => '',
            'allocation_source' => 'employee',
            'allocation_minutes' => 0,
        ];

        $employeeUserId = (int) ($entry->employee_id ?? 0);
        if ($employeeUserId <= 0) {
            return $default;
        }

        $periodStart = optional($entry->payroll?->pay_period_start)->toDateString()
            ?: optional($entry->payroll?->pay_date)->toDateString();
        $periodEnd = optional($entry->payroll?->pay_period_end)->toDateString()
            ?: optional($entry->payroll?->pay_date)->toDateString();

        if (!$periodStart || !$periodEnd) {
            return $default;
        }

        $cacheKey = implode(':', [$companyId, $employeeUserId, $periodStart, $periodEnd]);
        if (isset($this->timesheetAllocationCache[$cacheKey])) {
            return $this->timesheetAllocationCache[$cacheKey];
        }

        if (!class_exists('\\Workdo\\Timesheet\\Models\\Timesheet') || !Schema::hasTable('timesheets')) {
            $this->timesheetAllocationCache[$cacheKey] = $default;

            return $default;
        }

        $timesheetClass = '\\Workdo\\Timesheet\\Models\\Timesheet';
        $topProjectAllocation = $timesheetClass::query()
            ->where('created_by', $companyId)
            ->where('user_id', $employeeUserId)
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->whereNotNull('project_id')
            ->select('project_id')
            ->selectRaw('SUM((COALESCE(hours, 0) * 60) + COALESCE(minutes, 0)) as total_minutes')
            ->groupBy('project_id')
            ->orderByDesc('total_minutes')
            ->first();

        if (!$topProjectAllocation || empty($topProjectAllocation->project_id)) {
            $this->timesheetAllocationCache[$cacheKey] = $default;

            return $default;
        }

        $projectId = (int) $topProjectAllocation->project_id;
        $projectMeta = $this->resolveProjectMetadata($projectId);

        $resolved = [
            'project_name' => (string) ($projectMeta['project_name'] ?? ''),
            'client_name' => (string) ($projectMeta['client_name'] ?? ''),
            'allocation_source' => 'timesheet',
            'allocation_minutes' => (int) ($topProjectAllocation->total_minutes ?? 0),
        ];

        $this->timesheetAllocationCache[$cacheKey] = $resolved;

        return $resolved;
    }

    /**
     * @return array{project_name:string, client_name:string}
     */
    private function resolveProjectMetadata(int $projectId): array
    {
        if ($projectId <= 0) {
            return ['project_name' => '', 'client_name' => ''];
        }

        if (isset($this->projectClientCache[$projectId])) {
            return $this->projectClientCache[$projectId];
        }

        if (!class_exists('\\Workdo\\Taskly\\Models\\Project') || !Schema::hasTable('projects')) {
            return ['project_name' => '', 'client_name' => ''];
        }

        $projectClass = '\\Workdo\\Taskly\\Models\\Project';
        $project = $projectClass::query()
            ->where('id', $projectId)
            ->with(['clients:id,name'])
            ->first(['id', 'name']);

        if (!$project) {
            return ['project_name' => '', 'client_name' => ''];
        }

        $resolved = [
            'project_name' => (string) ($project->name ?? ''),
            'client_name' => (string) ($project->clients->first()->name ?? ''),
        ];

        $this->projectClientCache[$projectId] = $resolved;

        return $resolved;
    }
}
