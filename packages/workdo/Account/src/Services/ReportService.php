<?php

namespace Workdo\Account\Services;

use App\Support\MozambiqueTaxNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Workdo\Account\Models\MozFiscalClosing;
use Workdo\Account\Models\MozPilotCompany;
use Workdo\Account\Models\MozPilotValidationCase;
use Workdo\Account\Models\MozTaxAccountMapping;

class ReportService
{
    public function getInvoiceAging($filters = [])
    {
        $asOfDate = $filters['as_of_date'] ?? date('Y-m-d');

        $invoices = DB::table('sales_invoices')
            ->where('sales_invoices.created_by', creatorId())
            ->whereIn('sales_invoices.status', ['posted', 'partial'])
            ->leftJoin('users', 'sales_invoices.customer_id', '=', 'users.id')
            ->where('users.type', 'client')
            ->where('sales_invoices.balance_amount', '>', 0)
            ->select(
                'sales_invoices.id',
                'sales_invoices.invoice_number',
                'sales_invoices.due_date',
                'sales_invoices.balance_amount as balance',
                'users.name as customer_name',
                'users.id as customer_id',
                DB::raw('DATEDIFF("' . $asOfDate . '", sales_invoices.due_date) as days_overdue')
            )
            ->get();

        $aging = [
            'current' => 0,
            '1_30_days' => 0,
            '31_60_days' => 0,
            '61_90_days' => 0,
            'over_90_days' => 0,
            'total' => 0
        ];

        $customerData = [];

        foreach ($invoices as $invoice) {
            $balance = $invoice->balance;
            $days = $invoice->days_overdue;

            if ($days <= 0) {
                $aging['current'] += $balance;
                $bucket = 'current';
            } elseif ($days <= 30) {
                $aging['1_30_days'] += $balance;
                $bucket = '1_30_days';
            } elseif ($days <= 60) {
                $aging['31_60_days'] += $balance;
                $bucket = '31_60_days';
            } elseif ($days <= 90) {
                $aging['61_90_days'] += $balance;
                $bucket = '61_90_days';
            } else {
                $aging['over_90_days'] += $balance;
                $bucket = 'over_90_days';
            }

            $aging['total'] += $balance;

            if (!isset($customerData[$invoice->customer_id])) {
                $customerData[$invoice->customer_id] = [
                    'customer_name' => $invoice->customer_name,
                    'current' => 0,
                    '1_30_days' => 0,
                    '31_60_days' => 0,
                    '61_90_days' => 0,
                    'over_90_days' => 0,
                    'total' => 0
                ];
            }

            $customerData[$invoice->customer_id][$bucket] += $balance;
            $customerData[$invoice->customer_id]['total'] += $balance;
        }

        return [
            'aging_summary' => $aging,
            'customers' => array_values($customerData),
            'as_of_date' => $asOfDate
        ];
    }

    public function getBillAging($filters = [])
    {
        $asOfDate = $filters['as_of_date'] ?? date('Y-m-d');

        $bills = DB::table('purchase_invoices')
            ->where('purchase_invoices.created_by', creatorId())
            ->whereIn('purchase_invoices.status', ['posted', 'partial'])
            ->leftJoin('users', 'purchase_invoices.vendor_id', '=', 'users.id')
            ->where('users.type', 'vendor')
            ->where('purchase_invoices.balance_amount', '>', 0)
            ->select(
                'purchase_invoices.id',
                'purchase_invoices.invoice_number as bill_number',
                'purchase_invoices.due_date',
                'purchase_invoices.balance_amount as balance',
                'users.name as vendor_name',
                'users.id as vendor_id',
                DB::raw('DATEDIFF("' . $asOfDate . '", purchase_invoices.due_date) as days_overdue')
            )
            ->get();

        $aging = [
            'current' => 0,
            '1_30_days' => 0,
            '31_60_days' => 0,
            '61_90_days' => 0,
            'over_90_days' => 0,
            'total' => 0
        ];

        $vendorData = [];

        foreach ($bills as $bill) {
            $balance = $bill->balance;
            $days = $bill->days_overdue;

            if ($days <= 0) {
                $aging['current'] += $balance;
                $bucket = 'current';
            } elseif ($days <= 30) {
                $aging['1_30_days'] += $balance;
                $bucket = '1_30_days';
            } elseif ($days <= 60) {
                $aging['31_60_days'] += $balance;
                $bucket = '31_60_days';
            } elseif ($days <= 90) {
                $aging['61_90_days'] += $balance;
                $bucket = '61_90_days';
            } else {
                $aging['over_90_days'] += $balance;
                $bucket = 'over_90_days';
            }

            $aging['total'] += $balance;

            if (!isset($vendorData[$bill->vendor_id])) {
                $vendorData[$bill->vendor_id] = [
                    'vendor_name' => $bill->vendor_name,
                    'current' => 0,
                    '1_30_days' => 0,
                    '31_60_days' => 0,
                    '61_90_days' => 0,
                    'over_90_days' => 0,
                    'total' => 0
                ];
            }

            $vendorData[$bill->vendor_id][$bucket] += $balance;
            $vendorData[$bill->vendor_id]['total'] += $balance;
        }

        return [
            'aging_summary' => $aging,
            'vendors' => array_values($vendorData),
            'as_of_date' => $asOfDate
        ];
    }

    public function getTaxSummary($filters = [])
    {
        $fromDate = $filters['from_date'] ?? date('Y-01-01');
        $toDate = $filters['to_date'] ?? date('Y-12-31');

        // Get tax collected from sales invoices
        $taxCollected = DB::table('sales_invoice_item_taxes')
            ->join('sales_invoice_items', 'sales_invoice_item_taxes.item_id', '=', 'sales_invoice_items.id')
            ->join('sales_invoices', 'sales_invoice_items.invoice_id', '=', 'sales_invoices.id')
            ->where('sales_invoices.created_by', creatorId())
            ->whereIn('sales_invoices.status', ['posted', 'partial', 'paid'])
            ->whereBetween('sales_invoices.invoice_date', [$fromDate, $toDate])
            ->select(
                'sales_invoice_item_taxes.tax_name',
                'sales_invoice_item_taxes.tax_rate',
                DB::raw('SUM((sales_invoice_items.unit_price * sales_invoice_items.quantity - sales_invoice_items.discount_amount) * sales_invoice_item_taxes.tax_rate / 100) as tax_amount')
            )
            ->groupBy('sales_invoice_item_taxes.tax_name', 'sales_invoice_item_taxes.tax_rate')
            ->get();

        // Get tax paid on purchases
        $taxPaid = DB::table('purchase_invoice_item_taxes')
            ->join('purchase_invoice_items', 'purchase_invoice_item_taxes.item_id', '=', 'purchase_invoice_items.id')
            ->join('purchase_invoices', 'purchase_invoice_items.invoice_id', '=', 'purchase_invoices.id')
            ->where('purchase_invoices.created_by', creatorId())
            ->whereIn('purchase_invoices.status', ['posted', 'partial', 'paid'])
            ->whereBetween('purchase_invoices.invoice_date', [$fromDate, $toDate])
            ->select(
                'purchase_invoice_item_taxes.tax_name',
                'purchase_invoice_item_taxes.tax_rate',
                DB::raw('SUM((purchase_invoice_items.unit_price * purchase_invoice_items.quantity - purchase_invoice_items.discount_amount) * purchase_invoice_item_taxes.tax_rate / 100) as tax_amount')
            )
            ->groupBy('purchase_invoice_item_taxes.tax_name', 'purchase_invoice_item_taxes.tax_rate')
            ->get();

        $totalCollected = $taxCollected->sum('tax_amount');
        $totalPaid = $taxPaid->sum('tax_amount');

        return [
            'tax_collected' => [
                'items' => $taxCollected->map(fn($t) => [
                    'tax_name' => $t->tax_name . ' (' . $t->tax_rate . '%)',
                    'amount' => $t->tax_amount
                ]),
                'total' => $totalCollected
            ],
            'tax_paid' => [
                'items' => $taxPaid->map(fn($t) => [
                    'tax_name' => $t->tax_name . ' (' . $t->tax_rate . '%)',
                    'amount' => $t->tax_amount
                ]),
                'total' => $totalPaid
            ],
            'net_tax_liability' => $totalCollected - $totalPaid,
            'from_date' => $fromDate,
            'to_date' => $toDate
        ];
    }

    public function getMozambiqueFiscalMap($filters = [])
    {
        $fromDate = $filters['from_date'] ?? date('Y-01-01');
        $toDate = $filters['to_date'] ?? date('Y-12-31');

        $salesBaseQuery = DB::table('sales_invoices')
            ->where('created_by', creatorId())
            ->whereIn('status', ['posted', 'partial', 'paid'])
            ->whereBetween('invoice_date', [$fromDate, $toDate]);

        $purchaseBaseQuery = DB::table('purchase_invoices')
            ->where('created_by', creatorId())
            ->whereIn('status', ['posted', 'partial', 'paid'])
            ->whereBetween('invoice_date', [$fromDate, $toDate]);

        $creditNoteBaseQuery = DB::table('credit_notes')
            ->where('created_by', creatorId())
            ->where('status', 'approved')
            ->whereBetween('credit_note_date', [$fromDate, $toDate]);

        $debitNoteBaseQuery = DB::table('debit_notes')
            ->where('created_by', creatorId())
            ->where('status', 'approved')
            ->whereBetween('debit_note_date', [$fromDate, $toDate]);

        $posSummary = (object) [
            'documents' => 0,
            'taxable_base' => 0.0,
            'tax_amount' => 0.0,
            'total_amount' => 0.0,
        ];
        $posFiscalStatus = [];
        if (Schema::hasTable('pos') && Schema::hasTable('pos_items') && Schema::hasColumn('pos', 'pos_date')) {
            $posBaseQuery = DB::table('pos')
                ->where('pos.created_by', creatorId())
                ->whereBetween('pos.pos_date', [$fromDate, $toDate]);

            if (Schema::hasColumn('pos', 'status')) {
                $posBaseQuery->where('pos.status', 'completed');
            }

            if (Schema::hasColumn('pos', 'is_cancelled')) {
                $posBaseQuery->where('pos.is_cancelled', false);
            }

            $posSummary = (clone $posBaseQuery)
                ->join('pos_items', 'pos.id', '=', 'pos_items.pos_id')
                ->selectRaw('COUNT(DISTINCT pos.id) as documents, COALESCE(SUM(pos_items.subtotal),0) as taxable_base, COALESCE(SUM(pos_items.tax_amount),0) as tax_amount, COALESCE(SUM(pos_items.total_amount),0) as total_amount')
                ->first();

            if (Schema::hasColumn('pos', 'fiscal_submission_status')) {
                $posFiscalStatus = (clone $posBaseQuery)
                    ->select('pos.fiscal_submission_status', DB::raw('COUNT(*) as total'))
                    ->groupBy('pos.fiscal_submission_status')
                    ->pluck('total', 'pos.fiscal_submission_status')
                    ->toArray();
            }
        }

        $salesSummary = (clone $salesBaseQuery)
            ->selectRaw('COUNT(*) as documents, COALESCE(SUM(subtotal),0) as taxable_base, COALESCE(SUM(tax_amount),0) as tax_amount, COALESCE(SUM(total_amount),0) as total_amount')
            ->first();

        $purchaseSummary = (clone $purchaseBaseQuery)
            ->selectRaw('COUNT(*) as documents, COALESCE(SUM(subtotal),0) as taxable_base, COALESCE(SUM(tax_amount),0) as tax_amount, COALESCE(SUM(total_amount),0) as total_amount')
            ->first();

        $creditNoteSummary = (clone $creditNoteBaseQuery)
            ->selectRaw('COUNT(*) as documents, COALESCE(SUM(subtotal),0) as taxable_base, COALESCE(SUM(tax_amount),0) as tax_amount, COALESCE(SUM(total_amount),0) as total_amount')
            ->first();

        $debitNoteSummary = (clone $debitNoteBaseQuery)
            ->selectRaw('COUNT(*) as documents, COALESCE(SUM(subtotal),0) as taxable_base, COALESCE(SUM(tax_amount),0) as tax_amount, COALESCE(SUM(total_amount),0) as total_amount')
            ->first();

        $salesFiscalStatus = [];
        if (Schema::hasColumn('sales_invoices', 'fiscal_submission_status')) {
            $salesFiscalStatus = (clone $salesBaseQuery)
                ->select('fiscal_submission_status', DB::raw('COUNT(*) as total'))
                ->groupBy('fiscal_submission_status')
                ->pluck('total', 'fiscal_submission_status')
                ->toArray();
        }

        $purchaseFiscalStatus = [];
        if (Schema::hasColumn('purchase_invoices', 'fiscal_submission_status')) {
            $purchaseFiscalStatus = (clone $purchaseBaseQuery)
                ->select('fiscal_submission_status', DB::raw('COUNT(*) as total'))
                ->groupBy('fiscal_submission_status')
                ->pluck('total', 'fiscal_submission_status')
                ->toArray();
        }

        $outputVat = max(
            ((float) $salesSummary->tax_amount + (float) ($posSummary->tax_amount ?? 0))
                - (float) $creditNoteSummary->tax_amount,
            0.0
        );
        $inputVat = max((float) $purchaseSummary->tax_amount - (float) $debitNoteSummary->tax_amount, 0.0);
        $nonDeductibleInputVat = $this->getNonDeductibleInputVatTotal($fromDate, $toDate);
        $deductibleInputVat = max($inputVat - $nonDeductibleInputVat, 0.0);

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'sales' => [
                'documents' => (int) $salesSummary->documents,
                'taxable_base' => (float) $salesSummary->taxable_base,
                'tax_amount' => (float) $salesSummary->tax_amount,
                'total_amount' => (float) $salesSummary->total_amount,
            ],
            'pos_sales' => [
                'documents' => (int) ($posSummary->documents ?? 0),
                'taxable_base' => (float) ($posSummary->taxable_base ?? 0),
                'tax_amount' => (float) ($posSummary->tax_amount ?? 0),
                'total_amount' => (float) ($posSummary->total_amount ?? 0),
            ],
            'purchases' => [
                'documents' => (int) $purchaseSummary->documents,
                'taxable_base' => (float) $purchaseSummary->taxable_base,
                'tax_amount' => (float) $purchaseSummary->tax_amount,
                'total_amount' => (float) $purchaseSummary->total_amount,
            ],
            'credit_notes' => [
                'documents' => (int) $creditNoteSummary->documents,
                'taxable_base' => (float) $creditNoteSummary->taxable_base,
                'tax_amount' => (float) $creditNoteSummary->tax_amount,
                'total_amount' => (float) $creditNoteSummary->total_amount,
            ],
            'debit_notes' => [
                'documents' => (int) $debitNoteSummary->documents,
                'taxable_base' => (float) $debitNoteSummary->taxable_base,
                'tax_amount' => (float) $debitNoteSummary->tax_amount,
                'total_amount' => (float) $debitNoteSummary->total_amount,
            ],
            'vat' => [
                'output_vat' => $outputVat,
                'input_vat' => $inputVat,
                'input_vat_deductible' => $deductibleInputVat,
                'input_vat_non_deductible' => $nonDeductibleInputVat,
                'net_vat_payable' => $outputVat - $inputVat,
            ],
            'fiscal_status' => [
                'sales' => $salesFiscalStatus,
                'pos' => $posFiscalStatus,
                'purchases' => $purchaseFiscalStatus,
            ],
            'tax_account_mapping' => $this->getActiveMozambiqueTaxAccountMapping($toDate),
        ];
    }

    public function getMozambiqueVatDeclaration($filters = []): array
    {
        $fromDate = $filters['from_date'] ?? date('Y-01-01');
        $toDate = $filters['to_date'] ?? date('Y-12-31');
        $driver = DB::connection()->getDriverName();

        $salesPeriodExpression = $driver === 'sqlite'
            ? "strftime('%Y-%m', invoice_date)"
            : "DATE_FORMAT(invoice_date, '%Y-%m')";
        $purchasePeriodExpression = $driver === 'sqlite'
            ? "strftime('%Y-%m', invoice_date)"
            : "DATE_FORMAT(invoice_date, '%Y-%m')";
        $creditNotePeriodExpression = $driver === 'sqlite'
            ? "strftime('%Y-%m', credit_note_date)"
            : "DATE_FORMAT(credit_note_date, '%Y-%m')";
        $debitNotePeriodExpression = $driver === 'sqlite'
            ? "strftime('%Y-%m', debit_note_date)"
            : "DATE_FORMAT(debit_note_date, '%Y-%m')";
        $posPeriodExpression = $driver === 'sqlite'
            ? "strftime('%Y-%m', pos.pos_date)"
            : "DATE_FORMAT(pos.pos_date, '%Y-%m')";

        $salesByMonth = DB::table('sales_invoices')
            ->where('created_by', creatorId())
            ->whereIn('status', ['posted', 'partial', 'paid'])
            ->whereBetween('invoice_date', [$fromDate, $toDate])
            ->selectRaw("{$salesPeriodExpression} as period, COALESCE(SUM(tax_amount),0) as amount")
            ->groupBy('period')
            ->pluck('amount', 'period');

        $purchasesByMonth = DB::table('purchase_invoices')
            ->where('created_by', creatorId())
            ->whereIn('status', ['posted', 'partial', 'paid'])
            ->whereBetween('invoice_date', [$fromDate, $toDate])
            ->selectRaw("{$purchasePeriodExpression} as period, COALESCE(SUM(tax_amount),0) as amount")
            ->groupBy('period')
            ->pluck('amount', 'period');

        $creditNotesByMonth = DB::table('credit_notes')
            ->where('created_by', creatorId())
            ->where('status', 'approved')
            ->whereBetween('credit_note_date', [$fromDate, $toDate])
            ->selectRaw("{$creditNotePeriodExpression} as period, COALESCE(SUM(tax_amount),0) as amount")
            ->groupBy('period')
            ->pluck('amount', 'period');

        $debitNotesByMonth = DB::table('debit_notes')
            ->where('created_by', creatorId())
            ->where('status', 'approved')
            ->whereBetween('debit_note_date', [$fromDate, $toDate])
            ->selectRaw("{$debitNotePeriodExpression} as period, COALESCE(SUM(tax_amount),0) as amount")
            ->groupBy('period')
            ->pluck('amount', 'period');

        $posByMonth = collect();
        if (Schema::hasTable('pos') && Schema::hasTable('pos_items') && Schema::hasColumn('pos', 'pos_date')) {
            $posByMonthQuery = DB::table('pos')
                ->join('pos_items', 'pos.id', '=', 'pos_items.pos_id')
                ->where('pos.created_by', creatorId())
                ->whereBetween('pos.pos_date', [$fromDate, $toDate]);

            if (Schema::hasColumn('pos', 'status')) {
                $posByMonthQuery->where('pos.status', 'completed');
            }

            if (Schema::hasColumn('pos', 'is_cancelled')) {
                $posByMonthQuery->where('pos.is_cancelled', false);
            }

            $posByMonth = $posByMonthQuery
                ->selectRaw("{$posPeriodExpression} as period, COALESCE(SUM(pos_items.tax_amount),0) as amount")
                ->groupBy('period')
                ->pluck('amount', 'period');
        }

        $nonDeductibleByMonth = $this->getNonDeductibleInputVatByMonth($fromDate, $toDate, $purchasePeriodExpression);

        $monthlyRows = [];
        $cursor = \Carbon\Carbon::parse($fromDate)->startOfMonth();
        $end = \Carbon\Carbon::parse($toDate)->startOfMonth();

        $totals = [
            'sales_vat' => 0.0,
            'pos_vat' => 0.0,
            'purchase_vat' => 0.0,
            'credit_notes_vat' => 0.0,
            'debit_notes_vat' => 0.0,
            'output_vat' => 0.0,
            'input_vat' => 0.0,
            'deductible_input_vat' => 0.0,
            'non_deductible_input_vat' => 0.0,
            'net_vat_payable' => 0.0,
        ];

        while ($cursor->lte($end)) {
            $period = $cursor->format('Y-m');
            $salesVat = (float) ($salesByMonth[$period] ?? 0);
            $posVat = (float) ($posByMonth[$period] ?? 0);
            $purchaseVat = (float) ($purchasesByMonth[$period] ?? 0);
            $creditNotesVat = (float) ($creditNotesByMonth[$period] ?? 0);
            $debitNotesVat = (float) ($debitNotesByMonth[$period] ?? 0);
            $outputVat = max(($salesVat + $posVat) - $creditNotesVat, 0.0);
            $inputVat = max($purchaseVat - $debitNotesVat, 0.0);
            $nonDeductibleInputVat = min((float) ($nonDeductibleByMonth[$period] ?? 0), $inputVat);
            $deductibleInputVat = max($inputVat - $nonDeductibleInputVat, 0.0);
            $netVatPayable = $outputVat - $inputVat;

            $monthlyRows[] = [
                'period' => $period,
                'sales_vat' => $salesVat,
                'pos_vat' => $posVat,
                'purchase_vat' => $purchaseVat,
                'credit_notes_vat' => $creditNotesVat,
                'debit_notes_vat' => $debitNotesVat,
                'output_vat' => $outputVat,
                'input_vat' => $inputVat,
                'deductible_input_vat' => $deductibleInputVat,
                'non_deductible_input_vat' => $nonDeductibleInputVat,
                'net_vat_payable' => $netVatPayable,
            ];

            $totals['sales_vat'] += $salesVat;
            $totals['pos_vat'] += $posVat;
            $totals['purchase_vat'] += $purchaseVat;
            $totals['credit_notes_vat'] += $creditNotesVat;
            $totals['debit_notes_vat'] += $debitNotesVat;
            $totals['output_vat'] += $outputVat;
            $totals['input_vat'] += $inputVat;
            $totals['deductible_input_vat'] += $deductibleInputVat;
            $totals['non_deductible_input_vat'] += $nonDeductibleInputVat;
            $totals['net_vat_payable'] += $netVatPayable;

            $cursor->addMonth();
        }

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'totals' => $totals,
            'monthly' => $monthlyRows,
        ];
    }

    public function getMozambiqueFiscalSubmissionRegister($filters = []): array
    {
        $fromDate = $filters['from_date'] ?? date('Y-01-01');
        $toDate = $filters['to_date'] ?? date('Y-12-31');
        $driver = DB::connection()->getDriverName();

        $periodExpressionFor = static function (string $column) use ($driver): string {
            return $driver === 'sqlite'
                ? "strftime('%Y-%m', {$column})"
                : "DATE_FORMAT({$column}, '%Y-%m')";
        };

        $sources = [
            ['table' => 'sales_invoices', 'date_column' => 'invoice_date', 'group' => 'sales_invoices'],
            ['table' => 'purchase_invoices', 'date_column' => 'invoice_date', 'group' => 'purchase_invoices'],
            ['table' => 'sales_invoice_returns', 'date_column' => 'return_date', 'group' => 'sales_returns'],
            ['table' => 'purchase_returns', 'date_column' => 'return_date', 'group' => 'purchase_returns'],
            ['table' => 'pos', 'date_column' => 'pos_date', 'group' => 'pos_sales'],
        ];

        $rows = [];
        $summaryByStatus = [];
        $includedSources = [];

        foreach ($sources as $source) {
            $table = $source['table'];
            $dateColumn = $source['date_column'];

            if (
                !Schema::hasTable($table)
                || !Schema::hasColumn($table, 'fiscal_submission_status')
                || !Schema::hasColumn($table, $dateColumn)
            ) {
                continue;
            }

            $includedSources[] = $source['group'];

            $periodExpression = $periodExpressionFor($dateColumn);
            $records = DB::table($table)
                ->where('created_by', creatorId())
                ->whereBetween($dateColumn, [$fromDate, $toDate])
                ->whereNotNull('fiscal_submission_status')
                ->selectRaw("{$periodExpression} as period, fiscal_submission_status, COUNT(*) as total")
                ->groupBy('period', 'fiscal_submission_status')
                ->orderBy('period')
                ->get();

            foreach ($records as $record) {
                $status = (string) $record->fiscal_submission_status;
                $total = (int) $record->total;

                $rows[] = [
                    'period' => (string) $record->period,
                    'document_group' => $source['group'],
                    'fiscal_status' => $status,
                    'total' => $total,
                ];

                $summaryByStatus[$status] = ($summaryByStatus[$status] ?? 0) + $total;
            }
        }

        usort($rows, function (array $a, array $b): int {
            return [$a['period'], $a['document_group'], $a['fiscal_status']]
                <=> [$b['period'], $b['document_group'], $b['fiscal_status']];
        });

        ksort($summaryByStatus);

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'sources_included' => $includedSources,
            'summary_by_status' => $summaryByStatus,
            'rows' => $rows,
        ];
    }

    private function getNonDeductibleInputVatTotal(string $fromDate, string $toDate): float
    {
        return (float) collect(
            $this->getNonDeductibleInputVatByMonth($fromDate, $toDate)
        )->sum();
    }

    /**
     * @return array<string, float>
     */
    private function getNonDeductibleInputVatByMonth(
        string $fromDate,
        string $toDate,
        ?string $periodExpression = null
    ): array {
        if (!Schema::hasTable('purchase_invoices')) {
            return [];
        }

        $driver = DB::connection()->getDriverName();
        $periodExpression = $periodExpression ?: (
            $driver === 'sqlite'
                ? "strftime('%Y-%m', pi.invoice_date)"
                : "DATE_FORMAT(pi.invoice_date, '%Y-%m')"
        );

        $query = DB::table('purchase_invoices as pi')
            ->where('pi.created_by', creatorId())
            ->whereIn('pi.status', ['posted', 'partial', 'paid'])
            ->whereBetween('pi.invoice_date', [$fromDate, $toDate])
            ->selectRaw("{$periodExpression} as period, pi.tax_amount, pi.counterparty_snapshot, pi.vendor_id");

        if (Schema::hasTable('vendors') && Schema::hasColumn('vendors', 'tax_number')) {
            $query->leftJoin('vendors as v', function ($join) {
                $join->on('v.user_id', '=', 'pi.vendor_id')
                    ->on('v.created_by', '=', 'pi.created_by');
            });
            $query->addSelect('v.tax_number as vendor_tax_number');
        }

        $rows = $query->get();

        $invalidByMonth = [];
        foreach ($rows as $row) {
            $period = (string) ($row->period ?? '');
            if ($period === '') {
                continue;
            }

            $snapshot = $this->normaliseSnapshot($row->counterparty_snapshot ?? null);
            $candidateNuit = (string) (
                $row->vendor_tax_number
                ?? data_get($snapshot, 'tax_number')
                ?? ''
            );

            if (MozambiqueTaxNumber::isValidNuit($candidateNuit)) {
                continue;
            }

            $invalidByMonth[$period] = ($invalidByMonth[$period] ?? 0.0) + (float) ($row->tax_amount ?? 0);
        }

        return $invalidByMonth;
    }

    public function buildMozambiqueSaftXml(array $filters = []): string
    {
        if (!class_exists(\XMLWriter::class)) {
            throw new \RuntimeException('XMLWriter extension is not available on this server.');
        }

        $fromDate = $filters['from_date'] ?? date('Y-01-01');
        $toDate = $filters['to_date'] ?? date('Y-12-31');
        $companySettings = getCompanyAllSetting(creatorId());

        $salesInvoiceColumns = array_merge([
            'id',
            'invoice_number',
            'invoice_date',
            'due_date',
            'status',
            'customer_id',
            'subtotal',
            'tax_amount',
            'discount_amount',
            'total_amount',
            'created_at',
            'updated_at',
        ], $this->existingColumns('sales_invoices', [
            'document_type',
            'document_series',
            'document_sequence',
            'fiscal_submission_status',
            'fiscal_submission_reference',
            'fiscal_submitted_at',
            'fiscal_validated_at',
            'is_cancelled',
            'issuer_snapshot',
            'counterparty_snapshot',
        ]));

        $purchaseInvoiceColumns = array_merge([
            'id',
            'invoice_number',
            'invoice_date',
            'due_date',
            'status',
            'vendor_id',
            'subtotal',
            'tax_amount',
            'discount_amount',
            'total_amount',
            'created_at',
            'updated_at',
        ], $this->existingColumns('purchase_invoices', [
            'document_type',
            'document_series',
            'document_sequence',
            'fiscal_submission_status',
            'fiscal_submission_reference',
            'fiscal_submitted_at',
            'fiscal_validated_at',
            'is_cancelled',
            'issuer_snapshot',
            'counterparty_snapshot',
        ]));

        $salesInvoices = DB::table('sales_invoices')
            ->where('created_by', creatorId())
            ->whereIn('status', ['posted', 'partial', 'paid', 'cancelled'])
            ->whereBetween('invoice_date', [$fromDate, $toDate])
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get($salesInvoiceColumns);

        $purchaseInvoices = DB::table('purchase_invoices')
            ->where('created_by', creatorId())
            ->whereIn('status', ['posted', 'partial', 'paid', 'cancelled'])
            ->whereBetween('invoice_date', [$fromDate, $toDate])
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get($purchaseInvoiceColumns);

        $salesInvoiceItemColumns = array_merge([
            'invoice_id',
            'product_id',
            'quantity',
            'unit_price',
            'discount_amount',
            'tax_amount',
            'total_amount',
        ], $this->existingColumns('sales_invoice_items', [
            'description',
            'tax_percentage',
        ]));

        $purchaseInvoiceItemColumns = array_merge([
            'invoice_id',
            'product_id',
            'quantity',
            'unit_price',
            'discount_amount',
            'tax_amount',
            'total_amount',
        ], $this->existingColumns('purchase_invoice_items', [
            'description',
            'tax_percentage',
        ]));

        $salesInvoiceItems = collect();
        if (Schema::hasTable('sales_invoice_items')) {
            $salesInvoiceItems = DB::table('sales_invoice_items')
                ->whereIn('invoice_id', $salesInvoices->pluck('id')->all())
                ->orderBy('invoice_id')
                ->orderBy('id')
                ->get($salesInvoiceItemColumns)
                ->groupBy('invoice_id');
        }

        $purchaseInvoiceItems = collect();
        if (Schema::hasTable('purchase_invoice_items')) {
            $purchaseInvoiceItems = DB::table('purchase_invoice_items')
                ->whereIn('invoice_id', $purchaseInvoices->pluck('id')->all())
                ->orderBy('invoice_id')
                ->orderBy('id')
                ->get($purchaseInvoiceItemColumns)
                ->groupBy('invoice_id');
        }

        $customerUsers = DB::table('users')
            ->whereIn('id', $salesInvoices->pluck('customer_id')->filter()->all())
            ->select('id', 'name', 'email')
            ->get()
            ->keyBy('id');

        $vendorUsers = DB::table('users')
            ->whereIn('id', $purchaseInvoices->pluck('vendor_id')->filter()->all())
            ->select('id', 'name', 'email')
            ->get()
            ->keyBy('id');

        $customerTaxNumbers = collect();
        if (Schema::hasTable('customers')) {
            $customerTaxNumbers = DB::table('customers')
                ->where('created_by', creatorId())
                ->whereIn('user_id', $salesInvoices->pluck('customer_id')->filter()->all())
                ->pluck('tax_number', 'user_id');
        }

        $vendorTaxNumbers = collect();
        if (Schema::hasTable('vendors')) {
            $vendorTaxNumbers = DB::table('vendors')
                ->where('created_by', creatorId())
                ->whereIn('user_id', $purchaseInvoices->pluck('vendor_id')->filter()->all())
                ->pluck('tax_number', 'user_id');
        }

        $customerMaster = [];
        foreach ($salesInvoices as $invoice) {
            $customerId = (int) ($invoice->customer_id ?? 0);
            if ($customerId <= 0) {
                continue;
            }

            if (!isset($customerMaster[$customerId])) {
                $counterpartySnapshot = $this->normaliseSnapshot($invoice->counterparty_snapshot ?? null);
                $customerUser = $customerUsers->get($customerId);

                $customerMaster[$customerId] = [
                    'id' => $customerId,
                    'name' => $this->pickFirstNonEmpty([
                        data_get($counterpartySnapshot, 'company_name'),
                        data_get($counterpartySnapshot, 'name'),
                        $customerUser->name ?? null,
                    ], 'Customer ' . $customerId),
                    'email' => $this->pickFirstNonEmpty([
                        data_get($counterpartySnapshot, 'primary_email'),
                        data_get($counterpartySnapshot, 'email'),
                        $customerUser->email ?? null,
                    ], ''),
                    'tax_number' => $this->normaliseTaxNumber(
                        $this->pickFirstNonEmpty([
                            data_get($counterpartySnapshot, 'tax_number'),
                            $customerTaxNumbers[$customerId] ?? null,
                        ], '')
                    ),
                ];
            }
        }

        $supplierMaster = [];
        foreach ($purchaseInvoices as $invoice) {
            $vendorId = (int) ($invoice->vendor_id ?? 0);
            if ($vendorId <= 0) {
                continue;
            }

            if (!isset($supplierMaster[$vendorId])) {
                $counterpartySnapshot = $this->normaliseSnapshot($invoice->counterparty_snapshot ?? null);
                $vendorUser = $vendorUsers->get($vendorId);

                $supplierMaster[$vendorId] = [
                    'id' => $vendorId,
                    'name' => $this->pickFirstNonEmpty([
                        data_get($counterpartySnapshot, 'company_name'),
                        data_get($counterpartySnapshot, 'name'),
                        $vendorUser->name ?? null,
                    ], 'Supplier ' . $vendorId),
                    'email' => $this->pickFirstNonEmpty([
                        data_get($counterpartySnapshot, 'primary_email'),
                        data_get($counterpartySnapshot, 'email'),
                        $vendorUser->email ?? null,
                    ], ''),
                    'tax_number' => $this->normaliseTaxNumber(
                        $this->pickFirstNonEmpty([
                            data_get($counterpartySnapshot, 'tax_number'),
                            $vendorTaxNumbers[$vendorId] ?? null,
                        ], '')
                    ),
                ];
            }
        }

        $issuerSnapshot = $this->resolveIssuerSnapshotFromInvoices($salesInvoices, $purchaseInvoices);
        $companyTaxNumber = $this->normaliseTaxNumber(
            $this->pickFirstNonEmpty([
                $companySettings['company_tax_number'] ?? null,
                $companySettings['vat_number'] ?? null,
                data_get($issuerSnapshot, 'tax_number'),
            ], '')
        );
        $companyName = $this->pickFirstNonEmpty([
            $companySettings['company_name'] ?? null,
            data_get($issuerSnapshot, 'company_name'),
            config('app.name'),
        ], config('app.name'));
        $companyAddress = $this->pickFirstNonEmpty([
            $companySettings['company_address'] ?? null,
            data_get($issuerSnapshot, 'company_address'),
        ], '');
        $companyPhone = $this->pickFirstNonEmpty([
            $companySettings['company_telephone'] ?? null,
            data_get($issuerSnapshot, 'company_telephone'),
        ], '');
        $companyEmail = $this->pickFirstNonEmpty([
            $companySettings['company_email'] ?? null,
            data_get($issuerSnapshot, 'company_email'),
        ], '');

        $writer = new \XMLWriter();
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->setIndent(true);

        $writer->startElement('AuditFile');
        $writer->writeAttribute('xmlns', 'urn:OECD:StandardAuditFile-Tax:PT_1.04_01');

        $writer->startElement('Header');
        $writer->writeElement('AuditFileVersion', '1.0-MZ-DRAFT');
        $writer->writeElement('CompanyID', (string) $companyTaxNumber);
        $writer->writeElement('TaxRegistrationNumber', (string) $companyTaxNumber);
        $writer->writeElement('CompanyName', (string) $companyName);
        $writer->writeElement('BusinessName', (string) $companyName);
        $writer->writeElement('CompanyAddress', (string) $companyAddress);
        $writer->writeElement('FiscalYear', (string) date('Y', strtotime($fromDate)));
        $writer->writeElement('StartDate', $fromDate);
        $writer->writeElement('EndDate', $toDate);
        $writer->writeElement('CurrencyCode', (string) ($companySettings['defaultCurrency'] ?? 'MZN'));
        $writer->writeElement('DateCreated', now()->toDateString());
        $writer->writeElement('TaxEntity', 'MZ');
        $writer->writeElement('ProductCompanyTaxID', (string) $companyTaxNumber);
        $writer->writeElement('SoftwareCertificateNumber', 'N/A');
        $writer->writeElement('ProductID', 'IndicoERP');
        $writer->writeElement('ProductVersion', (string) app()->version());
        $writer->writeElement('Telephone', (string) $companyPhone);
        $writer->writeElement('Email', (string) $companyEmail);
        $writer->endElement();

        $writer->startElement('MasterFiles');
        foreach ($customerMaster as $customer) {
            $writer->startElement('Customer');
            $writer->writeElement('CustomerID', 'CUST-' . (string) $customer['id']);
            $writer->writeElement('AccountID', '1100');
            $writer->writeElement('CustomerTaxID', (string) $customer['tax_number']);
            $writer->writeElement('CompanyName', (string) $customer['name']);
            if ($customer['email'] !== '') {
                $writer->writeElement('Email', (string) $customer['email']);
            }
            $writer->endElement();
        }
        foreach ($supplierMaster as $supplier) {
            $writer->startElement('Supplier');
            $writer->writeElement('SupplierID', 'SUP-' . (string) $supplier['id']);
            $writer->writeElement('AccountID', '2000');
            $writer->writeElement('SupplierTaxID', (string) $supplier['tax_number']);
            $writer->writeElement('CompanyName', (string) $supplier['name']);
            if ($supplier['email'] !== '') {
                $writer->writeElement('Email', (string) $supplier['email']);
            }
            $writer->endElement();
        }
        $writer->endElement();

        $writer->startElement('SourceDocuments');

        $writer->startElement('SalesInvoices');
        $writer->writeElement('NumberOfEntries', (string) $salesInvoices->count());
        $writer->writeElement('TotalDebit', '0.00');
        $writer->writeElement('TotalCredit', number_format((float) $salesInvoices->sum('total_amount'), 2, '.', ''));

        foreach ($salesInvoices as $invoice) {
            $invoiceType = (string) ($invoice->document_type ?? 'FT');
            if ($invoiceType === '') {
                $invoiceType = 'FT';
            }

            $sourceIdentifier = $this->pickFirstNonEmpty([
                (string) ($invoice->customer_id ?? ''),
                (string) creatorId(),
            ], (string) creatorId());

            $writer->startElement('Invoice');
            $writer->writeElement('InvoiceNo', (string) $invoice->invoice_number);
            $writer->writeElement('InvoiceDate', (string) $invoice->invoice_date);
            $writer->writeElement('InvoiceType', $invoiceType);
            $writer->writeElement('CustomerID', 'CUST-' . (string) $invoice->customer_id);
            $writer->writeElement('SystemEntryDate', date('Y-m-d\TH:i:s', strtotime((string) ($invoice->created_at ?? $invoice->updated_at))));
            $writer->writeElement('SourceID', $sourceIdentifier);
            $writer->writeElement('SourceBilling', $this->mapSaftSourceBilling((string) ($invoice->fiscal_submission_status ?? 'pending')));

            $writer->startElement('DocumentStatus');
            $writer->writeElement('InvoiceStatus', $this->mapSaftInvoiceStatus($invoice->status, (bool) $invoice->is_cancelled));
            $writer->writeElement('InvoiceStatusDate', date('Y-m-d\TH:i:s', strtotime((string) ($invoice->updated_at ?? $invoice->created_at))));
            $writer->endElement();

            $lines = $salesInvoiceItems->get($invoice->id, collect());
            foreach ($lines->values() as $index => $line) {
                $lineNumber = (string) ($index + 1);
                $taxAmount = (float) ($line->tax_amount ?? 0);
                $lineNet = max(0, (float) $line->total_amount - $taxAmount);
                $taxPercent = isset($line->tax_percentage)
                    ? (float) $line->tax_percentage
                    : ($lineNet > 0 ? round(($taxAmount / $lineNet) * 100, 2) : 0.0);
                $productDescription = $this->pickFirstNonEmpty([
                    $line->description ?? null,
                    $line->product_id ? ('Item ' . (string) $line->product_id) : null,
                ], 'Item');

                $writer->startElement('Line');
                $writer->writeElement('LineNumber', $lineNumber);
                $writer->writeElement('ProductCode', (string) ($line->product_id ?? 'ITEM'));
                $writer->writeElement('ProductDescription', $productDescription);
                $writer->writeElement('Quantity', number_format((float) $line->quantity, 2, '.', ''));
                $writer->writeElement('UnitPrice', number_format((float) $line->unit_price, 2, '.', ''));
                $writer->writeElement('TaxPointDate', (string) $invoice->invoice_date);
                $writer->writeElement('CreditAmount', number_format((float) $lineNet, 2, '.', ''));

                $writer->startElement('Tax');
                $writer->writeElement('TaxType', 'IVA');
                $writer->writeElement('TaxCountryRegion', 'MZ');
                $writer->writeElement('TaxCode', 'NOR');
                $writer->writeElement('TaxPercentage', number_format($taxPercent, 2, '.', ''));
                $writer->endElement();

                $writer->endElement();
            }

            $writer->startElement('DocumentTotals');
            $writer->writeElement('TaxPayable', number_format((float) $invoice->tax_amount, 2, '.', ''));
            $writer->writeElement('NetTotal', number_format((float) $invoice->subtotal - (float) ($invoice->discount_amount ?? 0), 2, '.', ''));
            $writer->writeElement('GrossTotal', number_format((float) $invoice->total_amount, 2, '.', ''));
            $writer->endElement();

            $writer->endElement();
        }

        $writer->endElement();

        $writer->startElement('PurchaseInvoices');
        $writer->writeElement('NumberOfEntries', (string) $purchaseInvoices->count());
        $writer->writeElement('TotalDebit', number_format((float) $purchaseInvoices->sum('total_amount'), 2, '.', ''));
        $writer->writeElement('TotalCredit', '0.00');

        foreach ($purchaseInvoices as $invoice) {
            $invoiceType = (string) ($invoice->document_type ?? 'FC');
            if ($invoiceType === '') {
                $invoiceType = 'FC';
            }

            $sourceIdentifier = $this->pickFirstNonEmpty([
                (string) ($invoice->vendor_id ?? ''),
                (string) creatorId(),
            ], (string) creatorId());

            $writer->startElement('Invoice');
            $writer->writeElement('InvoiceNo', (string) $invoice->invoice_number);
            $writer->writeElement('InvoiceDate', (string) $invoice->invoice_date);
            $writer->writeElement('InvoiceType', $invoiceType);
            $writer->writeElement('SupplierID', 'SUP-' . (string) $invoice->vendor_id);
            $writer->writeElement('SystemEntryDate', date('Y-m-d\TH:i:s', strtotime((string) ($invoice->created_at ?? $invoice->updated_at))));
            $writer->writeElement('SourceID', $sourceIdentifier);
            $writer->writeElement('SourceBilling', $this->mapSaftSourceBilling((string) ($invoice->fiscal_submission_status ?? 'pending')));

            $writer->startElement('DocumentStatus');
            $writer->writeElement('InvoiceStatus', $this->mapSaftInvoiceStatus($invoice->status, (bool) $invoice->is_cancelled));
            $writer->writeElement('InvoiceStatusDate', date('Y-m-d\TH:i:s', strtotime((string) ($invoice->updated_at ?? $invoice->created_at))));
            $writer->endElement();

            $lines = $purchaseInvoiceItems->get($invoice->id, collect());
            foreach ($lines->values() as $index => $line) {
                $lineNumber = (string) ($index + 1);
                $taxAmount = (float) ($line->tax_amount ?? 0);
                $lineNet = max(0, (float) $line->total_amount - $taxAmount);
                $taxPercent = isset($line->tax_percentage)
                    ? (float) $line->tax_percentage
                    : ($lineNet > 0 ? round(($taxAmount / $lineNet) * 100, 2) : 0.0);
                $productDescription = $this->pickFirstNonEmpty([
                    $line->description ?? null,
                    $line->product_id ? ('Item ' . (string) $line->product_id) : null,
                ], 'Item');

                $writer->startElement('Line');
                $writer->writeElement('LineNumber', $lineNumber);
                $writer->writeElement('ProductCode', (string) ($line->product_id ?? 'ITEM'));
                $writer->writeElement('ProductDescription', $productDescription);
                $writer->writeElement('Quantity', number_format((float) $line->quantity, 2, '.', ''));
                $writer->writeElement('UnitPrice', number_format((float) $line->unit_price, 2, '.', ''));
                $writer->writeElement('TaxPointDate', (string) $invoice->invoice_date);
                $writer->writeElement('DebitAmount', number_format((float) $lineNet, 2, '.', ''));

                $writer->startElement('Tax');
                $writer->writeElement('TaxType', 'IVA');
                $writer->writeElement('TaxCountryRegion', 'MZ');
                $writer->writeElement('TaxCode', 'NOR');
                $writer->writeElement('TaxPercentage', number_format($taxPercent, 2, '.', ''));
                $writer->endElement();

                $writer->endElement();
            }

            $writer->startElement('DocumentTotals');
            $writer->writeElement('TaxPayable', number_format((float) $invoice->tax_amount, 2, '.', ''));
            $writer->writeElement('NetTotal', number_format((float) $invoice->subtotal - (float) ($invoice->discount_amount ?? 0), 2, '.', ''));
            $writer->writeElement('GrossTotal', number_format((float) $invoice->total_amount, 2, '.', ''));
            $writer->endElement();

            $writer->endElement();
        }

        $writer->endElement();
        $writer->endElement();
        $writer->endElement();
        $writer->endDocument();

        return $writer->outputMemory();
    }

    private function mapSaftSourceBilling(string $fiscalSubmissionStatus): string
    {
        $status = strtolower(trim($fiscalSubmissionStatus));

        if (in_array($status, ['submitted', 'validated'], true)) {
            return 'I';
        }

        return 'P';
    }

    private function mapSaftInvoiceStatus(?string $status, bool $isCancelled): string
    {
        if ($isCancelled || strtolower((string) $status) === 'cancelled') {
            return 'A';
        }

        return 'N';
    }

    private function existingColumns(string $table, array $columns): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        return array_values(array_filter(
            $columns,
            static fn(string $column): bool => Schema::hasColumn($table, $column)
        ));
    }

    private function normaliseSnapshot(mixed $snapshot): ?array
    {
        if (is_array($snapshot)) {
            return $snapshot;
        }

        if (is_string($snapshot) && $snapshot !== '') {
            $decoded = json_decode($snapshot, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function pickFirstNonEmpty(array $values, string $fallback = ''): string
    {
        foreach ($values as $value) {
            $candidate = trim((string) ($value ?? ''));
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return $fallback;
    }

    private function normaliseTaxNumber(?string $value): string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return '';
        }

        return preg_replace('/\s+/', '', $raw) ?? $raw;
    }

    private function resolveIssuerSnapshotFromInvoices($salesInvoices, $purchaseInvoices): ?array
    {
        foreach ([$salesInvoices, $purchaseInvoices] as $collection) {
            foreach ($collection as $invoice) {
                $snapshot = $this->normaliseSnapshot($invoice->issuer_snapshot ?? null);
                if ($snapshot !== null) {
                    return $snapshot;
                }
            }
        }

        return null;
    }

    private function requiresMozambicanNuitFromSettings(array $companySettings): bool
    {
        $taxType = strtoupper(trim((string) ($companySettings['tax_type'] ?? '')));
        if ($taxType === 'NUIT') {
            return true;
        }

        $country = mb_strtolower(trim((string) ($companySettings['company_country'] ?? '')));
        return str_contains($country, 'mozambique') || str_contains($country, 'moçambique');
    }

    private function isValidNuit(?string $taxNumber): bool
    {
        $digits = preg_replace('/\D+/', '', (string) ($taxNumber ?? '')) ?? '';
        return (bool) preg_match('/^\d{9}$/', $digits);
    }

    public function getMozambiqueGoLiveReadiness(): array
    {
        $today = now()->toDateString();
        $thirtyDaysAgo = now()->subDays(30)->toDateString();
        $checks = [];
        $summary = [
            'pass' => 0,
            'warn' => 0,
            'fail' => 0,
        ];

        $addCheck = function (
            string $code,
            string $label,
            string $status,
            string $details,
            bool $critical = false,
            array $meta = []
        ) use (&$checks, &$summary): void {
            if (!isset($summary[$status])) {
                return;
            }

            $summary[$status]++;
            $checks[] = [
                'code' => $code,
                'label' => $label,
                'status' => $status,
                'critical' => $critical,
                'details' => $details,
                'meta' => $meta,
            ];
        };

        $requiredTables = [
            'mz_tax_account_mappings',
            'mz_fiscal_closings',
            'mz_irps_tables',
            'mz_irps_brackets',
            'mz_inss_rates',
            'mz_minimum_wages',
            'customer_payments',
            'vendor_payments',
            'bank_transactions',
            'audit_trails',
        ];

        $missingTables = [];
        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                $missingTables[] = $table;
            }
        }

        if (empty($missingTables)) {
            $addCheck(
                'tables.localization',
                'Localization and compliance tables',
                'pass',
                'All required Mozambique and operational tables are available.',
                true
            );
        } else {
            $addCheck(
                'tables.localization',
                'Localization and compliance tables',
                'fail',
                'Missing required tables: ' . implode(', ', $missingTables),
                true,
                ['missing' => $missingTables]
            );
        }

        $companySettings = getCompanyAllSetting(creatorId());
        $requiresNuit = $this->requiresMozambicanNuitFromSettings($companySettings);
        $companyTaxNumber = (string) ($companySettings['company_tax_number'] ?? $companySettings['vat_number'] ?? '');
        $companyTaxNumberValid = $this->isValidNuit($companyTaxNumber);

        $companyTaxCheckStatus = 'pass';
        $companyTaxCheckDetails = 'Company tax number format is valid.';
        if ($requiresNuit && !$companyTaxNumberValid) {
            $companyTaxCheckStatus = 'fail';
            $companyTaxCheckDetails = 'Company NUIT is missing or invalid (expected 9 digits).';
        }

        $addCheck(
            'company.tax_number.format',
            'Company tax number (NUIT) format',
            $companyTaxCheckStatus,
            $companyTaxCheckDetails,
            $requiresNuit,
            [
                'requires_nuit' => $requiresNuit,
                'has_valid_nuit' => $companyTaxNumberValid,
            ]
        );

        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'tax_number')) {
            $invalidCustomerNuitCount = DB::table('customers')
                ->where('created_by', creatorId())
                ->whereNotNull('tax_number')
                ->where('tax_number', '!=', '')
                ->pluck('tax_number')
                ->filter(fn ($taxNumber): bool => !$this->isValidNuit((string) $taxNumber))
                ->count();

            $addCheck(
                'customers.tax_number.format',
                'Customer NUIT format',
                $invalidCustomerNuitCount === 0 ? 'pass' : ($requiresNuit ? 'fail' : 'warn'),
                $invalidCustomerNuitCount === 0
                    ? 'All customer tax numbers are valid.'
                    : "Found {$invalidCustomerNuitCount} customer tax number(s) with invalid NUIT format.",
                $requiresNuit,
                ['invalid_count' => $invalidCustomerNuitCount]
            );
        } else {
            $addCheck(
                'customers.tax_number.format',
                'Customer NUIT format',
                'warn',
                'Customer table or tax_number column is not available.'
            );
        }

        if (Schema::hasTable('vendors') && Schema::hasColumn('vendors', 'tax_number')) {
            $invalidVendorNuitCount = DB::table('vendors')
                ->where('created_by', creatorId())
                ->whereNotNull('tax_number')
                ->where('tax_number', '!=', '')
                ->pluck('tax_number')
                ->filter(fn ($taxNumber): bool => !$this->isValidNuit((string) $taxNumber))
                ->count();

            $addCheck(
                'vendors.tax_number.format',
                'Vendor NUIT format',
                $invalidVendorNuitCount === 0 ? 'pass' : ($requiresNuit ? 'fail' : 'warn'),
                $invalidVendorNuitCount === 0
                    ? 'All vendor tax numbers are valid.'
                    : "Found {$invalidVendorNuitCount} vendor tax number(s) with invalid NUIT format.",
                $requiresNuit,
                ['invalid_count' => $invalidVendorNuitCount]
            );
        } else {
            $addCheck(
                'vendors.tax_number.format',
                'Vendor NUIT format',
                'warn',
                'Vendor table or tax_number column is not available.'
            );
        }

        if (Schema::hasTable('mz_tax_account_mappings')) {
            $mappingExists = DB::table('mz_tax_account_mappings')
                ->where('created_by', creatorId())
                ->where('is_active', true)
                ->whereDate('effective_from', '<=', $today)
                ->where(function ($query) use ($today) {
                    $query->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', $today);
                })
                ->exists();

            $addCheck(
                'tax.mapping.active',
                'Active Mozambique tax account mapping',
                $mappingExists ? 'pass' : 'fail',
                $mappingExists
                    ? 'At least one active tax mapping exists for current date.'
                    : 'No active tax mapping found for current date.',
                true
            );
        } else {
            $addCheck(
                'tax.mapping.active',
                'Active Mozambique tax account mapping',
                'warn',
                'Tax mapping table is not available.'
            );
        }

        if (
            Schema::hasTable('mz_irps_tables')
            && Schema::hasTable('mz_irps_brackets')
            && Schema::hasTable('mz_inss_rates')
            && Schema::hasTable('mz_minimum_wages')
        ) {
            $activeIrpsTableId = DB::table('mz_irps_tables')
                ->where('created_by', creatorId())
                ->where('is_active', true)
                ->whereDate('effective_from', '<=', $today)
                ->where(function ($query) use ($today) {
                    $query->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', $today);
                })
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->value('id');

            $irpsBracketsCount = $activeIrpsTableId
                ? DB::table('mz_irps_brackets')->where('irps_table_id', $activeIrpsTableId)->count()
                : 0;

            $hasActiveInss = DB::table('mz_inss_rates')
                ->where('created_by', creatorId())
                ->where('is_active', true)
                ->whereDate('effective_from', '<=', $today)
                ->where(function ($query) use ($today) {
                    $query->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', $today);
                })
                ->exists();

            $hasActiveMinimumWage = DB::table('mz_minimum_wages')
                ->where('created_by', creatorId())
                ->where('is_active', true)
                ->whereDate('effective_from', '<=', $today)
                ->where(function ($query) use ($today) {
                    $query->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', $today);
                })
                ->exists();

            $missingPayrollItems = [];
            if (!$activeIrpsTableId) {
                $missingPayrollItems[] = 'IRPS active table';
            }
            if ($irpsBracketsCount <= 0) {
                $missingPayrollItems[] = 'IRPS brackets';
            }
            if (!$hasActiveInss) {
                $missingPayrollItems[] = 'INSS active rates';
            }
            if (!$hasActiveMinimumWage) {
                $missingPayrollItems[] = 'minimum wage active records';
            }

            $addCheck(
                'payroll.legal.setup',
                'Payroll legal setup (IRPS, INSS, minimum wage)',
                empty($missingPayrollItems) ? 'pass' : 'fail',
                empty($missingPayrollItems)
                    ? 'Payroll legal tables are configured with active records.'
                    : 'Missing payroll legal setup items: ' . implode(', ', $missingPayrollItems),
                true,
                [
                    'active_irps_table_id' => $activeIrpsTableId,
                    'irps_brackets_count' => $irpsBracketsCount,
                    'has_active_inss' => $hasActiveInss,
                    'has_active_minimum_wage' => $hasActiveMinimumWage,
                ]
            );
        } else {
            $addCheck(
                'payroll.legal.setup',
                'Payroll legal setup (IRPS, INSS, minimum wage)',
                'warn',
                'Payroll compliance tables are not fully available.'
            );
        }

        if (Schema::hasTable('settings')) {
            $policyKeys = [
                'mz_leave_min_notice_days',
                'mz_leave_count_non_working_days',
                'mz_leave_count_holidays',
            ];

            $configuredPolicyKeys = DB::table('settings')
                ->where('created_by', creatorId())
                ->whereIn('key', $policyKeys)
                ->pluck('key')
                ->all();

            $missingPolicyKeys = array_values(array_diff($policyKeys, $configuredPolicyKeys));

            $addCheck(
                'payroll.labour.rules',
                'Payroll labour rules (overtime/leaves)',
                empty($missingPolicyKeys) ? 'pass' : 'warn',
                empty($missingPolicyKeys)
                    ? 'Overtime/leave labour policy settings are configured.'
                    : 'Missing labour policy settings: ' . implode(', ', $missingPolicyKeys),
                false,
                ['missing_policy_keys' => $missingPolicyKeys]
            );
        } else {
            $addCheck(
                'payroll.labour.rules',
                'Payroll labour rules (overtime/leaves)',
                'warn',
                'Settings table is not available.'
            );
        }

        if (Schema::hasTable('mz_fiscal_closings')) {
            $closedPeriods = DB::table('mz_fiscal_closings')
                ->where('created_by', creatorId())
                ->where('status', 'closed')
                ->count();

            $addCheck(
                'fiscal.closing.history',
                'Fiscal closing history',
                $closedPeriods > 0 ? 'pass' : 'warn',
                $closedPeriods > 0
                    ? 'At least one closed fiscal period exists.'
                    : 'No closed fiscal period found yet. Close at least one period before go-live.',
                false,
                ['closed_periods' => $closedPeriods]
            );
        } else {
            $addCheck(
                'fiscal.closing.history',
                'Fiscal closing history',
                'warn',
                'Fiscal closing table is not available.'
            );
        }

        if (
            Schema::hasTable('sales_invoices')
            && Schema::hasTable('purchase_invoices')
            && Schema::hasColumn('sales_invoices', 'fiscal_submission_status')
            && Schema::hasColumn('purchase_invoices', 'fiscal_submission_status')
        ) {
            $salesBacklog = DB::table('sales_invoices')
                ->where('created_by', creatorId())
                ->whereIn('fiscal_submission_status', ['pending', 'rejected'])
                ->count();

            $purchaseBacklog = DB::table('purchase_invoices')
                ->where('created_by', creatorId())
                ->whereIn('fiscal_submission_status', ['pending', 'rejected'])
                ->count();

            $posBacklog = 0;
            if (Schema::hasTable('pos') && Schema::hasColumn('pos', 'fiscal_submission_status')) {
                $posBacklogQuery = DB::table('pos')
                    ->where('created_by', creatorId())
                    ->whereIn('fiscal_submission_status', ['pending', 'rejected']);

                if (Schema::hasColumn('pos', 'is_cancelled')) {
                    $posBacklogQuery->where('is_cancelled', false);
                }

                $posBacklog = $posBacklogQuery->count();
            }

            $totalBacklog = $salesBacklog + $purchaseBacklog + $posBacklog;

            $addCheck(
                'fiscal.submission.backlog',
                'Fiscal submission backlog',
                $totalBacklog === 0 ? 'pass' : 'warn',
                $totalBacklog === 0
                    ? 'No pending/rejected fiscal submissions detected.'
                    : "Pending/rejected fiscal submissions detected: {$totalBacklog}.",
                false,
                [
                    'sales_backlog' => $salesBacklog,
                    'pos_backlog' => $posBacklog,
                    'purchase_backlog' => $purchaseBacklog,
                ]
            );
        } else {
            $addCheck(
                'fiscal.submission.backlog',
                'Fiscal submission backlog',
                'warn',
                'Fiscal submission status columns are not available.'
            );
        }

        if (Schema::hasTable('pos')) {
            $requiredPosFiscalColumns = [
                'document_type',
                'document_series',
                'document_sequence',
                'fiscal_submission_status',
                'fiscal_submission_reference',
                'is_cancelled',
                'cancelled_at',
                'cancellation_reason',
            ];

            $missingPosFiscalColumns = array_values(array_filter(
                $requiredPosFiscalColumns,
                static fn (string $column): bool => !Schema::hasColumn('pos', $column)
            ));

            $addCheck(
                'pos.fiscal.columns',
                'POS fiscal compliance columns',
                empty($missingPosFiscalColumns) ? 'pass' : 'warn',
                empty($missingPosFiscalColumns)
                    ? 'POS fiscal columns are configured.'
                    : 'Missing POS fiscal columns: ' . implode(', ', $missingPosFiscalColumns),
                false,
                ['missing_columns' => $missingPosFiscalColumns]
            );
        } else {
            $addCheck(
                'pos.fiscal.columns',
                'POS fiscal compliance columns',
                'warn',
                'POS table is not available.'
            );
        }

        if (Schema::hasTable('bank_transactions')) {
            $oldUnreconciled = DB::table('bank_transactions')
                ->where('created_by', creatorId())
                ->where('reconciliation_status', 'unreconciled')
                ->whereDate('transaction_date', '<=', $thirtyDaysAgo)
                ->count();

            $addCheck(
                'bank.reconciliation.old_items',
                'Old unreconciled bank transactions',
                $oldUnreconciled === 0 ? 'pass' : 'warn',
                $oldUnreconciled === 0
                    ? 'No unreconciled bank transactions older than 30 days.'
                    : "There are {$oldUnreconciled} unreconciled bank transactions older than 30 days.",
                false,
                ['old_unreconciled_count' => $oldUnreconciled]
            );
        } else {
            $addCheck(
                'bank.reconciliation.old_items',
                'Old unreconciled bank transactions',
                'warn',
                'Bank transactions table is not available.'
            );
        }

        if (Schema::hasTable('audit_trails')) {
            $recentAuditEvents = DB::table('audit_trails')
                ->where('company_id', creatorId())
                ->whereDate('created_at', '>=', $thirtyDaysAgo)
                ->count();

            $addCheck(
                'audit.recent_activity',
                'Recent audit trail activity',
                $recentAuditEvents > 0 ? 'pass' : 'warn',
                $recentAuditEvents > 0
                    ? "Audit trail contains {$recentAuditEvents} events in the last 30 days."
                    : 'No audit trail events found in the last 30 days.',
                false,
                ['recent_events' => $recentAuditEvents]
            );
        } else {
            $addCheck(
                'audit.recent_activity',
                'Recent audit trail activity',
                'fail',
                'Audit trail table is not available.',
                true
            );
        }

        if (
            Schema::hasTable('customer_payments')
            && Schema::hasTable('vendor_payments')
            && Schema::hasColumn('customer_payments', 'payment_method')
            && Schema::hasColumn('vendor_payments', 'payment_method')
        ) {
            $invalidCustomerMobileMoney = DB::table('customer_payments')
                ->where('created_by', creatorId())
                ->where('payment_method', 'mobile_money')
                ->where(function ($query) {
                    $query->whereNull('mobile_money_provider')
                        ->orWhereNull('mobile_money_number')
                        ->orWhere('mobile_money_provider', '')
                        ->orWhere('mobile_money_number', '');
                })
                ->count();

            $invalidVendorMobileMoney = DB::table('vendor_payments')
                ->where('created_by', creatorId())
                ->where('payment_method', 'mobile_money')
                ->where(function ($query) {
                    $query->whereNull('mobile_money_provider')
                        ->orWhereNull('mobile_money_number')
                        ->orWhere('mobile_money_provider', '')
                        ->orWhere('mobile_money_number', '');
                })
                ->count();

            $invalidMobileMoneyRecords = $invalidCustomerMobileMoney + $invalidVendorMobileMoney;

            $addCheck(
                'mobile_money.data_integrity',
                'Mobile money payment data integrity',
                $invalidMobileMoneyRecords === 0 ? 'pass' : 'warn',
                $invalidMobileMoneyRecords === 0
                    ? 'No mobile money records with missing provider/number.'
                    : "Found {$invalidMobileMoneyRecords} mobile money records with missing provider/number.",
                false,
                [
                    'invalid_customer_records' => $invalidCustomerMobileMoney,
                    'invalid_vendor_records' => $invalidVendorMobileMoney,
                ]
            );
        } else {
            $addCheck(
                'mobile_money.data_integrity',
                'Mobile money payment data integrity',
                'warn',
                'Mobile money payment columns are not available.'
            );
        }

        $attestations = $this->getMozambiqueGoLiveAttestations();

        $legalStatus = strtolower((string) ($attestations['legal_review_status'] ?? 'pending'));
        $legalPassed = $legalStatus === 'approved' && !empty($attestations['legal_reviewed_at']);
        $addCheck(
            'legal.review.final',
            'Local legal/fiscal review finalization',
            $legalPassed ? 'pass' : 'fail',
            $legalPassed
                ? 'Legal and fiscal local review has been approved and dated.'
                : 'Legal/fiscal final review is not approved yet.',
            true,
            [
                'status' => $attestations['legal_review_status'],
                'reviewed_at' => $attestations['legal_reviewed_at'],
            ]
        );

        $commercialStatus = strtolower((string) ($attestations['commercial_readiness_status'] ?? 'pending'));
        $commercialPassed = $commercialStatus === 'approved' && !empty($attestations['commercial_reviewed_at']);
        $addCheck(
            'commercial.readiness.final',
            'Commercial readiness finalization',
            $commercialPassed ? 'pass' : 'fail',
            $commercialPassed
                ? 'Commercial package and rollout readiness has been approved and dated.'
                : 'Commercial readiness is not approved yet.',
            true,
            [
                'status' => $attestations['commercial_readiness_status'],
                'reviewed_at' => $attestations['commercial_reviewed_at'],
            ]
        );

        $pilotRegistryStats = [
            'total' => 0,
            'active' => 0,
            'completed' => 0,
            'validated_real' => 0,
        ];
        if (Schema::hasTable('mz_pilot_companies')) {
            try {
                $pilotRegistryStats['total'] = MozPilotCompany::query()
                    ->where('created_by', creatorId())
                    ->count();
                $pilotRegistryStats['active'] = MozPilotCompany::query()
                    ->where('created_by', creatorId())
                    ->where('status', 'active')
                    ->count();
                $pilotRegistryStats['completed'] = MozPilotCompany::query()
                    ->where('created_by', creatorId())
                    ->where('status', 'completed')
                    ->count();
                $pilotRegistryStats['validated_real'] = MozPilotCompany::query()
                    ->where('created_by', creatorId())
                    ->where('status', 'completed')
                    ->where('validation_result', 'passed')
                    ->whereNotNull('validation_signed_at')
                    ->whereNotNull('validation_evidence_ref')
                    ->where('validation_evidence_ref', '!=', '')
                    ->count();
            } catch (\Throwable $e) {
                // Keep readiness operational even when pilot table exists in metadata but is unavailable at runtime.
            }
        }

        $pilotRegistryReady = $pilotRegistryStats['total'] > 0;
        $addCheck(
            'pilot.registry.companies',
            'Pilot company registry',
            $pilotRegistryReady ? 'pass' : 'fail',
            $pilotRegistryReady
                ? "Pilot registry contains {$pilotRegistryStats['total']} company(ies)."
                : 'No pilot company registered yet.',
            true,
            $pilotRegistryStats
        );

        $pilotStatus = strtolower((string) ($attestations['pilot_status'] ?? 'not_started'));
        $pilotCompanyCount = max(
            (int) ($attestations['pilot_company_count'] ?? 0),
            (int) ($pilotRegistryStats['completed'] ?? 0)
        );
        $pilotCompleted = $pilotStatus === 'completed' && $pilotCompanyCount > 0 && !empty($attestations['pilot_completed_at']);
        $pilotCheckStatus = 'fail';
        if ($pilotCompleted) {
            $pilotCheckStatus = 'pass';
        } elseif ($pilotStatus === 'in_progress') {
            $pilotCheckStatus = 'warn';
        }
        $addCheck(
            'pilot.execution.final',
            'Pilot execution with local companies',
            $pilotCheckStatus,
            $pilotCompleted
                ? "Pilot completed with {$pilotCompanyCount} company(ies)."
                : ($pilotStatus === 'in_progress'
                    ? 'Pilot is in progress and not completed yet.'
                    : 'Pilot is not completed with at least one local company.'),
            true,
            [
                'status' => $attestations['pilot_status'],
                'completed_at' => $attestations['pilot_completed_at'],
                'company_count' => $pilotCompanyCount,
                'registry' => $pilotRegistryStats,
            ]
        );

        $realPilotValidated = (int) ($pilotRegistryStats['validated_real'] ?? 0) > 0;
        $addCheck(
            'pilot.real_companies.evidence',
            'Real pilot companies with signed evidence',
            $realPilotValidated ? 'pass' : 'fail',
            $realPilotValidated
                ? "Signed pilot validation evidence registered for {$pilotRegistryStats['validated_real']} company(ies)."
                : 'No completed pilot company with signed validation evidence reference.',
            true,
            $pilotRegistryStats
        );

        $payrollSectorValidationStatus = strtolower((string) ($attestations['payroll_sector_validation_status'] ?? 'not_started'));
        $payrollSectorValidationCompleted = $payrollSectorValidationStatus === 'completed'
            && !empty($attestations['payroll_sector_validation_completed_at']);
        $payrollSectorCheckStatus = 'fail';
        if ($payrollSectorValidationCompleted) {
            $payrollSectorCheckStatus = 'pass';
        } elseif ($payrollSectorValidationStatus === 'in_progress') {
            $payrollSectorCheckStatus = 'warn';
        }
        $addCheck(
            'payroll.sector.validation.final',
            'Payroll sector validation (minimum wage and labour rules)',
            $payrollSectorCheckStatus,
            $payrollSectorValidationCompleted
                ? 'Sector-based payroll validation has been completed and dated.'
                : ($payrollSectorValidationStatus === 'in_progress'
                    ? 'Sector-based payroll validation is in progress.'
                    : 'Sector-based payroll validation is not completed yet.'),
            true,
            [
                'status' => $attestations['payroll_sector_validation_status'],
                'completed_at' => $attestations['payroll_sector_validation_completed_at'],
            ]
        );

        $accountingLocalValidationStatus = strtolower((string) ($attestations['accounting_local_validation_status'] ?? 'not_started'));
        $accountingLocalValidationCompleted = $accountingLocalValidationStatus === 'completed'
            && !empty($attestations['accounting_local_validation_completed_at']);
        $accountingLocalCheckStatus = 'fail';
        if ($accountingLocalValidationCompleted) {
            $accountingLocalCheckStatus = 'pass';
        } elseif ($accountingLocalValidationStatus === 'in_progress') {
            $accountingLocalCheckStatus = 'warn';
        }
        $addCheck(
            'accounting.local.validation.final',
            'Local accounting validation (maps and declarations)',
            $accountingLocalCheckStatus,
            $accountingLocalValidationCompleted
                ? 'Local accounting validation has been completed and dated.'
                : ($accountingLocalValidationStatus === 'in_progress'
                    ? 'Local accounting validation is in progress.'
                    : 'Local accounting validation is not completed yet.'),
            true,
            [
                'status' => $attestations['accounting_local_validation_status'],
                'completed_at' => $attestations['accounting_local_validation_completed_at'],
            ]
        );

        $validationCaseStats = [
            'payroll_total' => 0,
            'payroll_validated' => 0,
            'accounting_total' => 0,
            'accounting_validated' => 0,
        ];
        if (Schema::hasTable('mz_pilot_validation_cases')) {
            try {
                $validationCaseStats['payroll_total'] = MozPilotValidationCase::query()
                    ->where('created_by', creatorId())
                    ->where('domain', 'payroll')
                    ->count();
                $validationCaseStats['payroll_validated'] = MozPilotValidationCase::query()
                    ->where('created_by', creatorId())
                    ->where('domain', 'payroll')
                    ->where('result', 'passed')
                    ->whereNotNull('executed_at')
                    ->whereNotNull('evidence_ref')
                    ->where('evidence_ref', '!=', '')
                    ->count();
                $validationCaseStats['accounting_total'] = MozPilotValidationCase::query()
                    ->where('created_by', creatorId())
                    ->where('domain', 'accounting')
                    ->count();
                $validationCaseStats['accounting_validated'] = MozPilotValidationCase::query()
                    ->where('created_by', creatorId())
                    ->where('domain', 'accounting')
                    ->where('result', 'passed')
                    ->whereNotNull('executed_at')
                    ->whereNotNull('evidence_ref')
                    ->where('evidence_ref', '!=', '')
                    ->count();
            } catch (\Throwable $e) {
                // Keep readiness working in environments without this table.
            }
        }

        $payrollRealCasesValidated = (int) $validationCaseStats['payroll_validated'] > 0;
        $addCheck(
            'payroll.sector.real_cases',
            'Payroll sector validation real cases',
            $payrollRealCasesValidated ? 'pass' : 'fail',
            $payrollRealCasesValidated
                ? "Validated payroll pilot case(s): {$validationCaseStats['payroll_validated']}."
                : 'No payroll pilot case with passed result, execution date, and evidence reference.',
            true,
            [
                'total' => $validationCaseStats['payroll_total'],
                'validated' => $validationCaseStats['payroll_validated'],
            ]
        );

        $accountingRealCasesValidated = (int) $validationCaseStats['accounting_validated'] > 0;
        $addCheck(
            'accounting.local.real_cases',
            'Accounting local validation real cases',
            $accountingRealCasesValidated ? 'pass' : 'fail',
            $accountingRealCasesValidated
                ? "Validated accounting pilot case(s): {$validationCaseStats['accounting_validated']}."
                : 'No accounting pilot case with passed result, execution date, and evidence reference.',
            true,
            [
                'total' => $validationCaseStats['accounting_total'],
                'validated' => $validationCaseStats['accounting_validated'],
            ]
        );

        $e2eFlows = [
            'sales' => strtolower((string) ($attestations['e2e_sales_flow_status'] ?? 'not_started')),
            'purchase' => strtolower((string) ($attestations['e2e_purchase_flow_status'] ?? 'not_started')),
            'pos' => strtolower((string) ($attestations['e2e_pos_flow_status'] ?? 'not_started')),
            'payroll' => strtolower((string) ($attestations['e2e_payroll_flow_status'] ?? 'not_started')),
        ];

        $e2eCompleted = collect($e2eFlows)->every(fn (string $status) => $status === 'completed')
            && !empty($attestations['e2e_completed_at']);

        $e2eCheckStatus = 'fail';
        if ($e2eCompleted) {
            $e2eCheckStatus = 'pass';
        } elseif (collect($e2eFlows)->contains('in_progress')) {
            $e2eCheckStatus = 'warn';
        }

        $pendingE2eFlows = collect($e2eFlows)
            ->filter(fn (string $status) => $status !== 'completed')
            ->keys()
            ->values()
            ->all();

        $addCheck(
            'qa.e2e_business_scenarios',
            'E2E business scenarios (sales/purchase/POS/payroll)',
            $e2eCheckStatus,
            $e2eCompleted
                ? 'All mandatory E2E business scenarios are marked as completed.'
                : (empty($pendingE2eFlows)
                    ? 'E2E scenarios are missing completion date.'
                    : 'Pending E2E scenarios: ' . implode(', ', $pendingE2eFlows)),
            true,
            [
                'flows' => $e2eFlows,
                'completed_at' => $attestations['e2e_completed_at'] ?? null,
            ]
        );

        $formalApproval = (string) ($attestations['go_live_approved'] ?? 'off') === 'on'
            && !empty($attestations['go_live_approved_at']);
        $addCheck(
            'go_live.formal_approval',
            'Formal go-live approval',
            $formalApproval ? 'pass' : 'fail',
            $formalApproval
                ? 'Formal go-live approval has been registered.'
                : 'Formal go-live approval is missing.',
            true,
            [
                'approved' => $attestations['go_live_approved'],
                'approved_at' => $attestations['go_live_approved_at'],
            ]
        );

        $vatDeclarationRoutesReady = Route::has('account.reports.mozambique-vat-declaration')
            && Route::has('account.reports.mozambique-vat-declaration.export');

        $addCheck(
            'exports.vat_declaration_routes',
            'VAT declaration export routes',
            $vatDeclarationRoutesReady ? 'pass' : 'fail',
            $vatDeclarationRoutesReady
                ? 'VAT declaration JSON and CSV routes are available.'
                : 'VAT declaration JSON/CSV route configuration is incomplete.',
            true
        );

        $submissionRegisterRoutesReady = Route::has('account.reports.mozambique-fiscal-submission-register')
            && Route::has('account.reports.mozambique-fiscal-submission-register.export');

        $addCheck(
            'exports.fiscal_submission_register_routes',
            'Fiscal submission register routes',
            $submissionRegisterRoutesReady ? 'pass' : 'warn',
            $submissionRegisterRoutesReady
                ? 'Fiscal submission register JSON and CSV routes are available.'
                : 'Fiscal submission register routes are not available yet.',
            false
        );

        $saftExportRouteReady = Route::has('account.reports.mozambique-saft.export');

        $addCheck(
            'exports.saft_route',
            'SAF-T export route',
            $saftExportRouteReady ? 'pass' : 'fail',
            $saftExportRouteReady
                ? 'SAF-T XML export route is available.'
                : 'SAF-T XML export route is missing.',
            true
        );

        $posFiscalRoutesReady = Route::has('pos.fiscal-status') && Route::has('pos.cancel-fiscal');

        $addCheck(
            'pos.fiscal.routes',
            'POS fiscal operation routes',
            $posFiscalRoutesReady ? 'pass' : 'warn',
            $posFiscalRoutesReady
                ? 'POS fiscal status/cancellation routes are available.'
                : 'POS fiscal status/cancellation routes are not available yet.',
            false
        );

        $overallStatus = 'ready';
        if ($summary['fail'] > 0) {
            $overallStatus = 'blocked';
        } elseif ($summary['warn'] > 0) {
            $overallStatus = 'attention';
        }

        $criticalChecksPassed = collect($checks)->every(function (array $check) {
            return !$check['critical'] || $check['status'] === 'pass';
        });

        $formalGoLiveCriteria = [
            'critical_checks_passed' => $criticalChecksPassed,
            'legal_review_completed' => $legalPassed,
            'commercial_readiness_completed' => $commercialPassed,
            'pilot_completed' => $pilotCompleted,
            'pilot_registry_populated' => $pilotRegistryReady,
            'pilot_real_companies_validated' => $realPilotValidated,
            'payroll_sector_validation_completed' => $payrollSectorValidationCompleted,
            'payroll_real_cases_validated' => $payrollRealCasesValidated,
            'accounting_local_validation_completed' => $accountingLocalValidationCompleted,
            'accounting_real_cases_validated' => $accountingRealCasesValidated,
            'e2e_scenarios_completed' => $e2eCompleted,
            'formal_approval_granted' => $formalApproval,
            'recommended_for_launch' => $criticalChecksPassed
                && $legalPassed
                && $commercialPassed
                && $pilotCompleted
                && $pilotRegistryReady
                && $realPilotValidated
                && $payrollSectorValidationCompleted
                && $payrollRealCasesValidated
                && $accountingLocalValidationCompleted
                && $accountingRealCasesValidated
                && $e2eCompleted
                && $formalApproval,
        ];

        return [
            'generated_at' => now()->toDateTimeString(),
            'overall_status' => $overallStatus,
            'summary' => $summary,
            'checks' => $checks,
            'formal_go_live_criteria' => $formalGoLiveCriteria,
            'attestations' => $attestations,
        ];
    }

    private function getMozambiqueGoLiveAttestations(): array
    {
        $stringSetting = static fn (string $key, string $default = ''): string => (string) (company_setting($key, creatorId()) ?? $default);

        $intSetting = static fn (string $key, int $default = 0): int => (int) (company_setting($key, creatorId()) ?? $default);

        return [
            'legal_review_status' => $stringSetting('mz_go_live_legal_review_status', 'pending'),
            'legal_reviewed_at' => $stringSetting('mz_go_live_legal_reviewed_at'),
            'legal_notes' => $stringSetting('mz_go_live_legal_notes'),
            'commercial_readiness_status' => $stringSetting('mz_go_live_commercial_status', 'pending'),
            'commercial_reviewed_at' => $stringSetting('mz_go_live_commercial_reviewed_at'),
            'commercial_notes' => $stringSetting('mz_go_live_commercial_notes'),
            'pilot_status' => $stringSetting('mz_go_live_pilot_status', 'not_started'),
            'pilot_completed_at' => $stringSetting('mz_go_live_pilot_completed_at'),
            'pilot_company_count' => $intSetting('mz_go_live_pilot_company_count', 0),
            'pilot_notes' => $stringSetting('mz_go_live_pilot_notes'),
            'payroll_sector_validation_status' => $stringSetting('mz_go_live_payroll_sector_validation_status', 'not_started'),
            'payroll_sector_validation_completed_at' => $stringSetting('mz_go_live_payroll_sector_validation_completed_at'),
            'payroll_sector_validation_notes' => $stringSetting('mz_go_live_payroll_sector_validation_notes'),
            'accounting_local_validation_status' => $stringSetting('mz_go_live_accounting_local_validation_status', 'not_started'),
            'accounting_local_validation_completed_at' => $stringSetting('mz_go_live_accounting_local_validation_completed_at'),
            'accounting_local_validation_notes' => $stringSetting('mz_go_live_accounting_local_validation_notes'),
            'e2e_sales_flow_status' => $stringSetting('mz_go_live_e2e_sales_flow_status', 'not_started'),
            'e2e_purchase_flow_status' => $stringSetting('mz_go_live_e2e_purchase_flow_status', 'not_started'),
            'e2e_pos_flow_status' => $stringSetting('mz_go_live_e2e_pos_flow_status', 'not_started'),
            'e2e_payroll_flow_status' => $stringSetting('mz_go_live_e2e_payroll_flow_status', 'not_started'),
            'e2e_completed_at' => $stringSetting('mz_go_live_e2e_completed_at'),
            'e2e_notes' => $stringSetting('mz_go_live_e2e_notes'),
            'go_live_approved' => $stringSetting('mz_go_live_formal_approval', 'off'),
            'go_live_approved_at' => $stringSetting('mz_go_live_formal_approval_at'),
            'go_live_approval_notes' => $stringSetting('mz_go_live_formal_approval_notes'),
        ];
    }

    private function getActiveMozambiqueTaxAccountMapping(string $asOfDate): ?array
    {
        if (!Schema::hasTable('mz_tax_account_mappings')) {
            return null;
        }

        try {
            $mapping = MozTaxAccountMapping::query()
                ->where('created_by', creatorId())
                ->where('is_active', true)
                ->whereDate('effective_from', '<=', $asOfDate)
                ->where(function ($query) use ($asOfDate) {
                    $query->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', $asOfDate);
                })
                ->with([
                    'vatOutputAccount:id,account_code,account_name',
                    'vatInputAccount:id,account_code,account_name',
                    'withholdingPayableAccount:id,account_code,account_name',
                    'withholdingReceivableAccount:id,account_code,account_name',
                    'irpcExpenseAccount:id,account_code,account_name',
                ])
                ->latest('effective_from')
                ->latest('id')
                ->first();
        } catch (\Throwable) {
            return null;
        }

        if (!$mapping) {
            return null;
        }

        return [
            'effective_from' => optional($mapping->effective_from)->toDateString(),
            'effective_to' => optional($mapping->effective_to)->toDateString(),
            'vat_output_account' => $mapping->vatOutputAccount ? "{$mapping->vatOutputAccount->account_code} - {$mapping->vatOutputAccount->account_name}" : null,
            'vat_input_account' => $mapping->vatInputAccount ? "{$mapping->vatInputAccount->account_code} - {$mapping->vatInputAccount->account_name}" : null,
            'withholding_payable_account' => $mapping->withholdingPayableAccount ? "{$mapping->withholdingPayableAccount->account_code} - {$mapping->withholdingPayableAccount->account_name}" : null,
            'withholding_receivable_account' => $mapping->withholdingReceivableAccount ? "{$mapping->withholdingReceivableAccount->account_code} - {$mapping->withholdingReceivableAccount->account_name}" : null,
            'irpc_expense_account' => $mapping->irpcExpenseAccount ? "{$mapping->irpcExpenseAccount->account_code} - {$mapping->irpcExpenseAccount->account_name}" : null,
        ];
    }

    public function getCustomerBalanceSummary($filters = [])
    {
        $asOfDate = $filters['as_of_date'] ?? date('Y-m-d');
        $showZeroBalances = $filters['show_zero_balances'] ?? false;

        $customers = DB::table('users')
            ->where('created_by', creatorId())
            ->where('type', 'client')
            ->select('id', 'name', 'email')
            ->get();

        $balances = [];
        $totalBalance = 0;

        foreach ($customers as $customer) {
            $invoiced = DB::table('sales_invoices')
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['posted', 'partial', 'paid'])
                ->where('invoice_date', '<=', $asOfDate)
                ->sum('total_amount');

            $returns = DB::table('sales_invoice_returns')
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['approved', 'completed'])
                ->where('return_date', '<=', $asOfDate)
                ->sum('total_amount');

            $balance = DB::table('sales_invoices')
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['posted', 'partial', 'paid'])
                ->where('invoice_date', '<=', $asOfDate)
                ->sum('balance_amount');

            $netInvoiced = $invoiced - $returns;
            $paid = $invoiced - $balance;

            if (!$showZeroBalances && abs($balance) < 0.01) {
                continue;
            }

            $balances[] = [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'total_invoiced' => $invoiced,
                'total_returns' => $returns,
                'net_invoiced' => $netInvoiced,
                'total_paid' => $paid,
                'balance' => $balance
            ];

            $totalBalance += $balance;
        }

        usort($balances, fn($a, $b) => $b['balance'] <=> $a['balance']);

        return [
            'customers' => $balances,
            'total_balance' => $totalBalance,
            'as_of_date' => $asOfDate
        ];
    }

    public function getVendorBalanceSummary($filters = [])
    {
        $asOfDate = $filters['as_of_date'] ?? date('Y-m-d');
        $showZeroBalances = $filters['show_zero_balances'] ?? false;

        $vendors = DB::table('users')
            ->where('created_by', creatorId())
            ->where('type', 'vendor')
            ->select('id', 'name', 'email')
            ->get();

        $balances = [];
        $totalBalance = 0;

        foreach ($vendors as $vendor) {
            $billed = DB::table('purchase_invoices')
                ->where('vendor_id', $vendor->id)
                ->whereIn('status', ['posted', 'partial', 'paid'])
                ->where('invoice_date', '<=', $asOfDate)
                ->sum('total_amount');

            $returns = DB::table('purchase_returns')
                ->where('vendor_id', $vendor->id)
                ->whereIn('status', ['approved', 'completed'])
                ->where('return_date', '<=', $asOfDate)
                ->sum('total_amount');

            $balance = DB::table('purchase_invoices')
                ->where('vendor_id', $vendor->id)
                ->whereIn('status', ['posted', 'partial', 'paid'])
                ->where('invoice_date', '<=', $asOfDate)
                ->sum('balance_amount');

            $netBilled = $billed - $returns;
            $paid = $billed - $balance;

            if (!$showZeroBalances && abs($balance) < 0.01) {
                continue;
            }

            $balances[] = [
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->name,
                'vendor_email' => $vendor->email,
                'total_billed' => $billed,
                'total_returns' => $returns,
                'net_billed' => $netBilled,
                'total_paid' => $paid,
                'balance' => $balance
            ];

            $totalBalance += $balance;
        }

        usort($balances, fn($a, $b) => $b['balance'] <=> $a['balance']);

        return [
            'vendors' => $balances,
            'total_balance' => $totalBalance,
            'as_of_date' => $asOfDate
        ];
    }

    public function getCustomerDetail($customerId, $filters = [])
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $taxLabel = $this->resolveCompanyTaxLabel();

        $customer = DB::table('users')
            ->leftJoin('customers', function ($join) {
                $join->on('customers.user_id', '=', 'users.id')
                    ->where('customers.created_by', creatorId());
            })
            ->where('users.id', $customerId)
            ->where('users.type', 'client')
            ->where('users.created_by', creatorId())
            ->select('users.id', 'users.name', 'users.email', 'customers.company_name', 'customers.tax_number')
            ->first();

        if (!$customer) {
            return null;
        }

        $invoicesQuery = DB::table('sales_invoices')
            ->where('created_by', creatorId())
            ->where('customer_id', $customerId)
            ->whereIn('status', ['posted', 'partial', 'paid'])
            ->select('invoice_number', 'invoice_date as date', 'due_date', 'subtotal', 'tax_amount', 'total_amount', 'balance_amount', 'status');

        if ($startDate) $invoicesQuery->where('invoice_date', '>=', $startDate);
        if ($endDate) $invoicesQuery->where('invoice_date', '<=', $endDate);
        $invoices = $invoicesQuery->orderBy('invoice_date', 'desc')->get();

        $returnsQuery = DB::table('sales_invoice_returns')
            ->where('created_by', creatorId())
            ->where('customer_id', $customerId)
            ->whereIn('status', ['approved', 'completed'])
            ->select('return_number', 'return_date as date', 'subtotal', 'tax_amount', 'total_amount', 'status');

        if ($startDate) $returnsQuery->where('return_date', '>=', $startDate);
        if ($endDate) $returnsQuery->where('return_date', '<=', $endDate);
        $returns = $returnsQuery->orderBy('return_date', 'desc')->get();

        $creditNotesQuery = DB::table('credit_notes')
            ->where('created_by', creatorId())
            ->where('customer_id', $customerId)
            ->whereIn('status', ['approved', 'partial', 'applied'])
            ->select('credit_note_number', 'credit_note_date as date', 'total_amount', 'applied_amount', 'balance_amount', 'status');

        if ($startDate) $creditNotesQuery->where('credit_note_date', '>=', $startDate);
        if ($endDate) $creditNotesQuery->where('credit_note_date', '<=', $endDate);
        $creditNotes = $creditNotesQuery->orderBy('credit_note_date', 'desc')->get();

        $paymentsQuery = DB::table('customer_payments')
            ->leftJoin('bank_accounts', 'customer_payments.bank_account_id', '=', 'bank_accounts.id')
            ->where('customer_payments.created_by', creatorId())
            ->where('customer_payments.customer_id', $customerId)
            ->select('customer_payments.payment_number', 'customer_payments.payment_date as date', 'customer_payments.payment_amount as amount', 'customer_payments.reference_number', 'customer_payments.status', 'bank_accounts.account_name as bank_account');

        if ($startDate) $paymentsQuery->where('payment_date', '>=', $startDate);
        if ($endDate) $paymentsQuery->where('payment_date', '<=', $endDate);
        $payments = $paymentsQuery->orderBy('payment_date', 'desc')->get();

        return [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'company_name' => $customer->company_name,
                'tax_number' => $customer->tax_number,
                'tax_label' => $taxLabel,
            ],
            'date_range' => ['start_date' => $startDate, 'end_date' => $endDate],
            'invoices' => $invoices,
            'returns' => $returns,
            'credit_notes' => $creditNotes,
            'payments' => $payments,
            'summary' => [
                'total_invoiced' => $invoices->sum('total_amount'),
                'total_returns' => $returns->sum('total_amount'),
                'total_credit_notes' => $creditNotes->sum('total_amount'),
                'total_payments' => $payments->sum('amount'),
                'balance' => $invoices->sum('balance_amount')
            ]
        ];
    }

    public function getVendorDetail($vendorId, $filters = [])
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $taxLabel = $this->resolveCompanyTaxLabel();

        $vendor = DB::table('users')
            ->leftJoin('vendors', function ($join) {
                $join->on('vendors.user_id', '=', 'users.id')
                    ->where('vendors.created_by', creatorId());
            })
            ->where('users.id', $vendorId)
            ->where('users.type', 'vendor')
            ->where('users.created_by', creatorId())
            ->select('users.id', 'users.name', 'users.email', 'vendors.company_name', 'vendors.tax_number')
            ->first();

        if (!$vendor) {
            return null;
        }

        $invoicesQuery = DB::table('purchase_invoices')
            ->where('created_by', creatorId())
            ->where('vendor_id', $vendorId)
            ->whereIn('status', ['posted', 'partial', 'paid'])
            ->select('invoice_number', 'invoice_date as date', 'due_date', 'subtotal', 'tax_amount', 'total_amount', 'balance_amount', 'status');

        if ($startDate) $invoicesQuery->where('invoice_date', '>=', $startDate);
        if ($endDate) $invoicesQuery->where('invoice_date', '<=', $endDate);
        $invoices = $invoicesQuery->orderBy('invoice_date', 'desc')->get();

        $returnsQuery = DB::table('purchase_returns')
            ->where('created_by', creatorId())
            ->where('vendor_id', $vendorId)
            ->whereIn('status', ['approved', 'completed'])
            ->select('return_number', 'return_date as date', 'subtotal', 'tax_amount', 'total_amount', 'status');

        if ($startDate) $returnsQuery->where('return_date', '>=', $startDate);
        if ($endDate) $returnsQuery->where('return_date', '<=', $endDate);
        $returns = $returnsQuery->orderBy('return_date', 'desc')->get();

        $debitNotesQuery = DB::table('debit_notes')
            ->where('created_by', creatorId())
            ->where('vendor_id', $vendorId)
            ->whereIn('status', ['approved', 'partial', 'applied'])
            ->select('debit_note_number', 'debit_note_date as date', 'total_amount', 'applied_amount', 'balance_amount', 'status');

        if ($startDate) $debitNotesQuery->where('debit_note_date', '>=', $startDate);
        if ($endDate) $debitNotesQuery->where('debit_note_date', '<=', $endDate);
        $debitNotes = $debitNotesQuery->orderBy('debit_note_date', 'desc')->get();

        $paymentsQuery = DB::table('vendor_payments')
            ->leftJoin('bank_accounts', 'vendor_payments.bank_account_id', '=', 'bank_accounts.id')
            ->where('vendor_payments.created_by', creatorId())
            ->where('vendor_payments.vendor_id', $vendorId)
            ->select('vendor_payments.payment_number', 'vendor_payments.payment_date as date', 'vendor_payments.payment_amount as amount', 'vendor_payments.reference_number', 'vendor_payments.status', 'bank_accounts.account_name as bank_account');

        if ($startDate) $paymentsQuery->where('payment_date', '>=', $startDate);
        if ($endDate) $paymentsQuery->where('payment_date', '<=', $endDate);
        $payments = $paymentsQuery->orderBy('payment_date', 'desc')->get();

        return [
            'vendor' => [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'email' => $vendor->email,
                'company_name' => $vendor->company_name,
                'tax_number' => $vendor->tax_number,
                'tax_label' => $taxLabel,
            ],
            'date_range' => ['start_date' => $startDate, 'end_date' => $endDate],
            'invoices' => $invoices,
            'returns' => $returns,
            'debit_notes' => $debitNotes,
            'payments' => $payments,
            'summary' => [
                'total_invoiced' => $invoices->sum('total_amount'),
                'total_returns' => $returns->sum('total_amount'),
                'total_debit_notes' => $debitNotes->sum('total_amount'),
                'total_payments' => $payments->sum('amount'),
                'balance' => $invoices->sum('balance_amount')
            ]
        ];
    }

    public function getFiscalClosings(): array
    {
        if (!Schema::hasTable('mz_fiscal_closings')) {
            return [
                'latest_closed_until' => null,
                'closings' => [],
            ];
        }

        $rows = MozFiscalClosing::query()
            ->where('created_by', creatorId())
            ->with(['closedBy:id,name', 'reopenedBy:id,name'])
            ->orderByDesc('period_to')
            ->orderByDesc('id')
            ->limit(36)
            ->get();

        $latestClosedUntil = $rows
            ->where('status', 'closed')
            ->max(fn ($item) => optional($item->period_to)->toDateString());

        return [
            'latest_closed_until' => $latestClosedUntil ?: null,
            'closings' => $rows->map(function (MozFiscalClosing $closing) {
                return [
                    'id' => $closing->id,
                    'period_from' => optional($closing->period_from)->toDateString(),
                    'period_to' => optional($closing->period_to)->toDateString(),
                    'status' => $closing->status,
                    'close_reason' => $closing->close_reason,
                    'reopen_reason' => $closing->reopen_reason,
                    'closed_at' => optional($closing->closed_at)->toDateTimeString(),
                    'reopened_at' => optional($closing->reopened_at)->toDateTimeString(),
                    'closed_by' => $closing->closedBy?->name,
                    'reopened_by' => $closing->reopenedBy?->name,
                    'snapshot' => $closing->snapshot,
                ];
            })->values()->all(),
        ];
    }

    public function buildFiscalClosingSnapshot(string $fromDate, string $toDate): array
    {
        $taxSummary = $this->getTaxSummary([
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);

        $fiscalMap = $this->getMozambiqueFiscalMap([
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);

        $journalSummary = DB::table('journal_entries')
            ->where('created_by', creatorId())
            ->whereBetween('journal_date', [$fromDate, $toDate])
            ->selectRaw('COUNT(*) as entries, COALESCE(SUM(total_debit), 0) as total_debit, COALESCE(SUM(total_credit), 0) as total_credit')
            ->first();

        return [
            'period' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
            'tax_summary' => $taxSummary,
            'mozambique_fiscal_map' => $fiscalMap,
            'journal_summary' => [
                'entries' => (int) ($journalSummary->entries ?? 0),
                'total_debit' => (float) ($journalSummary->total_debit ?? 0),
                'total_credit' => (float) ($journalSummary->total_credit ?? 0),
            ],
            'generated_at' => now()->toDateTimeString(),
            'generated_by' => auth()->id(),
        ];
    }

    private function resolveCompanyTaxLabel(): string
    {
        $settings = getCompanyAllSetting(creatorId());
        $taxType = strtoupper((string) ($settings['tax_type'] ?? ''));
        $country = strtolower((string) ($settings['company_country'] ?? ''));

        if ($taxType === 'NUIT') {
            return 'NUIT';
        }

        if ($taxType === 'VAT') {
            return 'VAT';
        }

        if ($taxType === 'GST') {
            return 'GST';
        }

        if (str_contains($country, 'mozambique') || str_contains($country, 'moçambique')) {
            return 'NUIT';
        }

        return 'Tax Number';
    }
}
