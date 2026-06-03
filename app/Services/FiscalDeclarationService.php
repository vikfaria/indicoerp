<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FiscalDeclarationService
{
    public function __construct(
        private readonly VatCalculationService $vatCalculationService,
        private readonly IrpcCalculationService $irpcCalculationService,
        private readonly WithholdingTaxService $withholdingTaxService,
    ) {}

    public function getWithholdingDeclaration(int $companyId, string $year, int $month, array $filters = []): array
    {
        $declaration = $this->withholdingTaxService->getMonthlyDeclaration($companyId, $year, $month, $filters);

        return array_merge($declaration, [
            'due_date' => $this->resolveWithholdingDueDate($year, $month),
            'payment_reference' => sprintf('RET-%s-%02d', $year, $month),
        ]);
    }

    public function getModel20Support(int $companyId, string $fiscalYear): array
    {
        $startDate = "{$fiscalYear}-01-01";
        $endDate = "{$fiscalYear}-12-31";

        if (
            !Schema::hasTable('journal_entries')
            || !Schema::hasTable('journal_entry_items')
            || !Schema::hasTable('chart_of_accounts')
            || !Schema::hasColumn('chart_of_accounts', 'modelo20_line')
        ) {
            return [
                'fiscal_year' => $fiscalYear,
                'period' => ['start' => $startDate, 'end' => $endDate],
                'lines' => [],
                'unmapped_accounts' => [],
                'totals' => [
                    'debit' => 0.0,
                    'credit' => 0.0,
                    'net' => 0.0,
                    'mapped_movements' => 0,
                    'unmapped_movements' => 0,
                ],
                'warnings' => [__('Campos Modelo 20 não estão disponíveis no plano de contas.')],
            ];
        }

        $baseQuery = DB::table('journal_entry_items as jei')
            ->join('journal_entries as je', 'jei.journal_entry_id', '=', 'je.id')
            ->join('chart_of_accounts as coa', 'jei.account_id', '=', 'coa.id')
            ->where('je.created_by', $companyId)
            ->where('je.status', 'posted')
            ->whereBetween('je.journal_date', [$startDate, $endDate]);

        $lines = (clone $baseQuery)
            ->whereNotNull('coa.modelo20_line')
            ->where('coa.modelo20_line', '!=', '')
            ->groupBy('coa.modelo20_line')
            ->selectRaw(
                'coa.modelo20_line as model20_line,
                 COALESCE(SUM(jei.debit_amount),0) as debit_total,
                 COALESCE(SUM(jei.credit_amount),0) as credit_total,
                 COALESCE(SUM(jei.debit_amount - jei.credit_amount),0) as net_total,
                 COUNT(*) as movements'
            )
            ->orderBy('coa.modelo20_line')
            ->get()
            ->map(fn ($row) => [
                'model20_line' => (string) $row->model20_line,
                'debit_total' => (float) $row->debit_total,
                'credit_total' => (float) $row->credit_total,
                'net_total' => (float) $row->net_total,
                'movements' => (int) $row->movements,
            ])
            ->values()
            ->all();

        $unmappedAccounts = (clone $baseQuery)
            ->where(function ($query) {
                $query->whereNull('coa.modelo20_line')
                    ->orWhere('coa.modelo20_line', '');
            })
            ->groupBy('coa.id', 'coa.account_code', 'coa.account_name')
            ->selectRaw(
                'coa.id as account_id,
                 coa.account_code,
                 coa.account_name,
                 COALESCE(SUM(jei.debit_amount),0) as debit_total,
                 COALESCE(SUM(jei.credit_amount),0) as credit_total,
                 COUNT(*) as movements'
            )
            ->orderBy('coa.account_code')
            ->get()
            ->map(fn ($row) => [
                'account_id' => (int) $row->account_id,
                'account_code' => (string) $row->account_code,
                'account_name' => (string) $row->account_name,
                'debit_total' => (float) $row->debit_total,
                'credit_total' => (float) $row->credit_total,
                'movements' => (int) $row->movements,
            ])
            ->values()
            ->all();

        $totals = [
            'debit' => (float) collect($lines)->sum('debit_total'),
            'credit' => (float) collect($lines)->sum('credit_total'),
            'net' => (float) collect($lines)->sum('net_total'),
            'mapped_movements' => (int) collect($lines)->sum('movements'),
            'unmapped_movements' => (int) collect($unmappedAccounts)->sum('movements'),
        ];

        $warnings = [];
        if (!empty($unmappedAccounts)) {
            $warnings[] = __('Existem movimentos sem mapeamento Modelo 20. Configure "modelo20_line" no plano de contas.');
        }

        return [
            'fiscal_year' => $fiscalYear,
            'period' => ['start' => $startDate, 'end' => $endDate],
            'lines' => $lines,
            'unmapped_accounts' => $unmappedAccounts,
            'totals' => $totals,
            'warnings' => $warnings,
        ];
    }

    public function getAnnualFiscalDeclaration(int $companyId, string $fiscalYear): array
    {
        $startDate = "{$fiscalYear}-01-01";
        $endDate = "{$fiscalYear}-12-31";

        $vat = $this->vatCalculationService->calculatePeriodVat($companyId, $startDate, $endDate);
        $irpc = $this->irpcCalculationService->calculate($companyId, $fiscalYear);
        $model20 = $this->getModel20Support($companyId, $fiscalYear);

        $withholding = [
            'transaction_count' => 0,
            'gross_amount' => 0.0,
            'withholding_amount' => 0.0,
            'net_amount' => 0.0,
        ];

        if (Schema::hasTable('withholding_tax_transactions')) {
            $summary = DB::table('withholding_tax_transactions')
                ->where('company_id', $companyId)
                ->where('fiscal_year', $fiscalYear)
                ->selectRaw(
                    'COUNT(*) as transaction_count,
                     COALESCE(SUM(gross_amount),0) as gross_amount,
                     COALESCE(SUM(withholding_amount),0) as withholding_amount,
                     COALESCE(SUM(net_amount),0) as net_amount'
                )
                ->first();

            if ($summary !== null) {
                $withholding = [
                    'transaction_count' => (int) ($summary->transaction_count ?? 0),
                    'gross_amount' => (float) ($summary->gross_amount ?? 0),
                    'withholding_amount' => (float) ($summary->withholding_amount ?? 0),
                    'net_amount' => (float) ($summary->net_amount ?? 0),
                ];
            }
        }

        return [
            'fiscal_year' => $fiscalYear,
            'generated_at' => now()->toDateTimeString(),
            'period' => ['start' => $startDate, 'end' => $endDate],
            'vat' => $vat,
            'irpc' => $irpc,
            'withholding' => $withholding,
            'model20' => $model20,
        ];
    }

    /**
     * @param array<int, array<int, string>> $rows
     */
    public function toCsv(array $rows): string
    {
        $csv = '';
        foreach ($rows as $row) {
            $encoded = array_map(
                static fn (string $value): string => '"' . str_replace('"', '""', $value) . '"',
                $row
            );
            $csv .= implode(',', $encoded) . "\n";
        }

        return $csv;
    }

    private function resolveWithholdingDueDate(string $year, int $month): string
    {
        $date = \Carbon\Carbon::createFromDate((int) $year, $month, 1)
            ->addMonthNoOverflow()
            ->day(20);

        return $date->toDateString();
    }
}
