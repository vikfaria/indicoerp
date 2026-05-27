<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Workdo\Account\Models\JournalEntry;
use Workdo\Hrm\Models\Payroll;
use Workdo\Hrm\Models\PayrollEntry;

class MozambiquePayrollAccountingExportService
{
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
                'cost_center_code' => $costCenter?->code ?? '',
                'cost_center_name' => $costCenter?->name ?? '',
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

        $periodStart = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        return [$period, $periodStart, $periodEnd];
    }
}
