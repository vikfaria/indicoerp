<?php

namespace App\Services;

use App\Models\CompanyFiscalProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Generates official Mozambican financial statements:
 * - Balanço (Balance Sheet) — PGC-MZ format
 * - Demonstração de Resultados por Natureza
 * - Demonstração de Resultados por Funções
 * - Demonstração de Fluxos de Caixa (método directo)
 */
class FinancialStatementsService
{
    /**
     * Generate the Balance Sheet (Balanço) in PGC-MZ format.
     */
    public function generateBalanceSheet(int $companyId, string $asOfDate): array
    {
        $fiscalStartDate = $this->getFiscalYearStartDate($companyId, $asOfDate);
        $resultadoLiquido = $this->getClassBalance($companyId, 8, $asOfDate, 'credit');
        $resultadoLiquidoOrigem = 'class_8';

        if (abs($resultadoLiquido) < 0.01) {
            $resultadoLiquido = $this->calculateNetIncomeForPeriod($companyId, $fiscalStartDate, $asOfDate);
            $resultadoLiquidoOrigem = 'dre';
        }

        return [
            'title' => 'Balanço',
            'date' => $asOfDate,
            'activo' => [
                'activo_nao_corrente' => $this->getClassBalance($companyId, 4, $asOfDate, 'debit'),
                'activo_corrente' => [
                    'inventarios' => $this->getAccountBalance($companyId, '31', $asOfDate, 'debit')
                        + $this->getAccountBalance($companyId, '32', $asOfDate, 'debit')
                        + $this->getAccountBalance($companyId, '33', $asOfDate, 'debit'),
                    'clientes' => $this->getAccountBalance($companyId, '211', $asOfDate, 'debit'),
                    'estado' => $this->getAccountBalance($companyId, '24', $asOfDate, 'debit'),
                    'outros_devedores' => $this->getAccountBalance($companyId, '26', $asOfDate, 'debit')
                        + $this->getAccountBalance($companyId, '27', $asOfDate, 'debit'),
                    'diferimentos' => $this->getAccountBalance($companyId, '28', $asOfDate, 'debit'),
                    'caixa_bancos' => $this->getClassBalance($companyId, 1, $asOfDate, 'debit'),
                ],
            ],
            'capital_proprio' => [
                'capital_social' => $this->getAccountBalance($companyId, '51', $asOfDate, 'credit'),
                'reservas' => $this->getAccountBalance($companyId, '55', $asOfDate, 'credit'),
                'resultados_transitados' => $this->getAccountBalance($companyId, '56', $asOfDate, 'credit'),
                'resultado_liquido' => $resultadoLiquido,
                'resultado_liquido_origem' => $resultadoLiquidoOrigem,
            ],
            'passivo' => [
                'passivo_nao_corrente' => [
                    'emprestimos_mlp' => $this->getAccountBalance($companyId, '231', $asOfDate, 'credit'),
                    'provisoes' => $this->getAccountBalance($companyId, '29', $asOfDate, 'credit'),
                ],
                'passivo_corrente' => [
                    'fornecedores' => $this->getAccountBalance($companyId, '221', $asOfDate, 'credit'),
                    'estado' => $this->getAccountBalance($companyId, '24', $asOfDate, 'credit'),
                    'emprestimos_cp' => $this->getAccountBalance($companyId, '232', $asOfDate, 'credit'),
                    'outros_credores' => $this->getAccountBalance($companyId, '25', $asOfDate, 'credit')
                        + $this->getAccountBalance($companyId, '26', $asOfDate, 'credit'),
                ],
            ],
        ];
    }

    /**
     * Generate the Income Statement by Nature (Demonstração de Resultados por Natureza).
     */
    public function generateIncomeStatementByNature(int $companyId, string $startDate, string $endDate): array
    {
        return [
            'title' => 'Demonstração de Resultados por Natureza',
            'period' => ['start' => $startDate, 'end' => $endDate],
            'rendimentos' => [
                'vendas' => $this->getPeriodBalance($companyId, '71', $startDate, $endDate, 'credit'),
                'prestacoes_servicos' => $this->getPeriodBalance($companyId, '72', $startDate, $endDate, 'credit'),
                'variacao_producao' => $this->getPeriodBalance($companyId, '73', $startDate, $endDate, 'credit'),
                'trabalhos_propria_entidade' => $this->getPeriodBalance($companyId, '74', $startDate, $endDate, 'credit'),
                'subsidios' => $this->getPeriodBalance($companyId, '75', $startDate, $endDate, 'credit'),
                'outros_rendimentos' => $this->getPeriodBalance($companyId, '76', $startDate, $endDate, 'credit')
                    + $this->getPeriodBalance($companyId, '78', $startDate, $endDate, 'credit'),
                'rendimentos_financeiros' => $this->getPeriodBalance($companyId, '79', $startDate, $endDate, 'credit'),
            ],
            'gastos' => [
                'cmvmc' => $this->getPeriodBalance($companyId, '61', $startDate, $endDate, 'debit'),
                'fornecimentos_servicos' => $this->getPeriodBalance($companyId, '62', $startDate, $endDate, 'debit'),
                'gastos_pessoal' => $this->getPeriodBalance($companyId, '63', $startDate, $endDate, 'debit'),
                'depreciacao_amortizacao' => $this->getPeriodBalance($companyId, '64', $startDate, $endDate, 'debit'),
                'perdas_imparidade' => $this->getPeriodBalance($companyId, '65', $startDate, $endDate, 'debit'),
                'provisoes' => $this->getPeriodBalance($companyId, '66', $startDate, $endDate, 'debit'),
                'outros_gastos' => $this->getPeriodBalance($companyId, '67', $startDate, $endDate, 'debit')
                    + $this->getPeriodBalance($companyId, '68', $startDate, $endDate, 'debit'),
                'gastos_financeiros' => $this->getPeriodBalance($companyId, '69', $startDate, $endDate, 'debit'),
            ],
            'imposto_rendimento' => $this->getPeriodBalance($companyId, '85', $startDate, $endDate, 'debit'),
        ];
    }

    /**
     * Generate Cash Flow Statement (Demonstração de Fluxos de Caixa — método directo).
     */
    public function generateCashFlowStatement(int $companyId, string $startDate, string $endDate): array
    {
        // Class 1 accounts (Meios Financeiros Líquidos)
        $openingCash = $this->getClassBalance($companyId, 1, $startDate, 'debit');
        $closingCash = $this->getClassBalance($companyId, 1, $endDate, 'debit');

        // Operational: receipts from customers, payments to suppliers, payments to employees
        $receiptsCustomers = $this->getFlowBetweenClasses($companyId, 1, '211', $startDate, $endDate, 'debit');
        $paymentsSuppliers = $this->getFlowBetweenClasses($companyId, 1, '221', $startDate, $endDate, 'credit');
        $paymentsEmployees = $this->getFlowBetweenClasses($companyId, 1, '231', $startDate, $endDate, 'credit')
            + $this->getFlowBetweenClasses($companyId, 1, '232', $startDate, $endDate, 'credit');
        $paymentsTax = $this->getFlowBetweenClasses($companyId, 1, '24', $startDate, $endDate, 'credit');

        $operationalNet = $receiptsCustomers - $paymentsSuppliers - $paymentsEmployees - $paymentsTax;

        return [
            'title' => 'Demonstração de Fluxos de Caixa',
            'period' => ['start' => $startDate, 'end' => $endDate],
            'actividades_operacionais' => [
                'recebimentos_clientes' => round($receiptsCustomers, 2),
                'pagamentos_fornecedores' => round(-$paymentsSuppliers, 2),
                'pagamentos_pessoal' => round(-$paymentsEmployees, 2),
                'pagamentos_impostos' => round(-$paymentsTax, 2),
                'fluxo_liquido_operacional' => round($operationalNet, 2),
            ],
            'saldo_inicial' => round($openingCash, 2),
            'saldo_final' => round($closingCash, 2),
            'variacao' => round($closingCash - $openingCash, 2),
        ];
    }

    /**
     * Generate the Statement of Changes in Equity (Demonstração de Alterações no Capital Próprio).
     */
    public function generateStatementOfChangesInEquity(int $companyId, string $startDate, string $endDate): array
    {
        $openingDate = Carbon::parse($startDate)->subDay()->toDateString();
        $netIncome = $this->calculateNetIncomeForPeriod($companyId, $startDate, $endDate);

        $components = [
            'capital_social' => [
                'label' => 'Capital Social',
                'opening' => $this->getAccountBalance($companyId, '51', $openingDate, 'credit'),
                'closing' => $this->getAccountBalance($companyId, '51', $endDate, 'credit'),
            ],
            'acoes_proprias' => [
                'label' => 'Acções Próprias',
                'opening' => $this->getAccountBalance($companyId, '52', $openingDate, 'credit'),
                'closing' => $this->getAccountBalance($companyId, '52', $endDate, 'credit'),
            ],
            'reservas' => [
                'label' => 'Reservas',
                'opening' => $this->getAccountBalance($companyId, '55', $openingDate, 'credit'),
                'closing' => $this->getAccountBalance($companyId, '55', $endDate, 'credit'),
            ],
            'resultados_transitados' => [
                'label' => 'Resultados Transitados',
                'opening' => $this->getAccountBalance($companyId, '56', $openingDate, 'credit'),
                'closing' => $this->getAccountBalance($companyId, '56', $endDate, 'credit'),
            ],
            'resultado_liquido_exercicio' => [
                'label' => 'Resultado Líquido do Exercício',
                'opening' => 0.0,
                'closing' => $netIncome,
            ],
        ];

        foreach ($components as &$component) {
            $component['opening'] = round((float) ($component['opening'] ?? 0), 2);
            $component['closing'] = round((float) ($component['closing'] ?? 0), 2);
            $component['movement'] = round($component['closing'] - $component['opening'], 2);
        }
        unset($component);

        $openingTotal = round(array_sum(array_map(static fn (array $component): float => (float) $component['opening'], $components)), 2);
        $movementTotal = round(array_sum(array_map(static fn (array $component): float => (float) $component['movement'], $components)), 2);
        $closingTotal = round(array_sum(array_map(static fn (array $component): float => (float) $component['closing'], $components)), 2);
        $reconciliationDifference = round($closingTotal - ($openingTotal + $movementTotal), 2);

        $movementNotes = [];
        foreach ($components as $key => $component) {
            if ($key === 'resultado_liquido_exercicio' || abs($component['movement']) < 0.01) {
                continue;
            }

            $movementNotes[] = [
                'label' => $component['label'],
                'value' => $component['movement'],
            ];
        }

        $notes = [
            [
                'title' => 'Resumo do período',
                'items' => [
                    [
                        'label' => 'Resultado líquido apurado na DRE',
                        'value' => round($netIncome, 2),
                    ],
                    [
                        'label' => 'Período analisado',
                        'value' => "{$startDate} → {$endDate}",
                    ],
                    [
                        'label' => 'Moeda de apresentação',
                        'value' => 'MZN',
                    ],
                ],
            ],
            [
                'title' => 'Movimentos relevantes',
                'items' => $movementNotes,
            ],
            [
                'title' => 'Reconciliação',
                'items' => [
                    [
                        'label' => 'Capital próprio inicial',
                        'value' => $openingTotal,
                    ],
                    [
                        'label' => 'Movimento total do período',
                        'value' => $movementTotal,
                    ],
                    [
                        'label' => 'Capital próprio final',
                        'value' => $closingTotal,
                    ],
                    [
                        'label' => 'Diferença de reconciliação',
                        'value' => $reconciliationDifference,
                    ],
                ],
            ],
        ];

        return [
            'title' => 'Demonstração de Alterações no Capital Próprio',
            'period' => ['start' => $startDate, 'end' => $endDate],
            'components' => $components,
            'totals' => [
                'opening' => $openingTotal,
                'movement' => $movementTotal,
                'closing' => $closingTotal,
                'difference' => $reconciliationDifference,
                'is_balanced' => abs($reconciliationDifference) < 0.01,
                'net_income' => round($netIncome, 2),
            ],
            'notes' => $notes,
        ];
    }

    /**
     * Get account balance as of a date.
     */
    private function getAccountBalance(int $companyId, string $accountPrefix, string $asOfDate, string $normalSide): float
    {
        $result = DB::table('journal_entry_items as jei')
            ->join('journal_entries as je', 'jei.journal_entry_id', '=', 'je.id')
            ->join('chart_of_accounts as coa', 'jei.account_id', '=', 'coa.id')
            ->where('je.created_by', $companyId)
            ->where('je.status', 'posted')
            ->where('je.journal_date', '<=', $asOfDate)
            ->where('coa.account_code', 'like', $accountPrefix . '%')
            ->selectRaw('COALESCE(SUM(jei.debit_amount), 0) as debits, COALESCE(SUM(jei.credit_amount), 0) as credits')
            ->first();

        $balance = ($result->debits ?? 0) - ($result->credits ?? 0);
        return $normalSide === 'credit' ? -$balance : $balance;
    }

    /**
     * Get class balance as of a date.
     */
    private function getClassBalance(int $companyId, int $class, string $asOfDate, string $normalSide): float
    {
        $result = DB::table('journal_entry_items as jei')
            ->join('journal_entries as je', 'jei.journal_entry_id', '=', 'je.id')
            ->join('chart_of_accounts as coa', 'jei.account_id', '=', 'coa.id')
            ->where('je.created_by', $companyId)
            ->where('je.status', 'posted')
            ->where('je.journal_date', '<=', $asOfDate)
            ->where('coa.pgc_class', $class)
            ->selectRaw('COALESCE(SUM(jei.debit_amount), 0) as debits, COALESCE(SUM(jei.credit_amount), 0) as credits')
            ->first();

        $balance = ($result->debits ?? 0) - ($result->credits ?? 0);
        return $normalSide === 'credit' ? -$balance : $balance;
    }

    /**
     * Get period balance for income statement.
     */
    private function getPeriodBalance(int $companyId, string $accountPrefix, string $startDate, string $endDate, string $normalSide): float
    {
        $result = DB::table('journal_entry_items as jei')
            ->join('journal_entries as je', 'jei.journal_entry_id', '=', 'je.id')
            ->join('chart_of_accounts as coa', 'jei.account_id', '=', 'coa.id')
            ->where('je.created_by', $companyId)
            ->where('je.status', 'posted')
            ->whereBetween('je.journal_date', [$startDate, $endDate])
            ->where('coa.account_code', 'like', $accountPrefix . '%')
            ->selectRaw('COALESCE(SUM(jei.debit_amount), 0) as debits, COALESCE(SUM(jei.credit_amount), 0) as credits')
            ->first();

        $balance = ($result->debits ?? 0) - ($result->credits ?? 0);
        return abs($normalSide === 'credit' ? -$balance : $balance);
    }

    /**
     * Get cash flow between class 1 accounts and a specific account prefix.
     */
    private function getFlowBetweenClasses(int $companyId, int $cashClass, string $counterpartPrefix, string $startDate, string $endDate, string $side): float
    {
        $column = $side === 'debit' ? 'jei.debit_amount' : 'jei.credit_amount';

        $result = DB::table('journal_entry_items as jei')
            ->join('journal_entries as je', 'jei.journal_entry_id', '=', 'je.id')
            ->join('chart_of_accounts as coa', 'jei.account_id', '=', 'coa.id')
            ->where('je.created_by', $companyId)
            ->where('je.status', 'posted')
            ->whereBetween('je.journal_date', [$startDate, $endDate])
            ->where('coa.pgc_class', $cashClass)
            ->whereExists(function ($q) use ($counterpartPrefix) {
                $q->select(DB::raw(1))
                    ->from('journal_entry_items as jei2')
                    ->join('chart_of_accounts as coa2', 'jei2.account_id', '=', 'coa2.id')
                    ->whereColumn('jei2.journal_entry_id', 'jei.journal_entry_id')
                    ->where('coa2.account_code', 'like', $counterpartPrefix . '%');
            })
            ->selectRaw("COALESCE(SUM({$column}), 0) as total")
            ->first();

        return (float) ($result->total ?? 0);
    }

    private function calculateNetIncomeForPeriod(int $companyId, string $startDate, string $endDate): float
    {
        $incomeStatement = $this->generateIncomeStatementByNature($companyId, $startDate, $endDate);

        $totalRendimentos = array_sum(array_map(
            static fn ($value): float => (float) $value,
            $incomeStatement['rendimentos'] ?? []
        ));

        $totalGastos = array_sum(array_map(
            static fn ($value): float => (float) $value,
            $incomeStatement['gastos'] ?? []
        ));

        $impostoRendimento = (float) ($incomeStatement['imposto_rendimento'] ?? 0);

        return round($totalRendimentos - $totalGastos - $impostoRendimento, 2);
    }

    private function getFiscalYearStartDate(int $companyId, string $referenceDate): string
    {
        $profile = CompanyFiscalProfile::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        $startMonth = (int) ($profile?->fiscal_year_start_month ?: 1);
        $startMonth = max(1, min(12, $startMonth));
        $reference = Carbon::parse($referenceDate);

        if ($startMonth === 1) {
            return $reference->copy()->startOfYear()->toDateString();
        }

        $year = (int) $reference->format('Y');
        if ((int) $reference->format('n') < $startMonth) {
            $year -= 1;
        }

        return Carbon::create($year, $startMonth, 1)->toDateString();
    }
}
