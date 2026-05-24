<?php

namespace App\Services;

use App\Models\IrpcConfiguration;
use App\Models\TaxAdjustment;
use Illuminate\Support\Facades\DB;
use Workdo\DoubleEntry\Services\BalanceSheetService;

/**
 * IRPC (Imposto sobre o Rendimento das Pessoas Colectivas) calculation engine.
 * Handles: accounting result, fiscal corrections, taxable income, tax, payments on account.
 */
class IrpcCalculationService
{
    /**
     * Calculate IRPC for a given company and fiscal year.
     */
    public function calculate(int $companyId, string $fiscalYear): array
    {
        $config = IrpcConfiguration::where('company_id', $companyId)
            ->where('fiscal_year', $fiscalYear)
            ->first();

        if (!$config) {
            $config = new IrpcConfiguration([
                'standard_rate' => 32.00,
                'regime' => 'normal',
                'payment_on_account_rate' => 80.00,
                'is_first_year' => false,
            ]);
        }

        // 1. Accounting result (Resultado contabilístico)
        $balanceSheetService = app(BalanceSheetService::class);
        $endDate = "{$fiscalYear}-12-31";
        $accountingResult = $balanceSheetService->calculateNetIncome($endDate);

        // 2. Fiscal adjustments
        $adjustments = TaxAdjustment::where('company_id', $companyId)
            ->where('fiscal_year', $fiscalYear)
            ->get();

        $addBacks = $adjustments->where('type', 'add_back')->sum('amount');
        $deductions = $adjustments->where('type', 'deduction')->sum('amount');

        // 3. Taxable income (Matéria colectável)
        $taxableIncome = $accountingResult + $addBacks - $deductions;
        $taxableIncome = max($taxableIncome, 0); // Cannot be negative for tax purposes

        // 4. Tax calculation
        $rate = $config->getApplicableRate();
        $grossTax = round($taxableIncome * $rate / 100, 2);

        // 5. Payments on account (Pagamentos por conta) - 3 installments
        $previousYearTax = $this->getPreviousYearTax($companyId, $fiscalYear);
        $ppcRate = $config->payment_on_account_rate / 100;
        $totalPpc = round($previousYearTax * $ppcRate, 2);
        $ppcInstallment = round($totalPpc / 3, 2);

        // 6. Withholdings already made (retenções sofridas)
        $withholdingsSuffered = $this->getWithholdingsSuffered($companyId, $fiscalYear);

        // 7. Net tax payable
        $netTax = $grossTax - $totalPpc - $withholdingsSuffered;

        return [
            'fiscal_year' => $fiscalYear,
            'accounting_result' => round($accountingResult, 2),
            'add_backs' => round($addBacks, 2),
            'deductions' => round($deductions, 2),
            'taxable_income' => round($taxableIncome, 2),
            'rate' => $rate,
            'gross_tax' => $grossTax,
            'payments_on_account' => [
                'total' => $totalPpc,
                'installment' => $ppcInstallment,
                'may' => $ppcInstallment,
                'july' => $ppcInstallment,
                'september' => round($totalPpc - ($ppcInstallment * 2), 2), // Remainder
            ],
            'withholdings_suffered' => round($withholdingsSuffered, 2),
            'net_tax_payable' => round(max($netTax, 0), 2),
            'net_tax_recoverable' => round(max(-$netTax, 0), 2),
            'adjustments_detail' => $adjustments->toArray(),
        ];
    }

    /**
     * Get standard fiscal adjustment categories.
     */
    public function getAdjustmentCategories(): array
    {
        return [
            'add_back' => [
                'excess_depreciation' => 'Depreciações acima das taxas legais',
                'provisions_not_deductible' => 'Provisões não aceites fiscalmente',
                'fines_penalties' => 'Multas e penalidades',
                'entertainment' => 'Despesas de representação (50%)',
                'donations_excess' => 'Donativos acima do limite',
                'undocumented_expenses' => 'Gastos não documentados',
                'personal_expenses' => 'Despesas pessoais dos sócios',
                'irpc_expense' => 'IRPC do exercício',
                'non_deductible_taxes' => 'Impostos não dedutíveis',
            ],
            'deduction' => [
                'tax_benefits' => 'Benefícios fiscais',
                'previous_losses' => 'Prejuízos fiscais de anos anteriores',
                'reinvestment' => 'Dedução por reinvestimento',
                'job_creation' => 'Dedução por criação de emprego',
                'exempt_income' => 'Rendimentos isentos',
            ],
        ];
    }

    private function getPreviousYearTax(int $companyId, string $fiscalYear): float
    {
        $prevYear = (string) ((int) $fiscalYear - 1);

        $result = DB::table('journal_entry_items as jei')
            ->join('journal_entries as je', 'jei.journal_entry_id', '=', 'je.id')
            ->join('chart_of_accounts as coa', 'jei.account_id', '=', 'coa.id')
            ->where('je.created_by', $companyId)
            ->where('je.status', 'posted')
            ->where('je.fiscal_year', $prevYear)
            ->where('coa.account_code', 'like', '85%') // PGC Class 8: imposto sobre rendimento
            ->selectRaw('COALESCE(SUM(jei.debit_amount), 0) as total')
            ->first();

        return (float) ($result->total ?? 0);
    }

    private function getWithholdingsSuffered(int $companyId, string $fiscalYear): float
    {
        // Account 249 - Pagamentos por conta IRPC (debit balance = credit to company)
        $result = DB::table('journal_entry_items as jei')
            ->join('journal_entries as je', 'jei.journal_entry_id', '=', 'je.id')
            ->join('chart_of_accounts as coa', 'jei.account_id', '=', 'coa.id')
            ->where('je.created_by', $companyId)
            ->where('je.status', 'posted')
            ->where('je.fiscal_year', $fiscalYear)
            ->where('coa.account_code', 'like', '249%')
            ->selectRaw('COALESCE(SUM(jei.debit_amount) - SUM(jei.credit_amount), 0) as total')
            ->first();

        return (float) ($result->total ?? 0);
    }
}
