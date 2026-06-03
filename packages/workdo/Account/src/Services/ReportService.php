<?php

namespace Workdo\Account\Services;

use App\Services\VatCalculationService;
use Carbon\Carbon;
use App\Support\MozambiqueTaxNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\BankTransaction;
use Workdo\Account\Models\MozCashClosing;
use Workdo\Account\Models\MozFiscalClosing;
use Workdo\Account\Models\MozPilotCompany;
use Workdo\Account\Models\MozPilotValidationCase;
use Workdo\Account\Models\MozTaxAccountMapping;

class ReportService
{
    private VatCalculationService $vatCalculationService;
    private ?int $cachedCompanyId = null;
    private ?array $cachedCompanySettings = null;
    private array $cachedCompanySettingValues = [];

    public function __construct(VatCalculationService $vatCalculationService)
    {
        $this->vatCalculationService = $vatCalculationService;
    }

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

        $vatResolution = $this->resolveEffectiveVatTotals($fromDate, $toDate);
        $effectiveVat = $vatResolution['effective'];
        $totalCollected = (float) ($effectiveVat['output_vat'] ?? 0);
        $totalPaid = (float) ($effectiveVat['deductible_input_vat'] ?? 0);

        return [
            'tax_collected' => [
                'items' => [[
                    'tax_name' => 'IVA (Liquidado)',
                    'amount' => $totalCollected,
                ]],
                'total' => $totalCollected
            ],
            'tax_paid' => [
                'items' => [[
                    'tax_name' => 'IVA (Dedutível)',
                    'amount' => $totalPaid,
                ]],
                'total' => $totalPaid
            ],
            'net_tax_liability' => (float) ($effectiveVat['net_vat_payable'] ?? 0),
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'source' => $vatResolution['source'],
            'vat_reconciliation' => $vatResolution['reconciliation'],
        ];
    }

    public function getMozambiqueFiscalMap($filters = [])
    {
        $fromDate = $filters['from_date'] ?? date('Y-01-01');
        $toDate = $filters['to_date'] ?? date('Y-12-31');
        $companyId = $this->companyId();

        $salesBaseQuery = DB::table('sales_invoices')
            ->where('created_by', $companyId)
            ->whereIn('status', ['posted', 'partial', 'paid'])
            ->whereBetween('invoice_date', [$fromDate, $toDate]);

        $purchaseBaseQuery = DB::table('purchase_invoices')
            ->where('created_by', $companyId)
            ->whereIn('status', ['posted', 'partial', 'paid'])
            ->whereBetween('invoice_date', [$fromDate, $toDate]);

        $creditNoteBaseQuery = DB::table('credit_notes')
            ->where('created_by', $companyId)
            ->where('status', 'approved')
            ->whereBetween('credit_note_date', [$fromDate, $toDate]);

        $debitNoteBaseQuery = DB::table('debit_notes')
            ->where('created_by', $companyId)
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
                ->where('pos.created_by', $companyId)
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

        $vatResolution = $this->resolveEffectiveVatTotals($fromDate, $toDate, [
            'sales_vat' => (float) $salesSummary->tax_amount,
            'pos_vat' => (float) ($posSummary->tax_amount ?? 0),
            'purchase_vat' => (float) $purchaseSummary->tax_amount,
            'credit_notes_vat' => (float) $creditNoteSummary->tax_amount,
            'debit_notes_vat' => (float) $debitNoteSummary->tax_amount,
        ]);
        $effectiveVat = $vatResolution['effective'];

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
                'output_vat' => (float) ($effectiveVat['output_vat'] ?? 0),
                'input_vat' => (float) ($effectiveVat['input_vat'] ?? 0),
                'input_vat_deductible' => (float) ($effectiveVat['deductible_input_vat'] ?? 0),
                'input_vat_non_deductible' => (float) ($effectiveVat['non_deductible_input_vat'] ?? 0),
                'net_vat_payable' => (float) ($effectiveVat['net_vat_payable'] ?? 0),
            ],
            'fiscal_status' => [
                'sales' => $salesFiscalStatus,
                'pos' => $posFiscalStatus,
                'purchases' => $purchaseFiscalStatus,
            ],
            'tax_account_mapping' => $this->getActiveMozambiqueTaxAccountMapping($toDate),
            'source' => $vatResolution['source'],
            'vat_reconciliation' => $vatResolution['reconciliation'],
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
            'source' => 'documents',
            'vat_reconciliation' => [
                'ledger' => $this->buildLedgerVatPeriodTotals($fromDate, $toDate),
                'documents' => [
                    'output_vat' => (float) $totals['output_vat'],
                    'input_vat' => (float) $totals['input_vat'],
                    'deductible_input_vat' => (float) $totals['deductible_input_vat'],
                    'non_deductible_input_vat' => (float) $totals['non_deductible_input_vat'],
                    'net_vat_payable' => (float) $totals['net_vat_payable'],
                ],
            ],
        ];
    }

    private function resolveEffectiveVatTotals(
        string $fromDate,
        string $toDate,
        array $documentBuckets = []
    ): array {
        $ledger = $this->buildLedgerVatPeriodTotals($fromDate, $toDate);

        $documentOutputVat = max(
            ((float) ($documentBuckets['sales_vat'] ?? 0) + (float) ($documentBuckets['pos_vat'] ?? 0))
            - (float) ($documentBuckets['credit_notes_vat'] ?? 0),
            0.0
        );
        $documentInputVat = max(
            (float) ($documentBuckets['purchase_vat'] ?? 0) - (float) ($documentBuckets['debit_notes_vat'] ?? 0),
            0.0
        );

        $documentNonDeductible = min(
            $documentInputVat,
            $this->getNonDeductibleInputVatTotal($fromDate, $toDate)
        );
        $documentDeductible = max($documentInputVat - $documentNonDeductible, 0.0);

        $documents = [
            'output_vat' => $documentOutputVat,
            'input_vat' => $documentInputVat,
            'deductible_input_vat' => $documentDeductible,
            'non_deductible_input_vat' => $documentNonDeductible,
            'net_vat_payable' => $documentOutputVat - $documentInputVat,
        ];

        $useLedger = $this->hasLedgerVatMovements($ledger);
        $effective = $useLedger ? $ledger : $documents;

        return [
            'source' => $useLedger ? 'ledger_sce' : 'document_fallback',
            'effective' => $effective,
            'reconciliation' => [
                'ledger' => $ledger,
                'documents' => $documents,
                'delta' => [
                    'output_vat' => (float) $ledger['output_vat'] - (float) $documents['output_vat'],
                    'input_vat' => (float) $ledger['input_vat'] - (float) $documents['input_vat'],
                    'deductible_input_vat' => (float) $ledger['deductible_input_vat'] - (float) $documents['deductible_input_vat'],
                    'non_deductible_input_vat' => (float) $ledger['non_deductible_input_vat'] - (float) $documents['non_deductible_input_vat'],
                    'net_vat_payable' => (float) $ledger['net_vat_payable'] - (float) $documents['net_vat_payable'],
                ],
            ],
        ];
    }

    private function buildLedgerVatPeriodTotals(string $fromDate, string $toDate): array
    {
        $vat = $this->vatCalculationService->calculatePeriodVat(
            creatorId(),
            $fromDate,
            $toDate
        );

        return [
            'output_vat' => (float) ($vat['output_vat'] ?? 0),
            'input_vat' => (float) ($vat['supported_vat'] ?? 0),
            'deductible_input_vat' => (float) ($vat['deductible_vat'] ?? 0),
            'non_deductible_input_vat' => (float) ($vat['non_deductible_vat'] ?? 0),
            'net_vat_payable' => (float) ($vat['net_position'] ?? 0),
        ];
    }

    private function hasLedgerVatMovements(array $ledger): bool
    {
        $trackedKeys = [
            'output_vat',
            'input_vat',
            'deductible_input_vat',
            'non_deductible_input_vat',
            'net_vat_payable',
        ];

        foreach ($trackedKeys as $key) {
            if (abs((float) ($ledger[$key] ?? 0)) > 0.00001) {
                return true;
            }
        }

        return false;
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

    public function getMozambiqueExchangeControlReport($filters = []): array
    {
        $fromDate = $filters['from_date'] ?? date('Y-01-01');
        $toDate = $filters['to_date'] ?? date('Y-12-31');
        $companyId = $this->companyId();

        $outboundPayments = [];
        if (Schema::hasTable('vendor_payments') && Schema::hasColumn('vendor_payments', 'payment_date')) {
            $vendorPaymentsQuery = DB::table('vendor_payments as vp')
                ->leftJoin('vendors as v', function ($join) use ($companyId): void {
                    $join->on('v.user_id', '=', 'vp.vendor_id')
                        ->where('v.created_by', '=', $companyId);
                })
                ->leftJoin('users as u', 'u.id', '=', 'vp.vendor_id')
                ->where('vp.created_by', $companyId)
                ->whereBetween('vp.payment_date', [$fromDate, $toDate])
                ->select([
                    'vp.id',
                    'vp.payment_number',
                    'vp.payment_date',
                    'vp.payment_amount',
                    'vp.amount_mzn',
                    'vp.currency_code',
                    'vp.foreign_amount',
                    'vp.exchange_rate',
                    'vp.is_international_payment',
                    'vp.status',
                    'vp.beneficiary_country',
                    'vp.withholding_tax_treatment',
                    'vp.fiscal_compliance_reference',
                    'vp.financial_approval_reference',
                    'vp.fx_authorization_reference',
                    'v.company_name as vendor_company_name',
                    'v.fiscal_country as vendor_fiscal_country',
                    'v.fiscal_residency_status as vendor_residency_status',
                    'u.name as vendor_name',
                ]);

            if (Schema::hasColumn('vendor_payments', 'status')) {
                $vendorPaymentsQuery->where('vp.status', '!=', 'cancelled');
            }

            $outboundPayments = $vendorPaymentsQuery
                ->orderBy('vp.payment_date')
                ->orderBy('vp.id')
                ->get()
                ->map(function ($row): array {
                    $currencyCode = strtoupper((string) ($row->currency_code ?? 'MZN'));
                    if ($currencyCode === '') {
                        $currencyCode = 'MZN';
                    }

                    $amountMzn = (float) ($row->amount_mzn ?? $row->payment_amount ?? 0);
                    $isInternational = (bool) ($row->is_international_payment ?? false) || $currencyCode !== 'MZN';
                    $beneficiaryCountry = trim((string) ($row->beneficiary_country ?: $row->vendor_fiscal_country ?: ''));
                    $residencyStatus = strtolower((string) ($row->vendor_residency_status ?? 'resident'));
                    $isDomesticCountry = $beneficiaryCountry === '' || $this->isMozambiqueCountry($beneficiaryCountry);
                    $domesticFxViolation = $currencyCode !== 'MZN' && $residencyStatus !== 'non_resident' && $isDomesticCountry;

                    $missingFxDocumentation = $isInternational
                        && (
                            trim((string) ($row->fiscal_compliance_reference ?? '')) === ''
                            || trim((string) ($row->financial_approval_reference ?? '')) === ''
                            || trim((string) ($row->fx_authorization_reference ?? '')) === ''
                        );

                    return [
                        'payment_id' => (int) $row->id,
                        'payment_type' => 'vendor_payment',
                        'direction' => 'outbound',
                        'operation_type' => 'international_payment',
                        'reference' => (string) ($row->payment_number ?? ('VP-' . $row->id)),
                        'date' => (string) ($row->payment_date ?? ''),
                        'counterparty' => (string) ($row->vendor_company_name ?: $row->vendor_name ?: ''),
                        'counterparty_country' => $beneficiaryCountry,
                        'counterparty_residency_status' => $residencyStatus,
                        'currency_code' => $currencyCode,
                        'foreign_amount' => (float) ($row->foreign_amount ?? 0),
                        'exchange_rate' => (float) ($row->exchange_rate ?? 1),
                        'amount_mzn' => $amountMzn,
                        'status' => (string) ($row->status ?? 'pending'),
                        'is_export_receipt' => false,
                        'repatriation_status' => 'not_applicable',
                        'repatriated_amount_mzn' => 0.0,
                        'is_international' => $isInternational,
                        'domestic_fx_violation' => $domesticFxViolation,
                        'missing_fx_documentation' => $missingFxDocumentation,
                        'fiscal_compliance_reference' => $row->fiscal_compliance_reference,
                        'financial_approval_reference' => $row->financial_approval_reference,
                        'fx_authorization_reference' => $row->fx_authorization_reference,
                        'withholding_tax_treatment' => $row->withholding_tax_treatment,
                    ];
                })
                ->values()
                ->all();
        }

        $outboundCollection = collect($outboundPayments);
        $inboundCollection = collect();

        if (Schema::hasTable('customer_payments') && Schema::hasColumn('customer_payments', 'payment_date')) {
            $hasExportReceipt = Schema::hasColumn('customer_payments', 'is_export_receipt');
            $hasOriginCountry = Schema::hasColumn('customer_payments', 'receipt_origin_country');
            $hasExportReference = Schema::hasColumn('customer_payments', 'export_reference');
            $hasIntermediaryBank = Schema::hasColumn('customer_payments', 'intermediary_bank');
            $hasRepatriationStatus = Schema::hasColumn('customer_payments', 'repatriation_status');
            $hasRepatriatedAmount = Schema::hasColumn('customer_payments', 'repatriated_amount_mzn');
            $hasFxComplianceReference = Schema::hasColumn('customer_payments', 'fx_compliance_reference');

            $customerPaymentsQuery = DB::table('customer_payments as cp')
                ->leftJoin('customers as c', function ($join) use ($companyId): void {
                    $join->on('c.user_id', '=', 'cp.customer_id')
                        ->where('c.created_by', '=', $companyId);
                })
                ->leftJoin('users as u', 'u.id', '=', 'cp.customer_id')
                ->where('cp.created_by', $companyId)
                ->whereBetween('cp.payment_date', [$fromDate, $toDate])
                ->select([
                    'cp.id',
                    'cp.payment_number',
                    'cp.payment_date',
                    'cp.payment_amount',
                    'cp.amount_mzn',
                    'cp.currency_code',
                    'cp.foreign_amount',
                    'cp.exchange_rate',
                    'cp.status',
                    'c.company_name as customer_company_name',
                    'c.fiscal_country as customer_fiscal_country',
                    'c.fiscal_residency_status as customer_residency_status',
                    'u.name as customer_name',
                    DB::raw($hasExportReceipt ? 'cp.is_export_receipt as is_export_receipt' : '0 as is_export_receipt'),
                    DB::raw($hasOriginCountry ? 'cp.receipt_origin_country as receipt_origin_country' : 'NULL as receipt_origin_country'),
                    DB::raw($hasExportReference ? 'cp.export_reference as export_reference' : 'NULL as export_reference'),
                    DB::raw($hasIntermediaryBank ? 'cp.intermediary_bank as intermediary_bank' : 'NULL as intermediary_bank'),
                    DB::raw($hasRepatriationStatus ? 'cp.repatriation_status as repatriation_status' : "'not_applicable' as repatriation_status"),
                    DB::raw($hasRepatriatedAmount ? 'cp.repatriated_amount_mzn as repatriated_amount_mzn' : 'NULL as repatriated_amount_mzn'),
                    DB::raw($hasFxComplianceReference ? 'cp.fx_compliance_reference as fx_compliance_reference' : 'NULL as fx_compliance_reference'),
                ]);

            if (Schema::hasColumn('customer_payments', 'status')) {
                $customerPaymentsQuery->where('cp.status', '!=', 'cancelled');
            }

            $inboundCollection = $customerPaymentsQuery
                ->orderBy('cp.payment_date')
                ->orderBy('cp.id')
                ->get()
                ->map(function ($row): array {
                    $currencyCode = strtoupper((string) ($row->currency_code ?? 'MZN'));
                    if ($currencyCode === '') {
                        $currencyCode = 'MZN';
                    }

                    $amountMzn = (float) ($row->amount_mzn ?? $row->payment_amount ?? 0);
                    $isExportReceipt = (bool) ($row->is_export_receipt ?? false);
                    $residencyStatus = strtolower((string) ($row->customer_residency_status ?? 'resident'));
                    $originCountry = trim((string) ($row->receipt_origin_country ?: $row->customer_fiscal_country ?: ''));
                    $repatriationStatus = strtolower((string) ($row->repatriation_status ?? 'not_applicable'));
                    if (!in_array($repatriationStatus, ['not_applicable', 'pending', 'partial', 'completed'], true)) {
                        $repatriationStatus = 'pending';
                    }

                    $repatriatedAmount = (float) ($row->repatriated_amount_mzn ?? 0);
                    $isInternational = $currencyCode !== 'MZN' || $isExportReceipt || $residencyStatus === 'non_resident';
                    $domesticFxViolation = $currencyCode !== 'MZN'
                        && $residencyStatus !== 'non_resident'
                        && !$isExportReceipt;

                    $missingFxDocumentation = $isInternational
                        && (
                            trim((string) ($row->fx_compliance_reference ?? '')) === ''
                            || ($isExportReceipt && trim((string) ($row->export_reference ?? '')) === '')
                            || ($isExportReceipt && trim((string) ($row->intermediary_bank ?? '')) === '')
                        );

                    return [
                        'payment_id' => (int) $row->id,
                        'payment_type' => 'customer_payment',
                        'direction' => 'inbound',
                        'operation_type' => $isExportReceipt ? 'export_receipt' : 'foreign_receipt',
                        'reference' => (string) ($row->payment_number ?? ('CP-' . $row->id)),
                        'date' => (string) ($row->payment_date ?? ''),
                        'counterparty' => (string) ($row->customer_company_name ?: $row->customer_name ?: ''),
                        'counterparty_country' => $originCountry,
                        'counterparty_residency_status' => $residencyStatus,
                        'currency_code' => $currencyCode,
                        'foreign_amount' => (float) ($row->foreign_amount ?? 0),
                        'exchange_rate' => (float) ($row->exchange_rate ?? 1),
                        'amount_mzn' => $amountMzn,
                        'status' => (string) ($row->status ?? 'pending'),
                        'is_export_receipt' => $isExportReceipt,
                        'repatriation_status' => $repatriationStatus,
                        'repatriated_amount_mzn' => $repatriatedAmount,
                        'is_international' => $isInternational,
                        'domestic_fx_violation' => $domesticFxViolation,
                        'missing_fx_documentation' => $missingFxDocumentation,
                        'export_reference' => $row->export_reference,
                        'intermediary_bank' => $row->intermediary_bank,
                        'fx_compliance_reference' => $row->fx_compliance_reference,
                    ];
                })
                ->values();
        }

        $dossierByOperation = collect();
        if (Schema::hasTable('exchange_control_dossiers')) {
            $outboundIds = $outboundCollection->pluck('payment_id')->filter()->map(static fn ($id) => (int) $id)->values();
            $inboundIds = $inboundCollection->pluck('payment_id')->filter()->map(static fn ($id) => (int) $id)->values();

            $dossierRows = DB::table('exchange_control_dossiers')
                ->where('company_id', $companyId)
                ->where(function ($query) use ($outboundIds, $inboundIds): void {
                    if ($outboundIds->isNotEmpty()) {
                        $query->orWhere(function ($subQuery) use ($outboundIds): void {
                            $subQuery->where('direction', 'outbound')
                                ->where('payment_type', 'vendor_payment')
                                ->whereIn('payment_id', $outboundIds->all());
                        });
                    }

                    if ($inboundIds->isNotEmpty()) {
                        $query->orWhere(function ($subQuery) use ($inboundIds): void {
                            $subQuery->where('direction', 'inbound')
                                ->where('payment_type', 'customer_payment')
                                ->whereIn('payment_id', $inboundIds->all());
                        });
                    }
                })
                ->get()
                ->mapWithKeys(function ($row): array {
                    $key = $this->buildExchangeDossierOperationKey(
                        (string) ($row->direction ?? ''),
                        (string) ($row->payment_type ?? ''),
                        (int) ($row->payment_id ?? 0)
                    );

                    return [$key => $row];
                });

            $dossierByOperation = $dossierRows;
        }

        $outboundCollection = $outboundCollection
            ->map(fn (array $row): array => $this->attachExchangeDossierMetadata($row, $dossierByOperation))
            ->values();
        $inboundCollection = $inboundCollection
            ->map(fn (array $row): array => $this->attachExchangeDossierMetadata($row, $dossierByOperation))
            ->values();

        $allOperations = $outboundCollection->concat($inboundCollection)->values();
        $domesticFxViolations = $allOperations
            ->filter(static fn (array $row): bool => (bool) ($row['domestic_fx_violation'] ?? false))
            ->values();
        $missingDocumentation = $allOperations
            ->filter(static fn (array $row): bool => (bool) ($row['missing_fx_documentation'] ?? false))
            ->values();
        $missingDossiers = $allOperations
            ->filter(static fn (array $row): bool => (bool) ($row['dossier_required'] ?? false))
            ->filter(static fn (array $row): bool => !(bool) ($row['dossier_complete'] ?? false))
            ->values();
        $completedDossiers = $allOperations
            ->filter(static fn (array $row): bool => (bool) ($row['dossier_required'] ?? false))
            ->filter(static fn (array $row): bool => (bool) ($row['dossier_complete'] ?? false))
            ->values();
        $pendingRepatriation = $inboundCollection
            ->filter(static fn (array $row): bool => (bool) ($row['is_export_receipt'] ?? false))
            ->filter(static fn (array $row): bool => !in_array((string) ($row['repatriation_status'] ?? ''), ['completed'], true))
            ->values();
        $completedRepatriation = $inboundCollection
            ->filter(static fn (array $row): bool => (bool) ($row['is_export_receipt'] ?? false))
            ->filter(static fn (array $row): bool => (string) ($row['repatriation_status'] ?? '') === 'completed')
            ->values();

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'summary' => [
                'total_operations' => $allOperations->count(),
                'outbound_count' => $outboundCollection->count(),
                'inbound_count' => $inboundCollection->count(),
                'outbound_amount_mzn' => round((float) $outboundCollection->sum('amount_mzn'), 2),
                'inbound_amount_mzn' => round((float) $inboundCollection->sum('amount_mzn'), 2),
                'domestic_fx_violations' => $domesticFxViolations->count(),
                'missing_fx_documentation' => $missingDocumentation->count(),
                'missing_dossier_count' => $missingDossiers->count(),
                'completed_dossier_count' => $completedDossiers->count(),
                'pending_repatriation_count' => $pendingRepatriation->count(),
                'completed_repatriation_count' => $completedRepatriation->count(),
            ],
            'outbound_payments' => $outboundCollection->values()->all(),
            'inbound_receipts' => $inboundCollection->values()->all(),
            'domestic_fx_violations' => $domesticFxViolations->take(50)->values()->all(),
            'missing_documentation' => $missingDocumentation->take(50)->values()->all(),
            'missing_dossiers' => $missingDossiers->take(50)->values()->all(),
            'completed_dossiers' => $completedDossiers->take(50)->values()->all(),
            'pending_repatriation' => $pendingRepatriation->take(50)->values()->all(),
            'completed_repatriation' => $completedRepatriation->take(50)->values()->all(),
        ];
    }

    public function getMozambiqueReverseChargeReport($filters = []): array
    {
        $fromDate = $filters['from_date'] ?? date('Y-01-01');
        $toDate = $filters['to_date'] ?? date('Y-12-31');
        $companyId = $this->companyId();

        $emptyPayload = [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'vat_rate' => 0.0,
            'summary' => [
                'total_operations' => 0,
                'total_base_amount_mzn' => 0.0,
                'total_vat_liquidated_mzn' => 0.0,
                'total_vat_supported_mzn' => 0.0,
                'missing_supplier_tax_identifier_count' => 0,
                'missing_service_type_count' => 0,
                'missing_supplier_country_count' => 0,
            ],
            'operations' => [],
            'missing_supplier_tax_identifier' => [],
            'missing_service_type' => [],
            'missing_supplier_country' => [],
        ];

        if (!Schema::hasTable('vendor_payments') || !Schema::hasColumn('vendor_payments', 'payment_date')) {
            return $emptyPayload;
        }

        $hasStatus = Schema::hasColumn('vendor_payments', 'status');
        $hasAmountMzn = Schema::hasColumn('vendor_payments', 'amount_mzn');
        $hasInternationalPayment = Schema::hasColumn('vendor_payments', 'is_international_payment');
        $hasCurrencyCode = Schema::hasColumn('vendor_payments', 'currency_code');
        $hasServiceType = Schema::hasColumn('vendor_payments', 'service_type');
        $hasBeneficiaryCountry = Schema::hasColumn('vendor_payments', 'beneficiary_country');
        $hasVendorTaxNumber = Schema::hasColumn('vendors', 'tax_number');
        $hasVendorForeignTaxNumber = Schema::hasColumn('vendors', 'foreign_tax_number');
        $hasVendorResidencyStatus = Schema::hasColumn('vendors', 'fiscal_residency_status');
        $hasVendorFiscalCountry = Schema::hasColumn('vendors', 'fiscal_country');
        $hasReverseChargeApplicable = Schema::hasColumn('vendors', 'reverse_charge_applicable');

        $reverseChargeRate = $this->resolveReverseChargeVatRate();

        $operations = DB::table('vendor_payments as vp')
            ->leftJoin('vendors as v', function ($join) use ($companyId): void {
                $join->on('v.user_id', '=', 'vp.vendor_id')
                    ->where('v.created_by', '=', $companyId);
            })
            ->leftJoin('users as u', 'u.id', '=', 'vp.vendor_id')
            ->where('vp.created_by', $companyId)
            ->whereBetween('vp.payment_date', [$fromDate, $toDate])
            ->when($hasStatus, static function ($query): void {
                $query->where('vp.status', '!=', 'cancelled');
            })
            ->select([
                'vp.id',
                'vp.payment_number',
                'vp.payment_date',
                'vp.payment_amount',
                DB::raw($hasAmountMzn ? 'vp.amount_mzn as amount_mzn' : 'vp.payment_amount as amount_mzn'),
                DB::raw($hasCurrencyCode ? 'vp.currency_code as currency_code' : "'MZN' as currency_code"),
                DB::raw($hasInternationalPayment ? 'vp.is_international_payment as is_international_payment' : '0 as is_international_payment'),
                DB::raw($hasServiceType ? 'vp.service_type as service_type' : 'NULL as service_type'),
                DB::raw($hasBeneficiaryCountry ? 'vp.beneficiary_country as beneficiary_country' : 'NULL as beneficiary_country'),
                'v.company_name as vendor_company_name',
                'u.name as vendor_name',
                DB::raw($hasVendorTaxNumber ? 'v.tax_number as vendor_tax_number' : 'NULL as vendor_tax_number'),
                DB::raw($hasVendorForeignTaxNumber ? 'v.foreign_tax_number as vendor_foreign_tax_number' : 'NULL as vendor_foreign_tax_number'),
                DB::raw($hasVendorResidencyStatus ? 'v.fiscal_residency_status as vendor_residency_status' : "'resident' as vendor_residency_status"),
                DB::raw($hasVendorFiscalCountry ? 'v.fiscal_country as vendor_fiscal_country' : 'NULL as vendor_fiscal_country'),
                DB::raw($hasReverseChargeApplicable ? 'v.reverse_charge_applicable as reverse_charge_applicable' : '0 as reverse_charge_applicable'),
            ])
            ->orderBy('vp.payment_date')
            ->orderBy('vp.id')
            ->get()
            ->map(function ($row) use ($reverseChargeRate): ?array {
                $currencyCode = strtoupper((string) ($row->currency_code ?? 'MZN'));
                if ($currencyCode === '') {
                    $currencyCode = 'MZN';
                }

                $amountMzn = round((float) ($row->amount_mzn ?? $row->payment_amount ?? 0), 2);
                $residencyStatus = strtolower(trim((string) ($row->vendor_residency_status ?? 'resident')));
                if (!in_array($residencyStatus, ['resident', 'non_resident'], true)) {
                    $residencyStatus = 'resident';
                }

                $isInternational = (bool) ($row->is_international_payment ?? false) || $currencyCode !== 'MZN';
                $reverseChargeApplicable = (bool) ($row->reverse_charge_applicable ?? false);
                $subjectToReverseCharge = $isInternational && ($residencyStatus === 'non_resident' || $reverseChargeApplicable);

                if (!$subjectToReverseCharge) {
                    return null;
                }

                $supplierCountry = trim((string) ($row->beneficiary_country ?: $row->vendor_fiscal_country ?: ''));
                $supplierTaxIdentifier = trim((string) ($row->vendor_tax_number ?: $row->vendor_foreign_tax_number ?: ''));
                $serviceType = trim((string) ($row->service_type ?? ''));
                $vatAmount = round($amountMzn * $reverseChargeRate / 100, 2);

                return [
                    'payment_id' => (int) $row->id,
                    'payment_reference' => (string) ($row->payment_number ?? ('VP-' . $row->id)),
                    'payment_date' => (string) ($row->payment_date ?? ''),
                    'supplier' => (string) ($row->vendor_company_name ?: $row->vendor_name ?: ''),
                    'supplier_country' => $supplierCountry,
                    'supplier_tax_identifier' => $supplierTaxIdentifier !== '' ? $supplierTaxIdentifier : null,
                    'supplier_residency_status' => $residencyStatus,
                    'service_type' => $serviceType !== '' ? $serviceType : null,
                    'currency_code' => $currencyCode,
                    'base_amount_mzn' => $amountMzn,
                    'vat_rate' => $reverseChargeRate,
                    'vat_liquidated_mzn' => $vatAmount,
                    'vat_supported_mzn' => $vatAmount,
                    'reverse_charge_applicable' => $reverseChargeApplicable,
                    'missing_supplier_tax_identifier' => $supplierTaxIdentifier === '',
                    'missing_service_type' => $serviceType === '',
                    'missing_supplier_country' => $supplierCountry === '',
                ];
            })
            ->filter()
            ->values();

        $missingSupplierTaxIdentifier = $operations
            ->filter(static fn (array $row): bool => (bool) ($row['missing_supplier_tax_identifier'] ?? false))
            ->values();
        $missingServiceType = $operations
            ->filter(static fn (array $row): bool => (bool) ($row['missing_service_type'] ?? false))
            ->values();
        $missingSupplierCountry = $operations
            ->filter(static fn (array $row): bool => (bool) ($row['missing_supplier_country'] ?? false))
            ->values();

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'vat_rate' => $reverseChargeRate,
            'summary' => [
                'total_operations' => $operations->count(),
                'total_base_amount_mzn' => round((float) $operations->sum('base_amount_mzn'), 2),
                'total_vat_liquidated_mzn' => round((float) $operations->sum('vat_liquidated_mzn'), 2),
                'total_vat_supported_mzn' => round((float) $operations->sum('vat_supported_mzn'), 2),
                'missing_supplier_tax_identifier_count' => $missingSupplierTaxIdentifier->count(),
                'missing_service_type_count' => $missingServiceType->count(),
                'missing_supplier_country_count' => $missingSupplierCountry->count(),
            ],
            'operations' => $operations->all(),
            'missing_supplier_tax_identifier' => $missingSupplierTaxIdentifier->take(100)->values()->all(),
            'missing_service_type' => $missingServiceType->take(100)->values()->all(),
            'missing_supplier_country' => $missingSupplierCountry->take(100)->values()->all(),
        ];
    }

    public function getMozambiqueInternationalWithholdingReport($filters = []): array
    {
        $fromDate = $filters['from_date'] ?? date('Y-01-01');
        $toDate = $filters['to_date'] ?? date('Y-12-31');
        $companyId = $this->companyId();

        $emptyPayload = [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'summary' => [
                'total_operations' => 0,
                'total_gross_amount' => 0.0,
                'total_withholding_amount' => 0.0,
                'total_net_amount' => 0.0,
                'adt_applied_count' => 0,
                'pending_state_payment_count' => 0,
                'missing_supporting_documents_count' => 0,
            ],
            'operations' => [],
            'missing_supporting_documents' => [],
        ];

        if (!Schema::hasTable('withholding_tax_transactions') || !Schema::hasColumn('withholding_tax_transactions', 'transaction_date')) {
            return $emptyPayload;
        }

        $hasCompanyId = Schema::hasColumn('withholding_tax_transactions', 'company_id');
        $hasCreatedBy = Schema::hasColumn('withholding_tax_transactions', 'created_by');
        if (!$hasCompanyId && !$hasCreatedBy) {
            return $emptyPayload;
        }

        $hasStatus = Schema::hasColumn('withholding_tax_transactions', 'status');
        $hasBeneficiaryCountry = Schema::hasColumn('withholding_tax_transactions', 'beneficiary_country');
        $hasBeneficiaryResidencyStatus = Schema::hasColumn('withholding_tax_transactions', 'beneficiary_residency_status');
        $hasIncomeTypeSnapshot = Schema::hasColumn('withholding_tax_transactions', 'income_type_snapshot');
        $hasWithholdingTreatment = Schema::hasColumn('withholding_tax_transactions', 'withholding_treatment');
        $hasAdtApplied = Schema::hasColumn('withholding_tax_transactions', 'adt_applied');
        $hasAdtCertificate = Schema::hasColumn('withholding_tax_transactions', 'adt_certificate_reference');
        $hasFiscalComplianceReference = Schema::hasColumn('withholding_tax_transactions', 'fiscal_compliance_reference');
        $hasFinancialApprovalReference = Schema::hasColumn('withholding_tax_transactions', 'financial_approval_reference');
        $hasFxAuthorizationReference = Schema::hasColumn('withholding_tax_transactions', 'fx_authorization_reference');
        $hasSourceReferenceType = Schema::hasColumn('withholding_tax_transactions', 'source_reference_type');
        $hasVendorTaxNumber = Schema::hasColumn('vendors', 'tax_number');
        $hasVendorForeignTaxNumber = Schema::hasColumn('vendors', 'foreign_tax_number');
        $hasVendorFiscalCountry = Schema::hasColumn('vendors', 'fiscal_country');

        $operations = DB::table('withholding_tax_transactions as wtt')
            ->leftJoin('vendors as v', function ($join) use ($companyId): void {
                $join->on('v.user_id', '=', 'wtt.vendor_id')
                    ->where('v.created_by', '=', $companyId);
            })
            ->leftJoin('users as u', 'u.id', '=', 'wtt.vendor_id')
            ->when($hasCompanyId, static function ($query) use ($companyId): void {
                $query->where('wtt.company_id', $companyId);
            })
            ->when(!$hasCompanyId && $hasCreatedBy, static function ($query) use ($companyId): void {
                $query->where('wtt.created_by', $companyId);
            })
            ->whereBetween('wtt.transaction_date', [$fromDate, $toDate])
            ->select([
                'wtt.id',
                'wtt.transaction_date',
                'wtt.document_reference',
                'wtt.vendor_name',
                'wtt.vendor_nuit',
                'wtt.gross_amount',
                'wtt.withholding_rate',
                'wtt.withholding_amount',
                'wtt.net_amount',
                DB::raw($hasStatus ? 'wtt.status as status' : "'pending' as status"),
                DB::raw($hasBeneficiaryCountry ? 'wtt.beneficiary_country as beneficiary_country' : 'NULL as beneficiary_country'),
                DB::raw($hasBeneficiaryResidencyStatus ? 'wtt.beneficiary_residency_status as beneficiary_residency_status' : 'NULL as beneficiary_residency_status'),
                DB::raw($hasIncomeTypeSnapshot ? 'wtt.income_type_snapshot as income_type_snapshot' : 'NULL as income_type_snapshot'),
                DB::raw($hasWithholdingTreatment ? 'wtt.withholding_treatment as withholding_treatment' : "'withheld' as withholding_treatment"),
                DB::raw($hasAdtApplied ? 'wtt.adt_applied as adt_applied' : '0 as adt_applied'),
                DB::raw($hasAdtCertificate ? 'wtt.adt_certificate_reference as adt_certificate_reference' : 'NULL as adt_certificate_reference'),
                DB::raw($hasFiscalComplianceReference ? 'wtt.fiscal_compliance_reference as fiscal_compliance_reference' : 'NULL as fiscal_compliance_reference'),
                DB::raw($hasFinancialApprovalReference ? 'wtt.financial_approval_reference as financial_approval_reference' : 'NULL as financial_approval_reference'),
                DB::raw($hasFxAuthorizationReference ? 'wtt.fx_authorization_reference as fx_authorization_reference' : 'NULL as fx_authorization_reference'),
                DB::raw($hasSourceReferenceType ? 'wtt.source_reference_type as source_reference_type' : "'vendor_payment' as source_reference_type"),
                DB::raw($hasVendorTaxNumber ? 'v.tax_number as vendor_tax_number' : 'NULL as vendor_tax_number'),
                DB::raw($hasVendorForeignTaxNumber ? 'v.foreign_tax_number as vendor_foreign_tax_number' : 'NULL as vendor_foreign_tax_number'),
                DB::raw($hasVendorFiscalCountry ? 'v.fiscal_country as vendor_fiscal_country' : 'NULL as vendor_fiscal_country'),
                'v.company_name as vendor_company_name',
                'u.name as vendor_user_name',
            ])
            ->orderBy('wtt.transaction_date')
            ->orderBy('wtt.id')
            ->get()
            ->map(function ($row): ?array {
                $country = strtoupper(trim((string) ($row->beneficiary_country ?: $row->vendor_fiscal_country ?: '')));
                $residencyStatus = strtolower(trim((string) ($row->beneficiary_residency_status ?? '')));
                if (!in_array($residencyStatus, ['resident', 'non_resident'], true)) {
                    $residencyStatus = $country !== '' && !$this->isMozambiqueCountry($country)
                        ? 'non_resident'
                        : 'resident';
                }

                $isInternational = $residencyStatus === 'non_resident'
                    || ($country !== '' && !$this->isMozambiqueCountry($country));
                if (!$isInternational) {
                    return null;
                }

                $beneficiaryTaxIdentifier = trim((string) (
                    $row->vendor_nuit
                    ?: $row->vendor_tax_number
                    ?: $row->vendor_foreign_tax_number
                    ?: ''
                ));
                $adtApplied = (bool) ($row->adt_applied ?? false);
                $adtCertificateReference = trim((string) ($row->adt_certificate_reference ?? ''));
                $fiscalComplianceReference = trim((string) ($row->fiscal_compliance_reference ?? ''));
                $financialApprovalReference = trim((string) ($row->financial_approval_reference ?? ''));
                $fxAuthorizationReference = trim((string) ($row->fx_authorization_reference ?? ''));
                $sourceReferenceType = strtolower(trim((string) ($row->source_reference_type ?? 'vendor_payment')));
                $missingSupportingDocuments = $fiscalComplianceReference === ''
                    || $financialApprovalReference === ''
                    || ($sourceReferenceType === 'vendor_payment' && $fxAuthorizationReference === '')
                    || ($adtApplied && $adtCertificateReference === '');

                return [
                    'transaction_id' => (int) $row->id,
                    'transaction_date' => (string) ($row->transaction_date ?? ''),
                    'document_reference' => (string) ($row->document_reference ?? ''),
                    'beneficiary' => (string) ($row->vendor_name ?: $row->vendor_company_name ?: $row->vendor_user_name ?: ''),
                    'beneficiary_country' => $country !== '' ? $country : null,
                    'beneficiary_residency_status' => $residencyStatus,
                    'beneficiary_tax_identifier' => $beneficiaryTaxIdentifier !== '' ? $beneficiaryTaxIdentifier : null,
                    'income_type' => trim((string) ($row->income_type_snapshot ?? '')) ?: null,
                    'withholding_treatment' => trim((string) ($row->withholding_treatment ?? 'withheld')) ?: 'withheld',
                    'gross_amount' => round((float) ($row->gross_amount ?? 0), 2),
                    'withholding_rate' => round((float) ($row->withholding_rate ?? 0), 2),
                    'withholding_amount' => round((float) ($row->withholding_amount ?? 0), 2),
                    'net_amount' => round((float) ($row->net_amount ?? 0), 2),
                    'status' => strtolower(trim((string) ($row->status ?? 'pending'))),
                    'adt_applied' => $adtApplied,
                    'adt_certificate_reference' => $adtCertificateReference !== '' ? $adtCertificateReference : null,
                    'fiscal_compliance_reference' => $fiscalComplianceReference !== '' ? $fiscalComplianceReference : null,
                    'financial_approval_reference' => $financialApprovalReference !== '' ? $financialApprovalReference : null,
                    'fx_authorization_reference' => $fxAuthorizationReference !== '' ? $fxAuthorizationReference : null,
                    'missing_supporting_documents' => $missingSupportingDocuments,
                ];
            })
            ->filter()
            ->values();

        $missingSupportingDocuments = $operations
            ->filter(static fn (array $row): bool => (bool) ($row['missing_supporting_documents'] ?? false))
            ->values();

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'summary' => [
                'total_operations' => $operations->count(),
                'total_gross_amount' => round((float) $operations->sum('gross_amount'), 2),
                'total_withholding_amount' => round((float) $operations->sum('withholding_amount'), 2),
                'total_net_amount' => round((float) $operations->sum('net_amount'), 2),
                'adt_applied_count' => $operations->where('adt_applied', true)->count(),
                'pending_state_payment_count' => $operations->filter(static fn (array $row): bool => (string) ($row['status'] ?? '') !== 'paid')->count(),
                'missing_supporting_documents_count' => $missingSupportingDocuments->count(),
            ],
            'operations' => $operations->all(),
            'missing_supporting_documents' => $missingSupportingDocuments->take(100)->values()->all(),
        ];
    }

    public function getMozambiqueInvoicingReport($filters = []): array
    {
        $fromDate = $filters['from_date'] ?? date('Y-01-01');
        $toDate = $filters['to_date'] ?? date('Y-12-31');
        $companyId = $this->companyId();

        $emptyPayload = [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'summary' => [
                'total_documents' => 0,
                'total_amount' => 0.0,
                'total_tax_amount' => 0.0,
                'total_exempt_amount' => 0.0,
                'digital_operations_count' => 0,
                'cancelled_documents_count' => 0,
            ],
            'by_status' => [],
            'by_document_type' => [],
            'by_series' => [],
            'by_currency' => [],
            'vat_rates' => [],
            'top_customers' => [],
            'operations' => [],
            'missing_exemption_reason' => [],
        ];

        if (
            !Schema::hasTable('sales_invoices')
            || !Schema::hasColumn('sales_invoices', 'created_by')
            || !Schema::hasColumn('sales_invoices', 'invoice_date')
        ) {
            return $emptyPayload;
        }

        $hasStatus = Schema::hasColumn('sales_invoices', 'status');
        $hasDocumentType = Schema::hasColumn('sales_invoices', 'document_type');
        $hasDocumentSeries = Schema::hasColumn('sales_invoices', 'document_series');
        $hasFiscalSubmissionStatus = Schema::hasColumn('sales_invoices', 'fiscal_submission_status');
        $hasOperationDate = Schema::hasColumn('sales_invoices', 'operation_date');
        $hasIssuedWithDelay = Schema::hasColumn('sales_invoices', 'issued_with_delay');
        $hasLateIssueReason = Schema::hasColumn('sales_invoices', 'late_issue_reason');
        $hasIsCancelled = Schema::hasColumn('sales_invoices', 'is_cancelled');
        $hasCurrencyCode = Schema::hasColumn('sales_invoices', 'currency_code');
        $hasCustomerType = Schema::hasColumn('customers', 'customer_type');
        $hasBillingCurrency = Schema::hasColumn('customers', 'billing_currency_code');
        $hasOperationType = Schema::hasColumn('customers', 'operation_type');

        $documents = DB::table('sales_invoices as si')
            ->leftJoin('customers as c', function ($join) use ($companyId): void {
                $join->on('c.user_id', '=', 'si.customer_id')
                    ->where('c.created_by', '=', $companyId);
            })
            ->leftJoin('users as u', 'u.id', '=', 'si.customer_id')
            ->where('si.created_by', $companyId)
            ->whereBetween('si.invoice_date', [$fromDate, $toDate])
            ->select([
                'si.id',
                'si.invoice_number',
                'si.invoice_date',
                'si.customer_id',
                'si.total_amount',
                'si.tax_amount',
                DB::raw($hasStatus ? 'si.status as status' : "'posted' as status"),
                DB::raw($hasDocumentType ? 'si.document_type as document_type' : "COALESCE(si.type, 'FT') as document_type"),
                DB::raw($hasDocumentSeries ? 'si.document_series as document_series' : "'DEFAULT' as document_series"),
                DB::raw($hasFiscalSubmissionStatus ? 'si.fiscal_submission_status as fiscal_submission_status' : "'pending' as fiscal_submission_status"),
                DB::raw($hasOperationDate ? 'si.operation_date as operation_date' : 'NULL as operation_date'),
                DB::raw($hasIssuedWithDelay ? 'si.issued_with_delay as issued_with_delay' : '0 as issued_with_delay'),
                DB::raw($hasLateIssueReason ? 'si.late_issue_reason as late_issue_reason' : 'NULL as late_issue_reason'),
                DB::raw($hasIsCancelled ? 'si.is_cancelled as is_cancelled' : '0 as is_cancelled'),
                DB::raw($hasCurrencyCode ? 'si.currency_code as currency_code' : 'NULL as currency_code'),
                'c.company_name as customer_company_name',
                DB::raw($hasCustomerType ? 'c.customer_type as customer_type' : 'NULL as customer_type'),
                DB::raw($hasBillingCurrency ? 'c.billing_currency_code as billing_currency_code' : 'NULL as billing_currency_code'),
                DB::raw($hasOperationType ? 'c.operation_type as operation_type' : 'NULL as operation_type'),
                'u.name as customer_name',
            ])
            ->orderBy('si.invoice_date')
            ->orderBy('si.id')
            ->get();

        if ($documents->isEmpty()) {
            return $emptyPayload;
        }

        $lineStatsByInvoice = collect();
        if (
            Schema::hasTable('sales_invoice_items')
            && Schema::hasColumn('sales_invoice_items', 'invoice_id')
        ) {
            $hasLineCreatedBy = Schema::hasColumn('sales_invoice_items', 'created_by');
            $hasLineTaxPercentage = Schema::hasColumn('sales_invoice_items', 'tax_percentage');
            $hasLineVatCode = Schema::hasColumn('sales_invoice_items', 'vat_code');
            $hasLineTaxExemptionReason = Schema::hasColumn('sales_invoice_items', 'tax_exemption_reason');
            $hasLineTaxAmount = Schema::hasColumn('sales_invoice_items', 'tax_amount');
            $hasLineTotalAmount = Schema::hasColumn('sales_invoice_items', 'total_amount');

            $lineQuery = DB::table('sales_invoice_items as sii')
                ->join('sales_invoices as si', 'si.id', '=', 'sii.invoice_id')
                ->where('si.created_by', $companyId)
                ->whereBetween('si.invoice_date', [$fromDate, $toDate]);

            if ($hasLineCreatedBy) {
                $lineQuery->where('sii.created_by', $companyId);
            }

            $lineRows = $lineQuery
                ->select([
                    'sii.invoice_id',
                    DB::raw($hasLineTaxPercentage ? 'sii.tax_percentage as tax_percentage' : '0 as tax_percentage'),
                    DB::raw($hasLineVatCode ? 'sii.vat_code as vat_code' : 'NULL as vat_code'),
                    DB::raw($hasLineTaxExemptionReason ? 'sii.tax_exemption_reason as tax_exemption_reason' : 'NULL as tax_exemption_reason'),
                    DB::raw($hasLineTaxAmount ? 'sii.tax_amount as tax_amount' : '0 as tax_amount'),
                    DB::raw($hasLineTotalAmount ? 'sii.total_amount as total_amount' : '0 as total_amount'),
                ])
                ->get();

            $lineStatsByInvoice = $lineRows
                ->groupBy('invoice_id')
                ->map(function ($rows): array {
                    $exemptAmount = 0.0;
                    $digitalOperation = false;
                    $missingExemptionReason = false;
                    $rates = [];

                    foreach ($rows as $line) {
                        $rate = (float) ($line->tax_percentage ?? 0);
                        $vatCode = trim((string) ($line->vat_code ?? ''));
                        $taxExemptionReason = trim((string) ($line->tax_exemption_reason ?? ''));
                        $lineTaxAmount = (float) ($line->tax_amount ?? 0);
                        $lineTotalAmount = (float) ($line->total_amount ?? 0);
                        $lineTaxableBase = max($lineTotalAmount - $lineTaxAmount, 0.0);

                        $rateKey = number_format($rate, 2, '.', '');
                        if (!isset($rates[$rateKey])) {
                            $rates[$rateKey] = [
                                'rate' => (float) $rateKey,
                                'taxable_base' => 0.0,
                                'tax_amount' => 0.0,
                            ];
                        }
                        $rates[$rateKey]['taxable_base'] += $lineTaxableBase;
                        $rates[$rateKey]['tax_amount'] += $lineTaxAmount;

                        $isExemptLine = $this->isExemptVatCode($vatCode)
                            || ($rate <= 0.00001 && $lineTaxAmount <= 0.00001 && $taxExemptionReason !== '');

                        if ($isExemptLine) {
                            $exemptAmount += $lineTaxableBase;
                            if ($taxExemptionReason === '') {
                                $missingExemptionReason = true;
                            }
                        }

                        if ($this->isDigitalDescriptor($vatCode) || $this->isDigitalDescriptor($taxExemptionReason)) {
                            $digitalOperation = true;
                        }
                    }

                    return [
                        'exempt_amount' => round($exemptAmount, 2),
                        'is_digital' => $digitalOperation,
                        'missing_exemption_reason' => $missingExemptionReason,
                        'vat_rates' => $rates,
                    ];
                });
        }

        $summaryByStatus = [];
        $summaryByDocumentType = [];
        $summaryBySeries = [];
        $summaryByCurrency = [];
        $summaryByVatRate = [];
        $topCustomers = [];
        $operations = [];
        $missingExemptionReason = [];
        $totalExemptAmount = 0.0;
        $digitalOperationsCount = 0;
        $cancelledDocumentsCount = 0;

        foreach ($documents as $document) {
            $invoiceId = (int) ($document->id ?? 0);
            $lineStats = (array) ($lineStatsByInvoice->get($invoiceId) ?? []);

            $documentNumber = (string) ($document->invoice_number ?? ('INV-' . $invoiceId));
            $status = strtolower(trim((string) ($document->status ?? 'posted')));
            $fiscalSubmissionStatus = strtolower(trim((string) ($document->fiscal_submission_status ?? 'pending')));
            $documentType = strtoupper(trim((string) ($document->document_type ?? 'FT')));
            $series = strtoupper(trim((string) ($document->document_series ?? 'DEFAULT')));
            $operationType = strtolower(trim((string) ($document->operation_type ?? '')));
            $isCancelled = (bool) ($document->is_cancelled ?? false);
            if ($isCancelled) {
                $cancelledDocumentsCount++;
            }

            $currencyCode = strtoupper(trim((string) (
                $document->currency_code
                ?: $document->billing_currency_code
                ?: 'MZN'
            )));
            if ($currencyCode === '') {
                $currencyCode = 'MZN';
            }

            $totalAmount = round((float) ($document->total_amount ?? 0), 2);
            $taxAmount = round((float) ($document->tax_amount ?? 0), 2);
            $exemptAmount = round((float) ($lineStats['exempt_amount'] ?? 0), 2);
            $isDigitalOperation = (bool) ($lineStats['is_digital'] ?? false) || $this->isDigitalDescriptor($operationType);

            if ($isDigitalOperation) {
                $digitalOperationsCount++;
            }

            if ($exemptAmount > 0) {
                $totalExemptAmount += $exemptAmount;
            }

            $statusKey = $status === '' ? 'unknown' : $status;
            $summaryByStatus[$statusKey] = ($summaryByStatus[$statusKey] ?? 0) + 1;
            $summaryByDocumentType[$documentType] = ($summaryByDocumentType[$documentType] ?? 0) + 1;
            $summaryBySeries[$series] = ($summaryBySeries[$series] ?? 0) + 1;

            if (!isset($summaryByCurrency[$currencyCode])) {
                $summaryByCurrency[$currencyCode] = [
                    'currency_code' => $currencyCode,
                    'documents' => 0,
                    'total_amount' => 0.0,
                ];
            }
            $summaryByCurrency[$currencyCode]['documents']++;
            $summaryByCurrency[$currencyCode]['total_amount'] += $totalAmount;

            foreach ((array) ($lineStats['vat_rates'] ?? []) as $rateKey => $entry) {
                if (!isset($summaryByVatRate[$rateKey])) {
                    $summaryByVatRate[$rateKey] = [
                        'rate' => (float) ($entry['rate'] ?? 0),
                        'taxable_base' => 0.0,
                        'tax_amount' => 0.0,
                    ];
                }
                $summaryByVatRate[$rateKey]['taxable_base'] += (float) ($entry['taxable_base'] ?? 0);
                $summaryByVatRate[$rateKey]['tax_amount'] += (float) ($entry['tax_amount'] ?? 0);
            }

            $customerLabel = trim((string) ($document->customer_company_name ?: $document->customer_name ?: 'N/A'));
            if (!isset($topCustomers[$customerLabel])) {
                $topCustomers[$customerLabel] = [
                    'customer' => $customerLabel,
                    'documents' => 0,
                    'total_amount' => 0.0,
                    'fiscal_submission_pending' => 0,
                ];
            }
            $topCustomers[$customerLabel]['documents']++;
            $topCustomers[$customerLabel]['total_amount'] += $totalAmount;
            if (in_array($fiscalSubmissionStatus, ['pending', 'rejected'], true)) {
                $topCustomers[$customerLabel]['fiscal_submission_pending']++;
            }

            $operation = [
                'invoice_id' => $invoiceId,
                'invoice_number' => $documentNumber,
                'invoice_date' => (string) ($document->invoice_date ?? ''),
                'operation_date' => (string) ($document->operation_date ?? $document->invoice_date ?? ''),
                'document_type' => $documentType,
                'series' => $series,
                'status' => $statusKey,
                'fiscal_submission_status' => $fiscalSubmissionStatus,
                'customer' => $customerLabel,
                'customer_type' => trim((string) ($document->customer_type ?? '')) ?: null,
                'currency_code' => $currencyCode,
                'total_amount' => $totalAmount,
                'tax_amount' => $taxAmount,
                'exempt_amount' => $exemptAmount,
                'is_digital_operation' => $isDigitalOperation,
                'issued_with_delay' => (bool) ($document->issued_with_delay ?? false),
                'late_issue_reason' => trim((string) ($document->late_issue_reason ?? '')) ?: null,
                'is_cancelled' => $isCancelled,
            ];

            $operations[] = $operation;
            if ((bool) ($lineStats['missing_exemption_reason'] ?? false)) {
                $missingExemptionReason[] = $operation;
            }
        }

        ksort($summaryByStatus);
        ksort($summaryByDocumentType);
        ksort($summaryBySeries);
        ksort($summaryByCurrency);
        ksort($summaryByVatRate);
        uasort($topCustomers, static fn (array $a, array $b): int => ($b['total_amount'] <=> $a['total_amount']));

        $currencyRows = array_map(static function (array $row): array {
            return [
                'currency_code' => $row['currency_code'],
                'documents' => (int) $row['documents'],
                'total_amount' => round((float) $row['total_amount'], 2),
            ];
        }, array_values($summaryByCurrency));

        $vatRateRows = array_map(static function (array $row): array {
            return [
                'rate' => round((float) ($row['rate'] ?? 0), 2),
                'taxable_base' => round((float) ($row['taxable_base'] ?? 0), 2),
                'tax_amount' => round((float) ($row['tax_amount'] ?? 0), 2),
            ];
        }, array_values($summaryByVatRate));

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'summary' => [
                'total_documents' => count($operations),
                'total_amount' => round((float) collect($operations)->sum('total_amount'), 2),
                'total_tax_amount' => round((float) collect($operations)->sum('tax_amount'), 2),
                'total_exempt_amount' => round($totalExemptAmount, 2),
                'digital_operations_count' => $digitalOperationsCount,
                'cancelled_documents_count' => $cancelledDocumentsCount,
            ],
            'by_status' => $summaryByStatus,
            'by_document_type' => $summaryByDocumentType,
            'by_series' => $summaryBySeries,
            'by_currency' => $currencyRows,
            'vat_rates' => $vatRateRows,
            'top_customers' => array_slice(array_values($topCustomers), 0, 25),
            'operations' => array_slice($operations, 0, 500),
            'missing_exemption_reason' => array_slice($missingExemptionReason, 0, 100),
        ];
    }

    public function getMozambiqueCurrencyReport($filters = []): array
    {
        $fromDate = $filters['from_date'] ?? date('Y-01-01');
        $toDate = $filters['to_date'] ?? date('Y-12-31');
        $companyId = $this->companyId();

        $exchangeReport = $this->getMozambiqueExchangeControlReport([
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);

        $outbound = collect((array) ($exchangeReport['outbound_payments'] ?? []));
        $inbound = collect((array) ($exchangeReport['inbound_receipts'] ?? []));
        $allOperations = $outbound->concat($inbound)->values();

        $fxDifferenceEntries = collect();

        if (Schema::hasTable('vendor_payments') && Schema::hasColumn('vendor_payments', 'payment_date')) {
            $query = DB::table('vendor_payments')
                ->where('created_by', $companyId)
                ->whereBetween('payment_date', [$fromDate, $toDate]);
            if (Schema::hasColumn('vendor_payments', 'status')) {
                $query->where('status', '!=', 'cancelled');
            }

            $hasFxDifferenceAmount = Schema::hasColumn('vendor_payments', 'fx_difference_amount');
            $hasAmountMzn = Schema::hasColumn('vendor_payments', 'amount_mzn');
            $hasCurrencyCode = Schema::hasColumn('vendor_payments', 'currency_code');
            $hasForeignAmount = Schema::hasColumn('vendor_payments', 'foreign_amount');

            $rows = $query->get([
                'id',
                'payment_number',
                'payment_date',
                DB::raw($hasCurrencyCode ? 'currency_code' : "'MZN' as currency_code"),
                DB::raw($hasForeignAmount ? 'foreign_amount' : 'NULL as foreign_amount'),
                DB::raw($hasAmountMzn ? 'amount_mzn' : 'payment_amount as amount_mzn'),
                DB::raw($hasFxDifferenceAmount ? 'fx_difference_amount' : '0 as fx_difference_amount'),
            ]);

            $fxDifferenceEntries = $fxDifferenceEntries->concat($rows->map(static function ($row): array {
                return [
                    'direction' => 'outbound',
                    'reference' => (string) ($row->payment_number ?? ('VP-' . $row->id)),
                    'date' => (string) ($row->payment_date ?? ''),
                    'currency_code' => strtoupper(trim((string) ($row->currency_code ?? 'MZN'))) ?: 'MZN',
                    'foreign_amount' => round((float) ($row->foreign_amount ?? 0), 2),
                    'amount_mzn' => round((float) ($row->amount_mzn ?? 0), 2),
                    'fx_difference_amount' => round((float) ($row->fx_difference_amount ?? 0), 2),
                ];
            }));
        }

        if (Schema::hasTable('customer_payments') && Schema::hasColumn('customer_payments', 'payment_date')) {
            $query = DB::table('customer_payments')
                ->where('created_by', $companyId)
                ->whereBetween('payment_date', [$fromDate, $toDate]);
            if (Schema::hasColumn('customer_payments', 'status')) {
                $query->where('status', '!=', 'cancelled');
            }

            $hasFxDifferenceAmount = Schema::hasColumn('customer_payments', 'fx_difference_amount');
            $hasAmountMzn = Schema::hasColumn('customer_payments', 'amount_mzn');
            $hasCurrencyCode = Schema::hasColumn('customer_payments', 'currency_code');
            $hasForeignAmount = Schema::hasColumn('customer_payments', 'foreign_amount');

            $rows = $query->get([
                'id',
                'payment_number',
                'payment_date',
                DB::raw($hasCurrencyCode ? 'currency_code' : "'MZN' as currency_code"),
                DB::raw($hasForeignAmount ? 'foreign_amount' : 'NULL as foreign_amount'),
                DB::raw($hasAmountMzn ? 'amount_mzn' : 'payment_amount as amount_mzn'),
                DB::raw($hasFxDifferenceAmount ? 'fx_difference_amount' : '0 as fx_difference_amount'),
            ]);

            $fxDifferenceEntries = $fxDifferenceEntries->concat($rows->map(static function ($row): array {
                return [
                    'direction' => 'inbound',
                    'reference' => (string) ($row->payment_number ?? ('CP-' . $row->id)),
                    'date' => (string) ($row->payment_date ?? ''),
                    'currency_code' => strtoupper(trim((string) ($row->currency_code ?? 'MZN'))) ?: 'MZN',
                    'foreign_amount' => round((float) ($row->foreign_amount ?? 0), 2),
                    'amount_mzn' => round((float) ($row->amount_mzn ?? 0), 2),
                    'fx_difference_amount' => round((float) ($row->fx_difference_amount ?? 0), 2),
                ];
            }));
        }

        $currencyBreakdown = [];
        foreach ($allOperations as $operation) {
            $currencyCode = strtoupper(trim((string) ($operation['currency_code'] ?? 'MZN')));
            if ($currencyCode === '') {
                $currencyCode = 'MZN';
            }

            if (!isset($currencyBreakdown[$currencyCode])) {
                $currencyBreakdown[$currencyCode] = [
                    'currency_code' => $currencyCode,
                    'operations' => 0,
                    'amount_mzn' => 0.0,
                    'foreign_amount' => 0.0,
                ];
            }

            $currencyBreakdown[$currencyCode]['operations'] += 1;
            $currencyBreakdown[$currencyCode]['amount_mzn'] += (float) ($operation['amount_mzn'] ?? 0);
            $currencyBreakdown[$currencyCode]['foreign_amount'] += (float) ($operation['foreign_amount'] ?? 0);
        }
        ksort($currencyBreakdown);

        $exportReceipts = $inbound
            ->filter(static fn (array $row): bool => (bool) ($row['is_export_receipt'] ?? false))
            ->values();

        $pendingRepatriationRows = $exportReceipts
            ->filter(static fn (array $row): bool => (string) ($row['repatriation_status'] ?? '') !== 'completed')
            ->map(static function (array $row): array {
                $amountMzn = (float) ($row['amount_mzn'] ?? 0);
                $repatriated = (float) ($row['repatriated_amount_mzn'] ?? 0);

                return [
                    'reference' => (string) ($row['reference'] ?? ''),
                    'date' => (string) ($row['date'] ?? ''),
                    'counterparty' => (string) ($row['counterparty'] ?? ''),
                    'currency_code' => strtoupper((string) ($row['currency_code'] ?? 'MZN')),
                    'amount_mzn' => round($amountMzn, 2),
                    'repatriated_amount_mzn' => round($repatriated, 2),
                    'pending_amount_mzn' => round(max($amountMzn - $repatriated, 0), 2),
                    'repatriation_status' => (string) ($row['repatriation_status'] ?? 'pending'),
                ];
            })
            ->values();

        $totalFxDifference = round((float) $fxDifferenceEntries->sum(static fn (array $row): float => abs((float) ($row['fx_difference_amount'] ?? 0))), 2);
        $totalExportAmount = round((float) $exportReceipts->sum('amount_mzn'), 2);
        $totalRepatriatedAmount = round((float) $exportReceipts->sum('repatriated_amount_mzn'), 2);
        $pendingRepatriationAmount = round((float) $pendingRepatriationRows->sum('pending_amount_mzn'), 2);

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'summary' => [
                'total_operations' => $allOperations->count(),
                'foreign_currency_operations_count' => $allOperations->filter(static fn (array $row): bool => strtoupper((string) ($row['currency_code'] ?? 'MZN')) !== 'MZN')->count(),
                'international_payments_count' => $outbound->filter(static fn (array $row): bool => (bool) ($row['is_international'] ?? false))->count(),
                'international_receipts_count' => $inbound->filter(static fn (array $row): bool => (bool) ($row['is_international'] ?? false))->count(),
                'export_receipts_count' => $exportReceipts->count(),
                'export_receipts_amount_mzn' => $totalExportAmount,
                'repatriated_amount_mzn' => $totalRepatriatedAmount,
                'pending_repatriation_amount_mzn' => $pendingRepatriationAmount,
                'pending_repatriation_count' => $pendingRepatriationRows->count(),
                'domestic_fx_violations' => (int) data_get($exchangeReport, 'summary.domestic_fx_violations', 0),
                'missing_fx_documentation' => (int) data_get($exchangeReport, 'summary.missing_fx_documentation', 0),
                'missing_dossier_count' => (int) data_get($exchangeReport, 'summary.missing_dossier_count', 0),
                'total_fx_difference_amount_mzn' => $totalFxDifference,
            ],
            'currency_breakdown' => array_map(static function (array $row): array {
                return [
                    'currency_code' => $row['currency_code'],
                    'operations' => (int) $row['operations'],
                    'amount_mzn' => round((float) $row['amount_mzn'], 2),
                    'foreign_amount' => round((float) $row['foreign_amount'], 2),
                ];
            }, array_values($currencyBreakdown)),
            'pending_repatriation' => $pendingRepatriationRows->take(100)->values()->all(),
            'missing_documentation' => (array) data_get($exchangeReport, 'missing_documentation', []),
            'fx_difference_entries' => $fxDifferenceEntries
                ->filter(static fn (array $row): bool => abs((float) ($row['fx_difference_amount'] ?? 0)) > 0.00001)
                ->sortByDesc(static fn (array $row): float => abs((float) ($row['fx_difference_amount'] ?? 0)))
                ->take(100)
                ->values()
                ->all(),
            'exchange_control' => [
                'summary' => (array) data_get($exchangeReport, 'summary', []),
                'outbound_payments' => (array) data_get($exchangeReport, 'outbound_payments', []),
                'inbound_receipts' => (array) data_get($exchangeReport, 'inbound_receipts', []),
            ],
        ];
    }

    public function getMozambiqueTreasuryReport($filters = []): array
    {
        $fromDate = $filters['from_date'] ?? date('Y-01-01');
        $toDate = $filters['to_date'] ?? date('Y-12-31');
        $asOfDate = $filters['as_of_date'] ?? $toDate;
        $overdueCutoffDate = $asOfDate;
        $companyId = $this->companyId();

        $bankAccounts = [];
        $cashAccounts = [];
        $cashboxAccounts = [];
        $pettyCashAccounts = [];
        $bankBalanceMzn = 0.0;
        $cashBalanceMzn = 0.0;

        if (
            Schema::hasTable('bank_accounts')
            && Schema::hasColumn('bank_accounts', 'created_by')
            && Schema::hasColumn('bank_accounts', 'current_balance')
            && Schema::hasColumn('bank_accounts', 'account_type')
        ) {
            $query = DB::table('bank_accounts')
                ->where('created_by', $companyId);

            if (Schema::hasColumn('bank_accounts', 'is_active')) {
                $query->where('is_active', true);
            }

            $rows = $query
                ->orderBy('account_name')
                ->get([
                    'id',
                    'account_number',
                    'account_name',
                    'bank_name',
                    'account_type',
                    'current_balance',
                ]);

            foreach ($rows as $row) {
                $entry = [
                    'bank_account_id' => (int) ($row->id ?? 0),
                    'account_number' => (string) ($row->account_number ?? ''),
                    'account_name' => (string) ($row->account_name ?? ''),
                    'bank_name' => (string) ($row->bank_name ?? ''),
                    'account_type' => strtolower(trim((string) ($row->account_type ?? ''))),
                    'current_balance_mzn' => round((float) ($row->current_balance ?? 0), 2),
                ];

                if ($this->isCashAccountType((string) ($row->account_type ?? ''))) {
                    $cashAccounts[] = $entry;
                    $cashBalanceMzn += $entry['current_balance_mzn'];

                    $normalizedAccountType = strtolower(trim((string) ($row->account_type ?? '')));
                    if (str_contains($normalizedAccountType, 'petty') || str_contains($normalizedAccountType, 'menor')) {
                        $pettyCashAccounts[] = $entry;
                    } else {
                        $cashboxAccounts[] = $entry;
                    }

                    continue;
                }

                $bankAccounts[] = $entry;
                $bankBalanceMzn += $entry['current_balance_mzn'];
            }
        }

        $openReceivables = collect();
        if (
            Schema::hasTable('sales_invoices')
            && Schema::hasColumn('sales_invoices', 'created_by')
            && Schema::hasColumn('sales_invoices', 'invoice_date')
            && Schema::hasColumn('sales_invoices', 'due_date')
            && Schema::hasColumn('sales_invoices', 'balance_amount')
        ) {
            $query = DB::table('sales_invoices')
                ->where('created_by', $companyId)
                ->whereDate('invoice_date', '<=', $asOfDate)
                ->where('balance_amount', '>', 0);

            if (Schema::hasColumn('sales_invoices', 'status')) {
                $query->whereIn('status', ['posted', 'partial', 'paid']);
            }

            $openReceivables = $query
                ->get([
                    'id',
                    'invoice_number',
                    'due_date',
                    'balance_amount',
                ])
                ->map(static function ($row): array {
                    return [
                        'document_id' => (int) ($row->id ?? 0),
                        'document_number' => (string) ($row->invoice_number ?? ''),
                        'due_date' => (string) ($row->due_date ?? ''),
                        'balance_mzn' => round((float) ($row->balance_amount ?? 0), 2),
                    ];
                })
                ->values();
        }

        $openPayables = collect();
        if (
            Schema::hasTable('purchase_invoices')
            && Schema::hasColumn('purchase_invoices', 'created_by')
            && Schema::hasColumn('purchase_invoices', 'invoice_date')
            && Schema::hasColumn('purchase_invoices', 'due_date')
            && Schema::hasColumn('purchase_invoices', 'balance_amount')
        ) {
            $query = DB::table('purchase_invoices')
                ->where('created_by', $companyId)
                ->whereDate('invoice_date', '<=', $asOfDate)
                ->where('balance_amount', '>', 0);

            if (Schema::hasColumn('purchase_invoices', 'status')) {
                $query->whereIn('status', ['posted', 'partial', 'paid']);
            }

            $openPayables = $query
                ->get([
                    'id',
                    'invoice_number',
                    'due_date',
                    'balance_amount',
                ])
                ->map(static function ($row): array {
                    return [
                        'document_id' => (int) ($row->id ?? 0),
                        'document_number' => (string) ($row->invoice_number ?? ''),
                        'due_date' => (string) ($row->due_date ?? ''),
                        'balance_mzn' => round((float) ($row->balance_amount ?? 0), 2),
                    ];
                })
                ->values();
        }

        $overdueReceivables = $openReceivables
            ->filter(static function (array $row) use ($overdueCutoffDate): bool {
                return ($row['due_date'] ?? '') !== '' && (string) $row['due_date'] < $overdueCutoffDate;
            })
            ->sortBy('due_date')
            ->values();
        $overduePayables = $openPayables
            ->filter(static function (array $row) use ($overdueCutoffDate): bool {
                return ($row['due_date'] ?? '') !== '' && (string) $row['due_date'] < $overdueCutoffDate;
            })
            ->sortBy('due_date')
            ->values();

        $receiptsInPeriod = collect();
        if (
            Schema::hasTable('customer_payments')
            && Schema::hasColumn('customer_payments', 'created_by')
            && Schema::hasColumn('customer_payments', 'payment_date')
        ) {
            $amountColumn = Schema::hasColumn('customer_payments', 'amount_mzn') ? 'amount_mzn' : 'payment_amount';
            if (Schema::hasColumn('customer_payments', $amountColumn)) {
                $query = DB::table('customer_payments')
                    ->where('created_by', $companyId)
                    ->whereBetween('payment_date', [$fromDate, $toDate]);
                if (Schema::hasColumn('customer_payments', 'status')) {
                    $query->where('status', '!=', 'cancelled');
                }

                $receiptsInPeriod = $query
                    ->get([
                        'payment_date',
                        $amountColumn,
                    ])
                    ->map(static function ($row) use ($amountColumn): array {
                        return [
                            'date' => (string) ($row->payment_date ?? ''),
                            'amount_mzn' => round((float) ($row->{$amountColumn} ?? 0), 2),
                        ];
                    })
                    ->values();
            }
        }

        $paymentsInPeriod = collect();
        if (
            Schema::hasTable('vendor_payments')
            && Schema::hasColumn('vendor_payments', 'created_by')
            && Schema::hasColumn('vendor_payments', 'payment_date')
        ) {
            $amountColumn = Schema::hasColumn('vendor_payments', 'amount_mzn') ? 'amount_mzn' : 'payment_amount';
            if (Schema::hasColumn('vendor_payments', $amountColumn)) {
                $query = DB::table('vendor_payments')
                    ->where('created_by', $companyId)
                    ->whereBetween('payment_date', [$fromDate, $toDate]);
                if (Schema::hasColumn('vendor_payments', 'status')) {
                    $query->where('status', '!=', 'cancelled');
                }

                $paymentsInPeriod = $query
                    ->get([
                        'payment_date',
                        $amountColumn,
                    ])
                    ->map(static function ($row) use ($amountColumn): array {
                        return [
                            'date' => (string) ($row->payment_date ?? ''),
                            'amount_mzn' => round((float) ($row->{$amountColumn} ?? 0), 2),
                        ];
                    })
                    ->values();
            }
        }

        $monthlyRealized = [];
        foreach ($receiptsInPeriod as $row) {
            $period = $this->periodKeyFromDate((string) ($row['date'] ?? ''));
            if ($period === null) {
                continue;
            }

            if (!isset($monthlyRealized[$period])) {
                $monthlyRealized[$period] = [
                    'period' => $period,
                    'receipts_mzn' => 0.0,
                    'payments_mzn' => 0.0,
                    'net_flow_mzn' => 0.0,
                ];
            }
            $monthlyRealized[$period]['receipts_mzn'] += (float) ($row['amount_mzn'] ?? 0);
        }
        foreach ($paymentsInPeriod as $row) {
            $period = $this->periodKeyFromDate((string) ($row['date'] ?? ''));
            if ($period === null) {
                continue;
            }

            if (!isset($monthlyRealized[$period])) {
                $monthlyRealized[$period] = [
                    'period' => $period,
                    'receipts_mzn' => 0.0,
                    'payments_mzn' => 0.0,
                    'net_flow_mzn' => 0.0,
                ];
            }
            $monthlyRealized[$period]['payments_mzn'] += (float) ($row['amount_mzn'] ?? 0);
        }
        ksort($monthlyRealized);
        $monthlyRealizedRows = array_map(static function (array $row): array {
            $row['receipts_mzn'] = round((float) $row['receipts_mzn'], 2);
            $row['payments_mzn'] = round((float) $row['payments_mzn'], 2);
            $row['net_flow_mzn'] = round((float) $row['receipts_mzn'] - (float) $row['payments_mzn'], 2);
            return $row;
        }, array_values($monthlyRealized));

        $projectedReceivables = $openReceivables
            ->filter(static function (array $row) use ($fromDate, $toDate): bool {
                return ($row['due_date'] ?? '') >= $fromDate && ($row['due_date'] ?? '') <= $toDate;
            })
            ->values();
        $projectedPayables = $openPayables
            ->filter(static function (array $row) use ($fromDate, $toDate): bool {
                return ($row['due_date'] ?? '') >= $fromDate && ($row['due_date'] ?? '') <= $toDate;
            })
            ->values();

        $monthlyProjected = [];
        foreach ($projectedReceivables as $row) {
            $period = $this->periodKeyFromDate((string) ($row['due_date'] ?? ''));
            if ($period === null) {
                continue;
            }

            if (!isset($monthlyProjected[$period])) {
                $monthlyProjected[$period] = [
                    'period' => $period,
                    'projected_inflows_mzn' => 0.0,
                    'projected_outflows_mzn' => 0.0,
                    'projected_net_mzn' => 0.0,
                ];
            }
            $monthlyProjected[$period]['projected_inflows_mzn'] += (float) ($row['balance_mzn'] ?? 0);
        }
        foreach ($projectedPayables as $row) {
            $period = $this->periodKeyFromDate((string) ($row['due_date'] ?? ''));
            if ($period === null) {
                continue;
            }

            if (!isset($monthlyProjected[$period])) {
                $monthlyProjected[$period] = [
                    'period' => $period,
                    'projected_inflows_mzn' => 0.0,
                    'projected_outflows_mzn' => 0.0,
                    'projected_net_mzn' => 0.0,
                ];
            }
            $monthlyProjected[$period]['projected_outflows_mzn'] += (float) ($row['balance_mzn'] ?? 0);
        }
        ksort($monthlyProjected);
        $monthlyProjectedRows = array_map(static function (array $row): array {
            $row['projected_inflows_mzn'] = round((float) $row['projected_inflows_mzn'], 2);
            $row['projected_outflows_mzn'] = round((float) $row['projected_outflows_mzn'], 2);
            $row['projected_net_mzn'] = round((float) $row['projected_inflows_mzn'] - (float) $row['projected_outflows_mzn'], 2);
            return $row;
        }, array_values($monthlyProjected));

        $periodReceipts = round((float) $receiptsInPeriod->sum('amount_mzn'), 2);
        $periodPayments = round((float) $paymentsInPeriod->sum('amount_mzn'), 2);
        $projectedInflows = round((float) $projectedReceivables->sum('balance_mzn'), 2);
        $projectedOutflows = round((float) $projectedPayables->sum('balance_mzn'), 2);
        $receivablesOpen = round((float) $openReceivables->sum('balance_mzn'), 2);
        $payablesOpen = round((float) $openPayables->sum('balance_mzn'), 2);

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'as_of_date' => $asOfDate,
            'summary' => [
                'bank_balance_mzn' => round($bankBalanceMzn, 2),
                'cash_balance_mzn' => round($cashBalanceMzn, 2),
                'cashbox_account_count' => count($cashboxAccounts),
                'cashbox_balance_mzn' => round((float) array_sum(array_column($cashboxAccounts, 'current_balance_mzn')), 2),
                'petty_cash_account_count' => count($pettyCashAccounts),
                'petty_cash_balance_mzn' => round((float) array_sum(array_column($pettyCashAccounts, 'current_balance_mzn')), 2),
                'total_liquidity_mzn' => round($bankBalanceMzn + $cashBalanceMzn, 2),
                'accounts_receivable_open_mzn' => $receivablesOpen,
                'accounts_payable_open_mzn' => $payablesOpen,
                'net_working_capital_exposure_mzn' => round($receivablesOpen - $payablesOpen, 2),
                'period_receipts_mzn' => $periodReceipts,
                'period_payments_mzn' => $periodPayments,
                'period_net_cash_flow_mzn' => round($periodReceipts - $periodPayments, 2),
                'projected_inflows_mzn' => $projectedInflows,
                'projected_outflows_mzn' => $projectedOutflows,
                'projected_net_cash_flow_mzn' => round($projectedInflows - $projectedOutflows, 2),
                'overdue_receivables_count' => $overdueReceivables->count(),
                'overdue_payables_count' => $overduePayables->count(),
            ],
            'bank_accounts' => array_values($bankAccounts),
            'cash_accounts' => array_values($cashAccounts),
            'cashbox_accounts' => array_values($cashboxAccounts),
            'petty_cash_accounts' => array_values($pettyCashAccounts),
            'monthly_realized_flow' => $monthlyRealizedRows,
            'monthly_projected_flow' => $monthlyProjectedRows,
            'top_overdue_receivables' => $overdueReceivables->take(20)->values()->all(),
            'top_overdue_payables' => $overduePayables->take(20)->values()->all(),
        ];
    }

    public function getMozambiqueFinancialComplianceDashboard($filters = []): array
    {
        $fromDate = $filters['from_date'] ?? date('Y-01-01');
        $toDate = $filters['to_date'] ?? date('Y-12-31');
        $dueSoonDays = max(1, min(30, (int) ($filters['due_soon_days'] ?? 7)));

        $fiscalAlerts = $this->getMozambiqueFiscalComplianceAlerts([
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'due_soon_days' => $dueSoonDays,
        ]);
        $exchangeReport = $this->getMozambiqueExchangeControlReport([
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);
        $gifimReport = $this->getMozambiqueGifimComplianceReport([
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);
        $internationalWithholdingReport = $this->getMozambiqueInternationalWithholdingReport([
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);
        $electronicMoneyReport = $this->getMozambiqueElectronicMoneyComplianceReport([
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);
        $cashClosingReport = $this->getCashClosings();

        $fiscalAlertByCode = collect((array) ($fiscalAlerts['alerts'] ?? []))->keyBy('code');
        $fiscalAlertCount = static function ($fiscalAlertByCode, string $code): int {
            return (int) data_get($fiscalAlertByCode->get($code), 'count', 0);
        };

        $closedCashAccountsToday = collect((array) data_get($cashClosingReport, 'closings', []))
            ->filter(static function (array $closing) use ($toDate): bool {
                return (string) data_get($closing, 'status', '') === 'closed'
                    && (string) data_get($closing, 'closing_date', '') === $toDate;
            })
            ->pluck('bank_account_id')
            ->map(static fn ($bankAccountId): int => (int) $bankAccountId)
            ->unique()
            ->values();

        $cashClosingMissingCount = collect((array) data_get($cashClosingReport, 'cash_accounts', []))
            ->filter(static function (array $cashAccount) use ($closedCashAccountsToday): bool {
                return !$closedCashAccountsToday->contains((int) data_get($cashAccount, 'bank_account_id', 0));
            })
            ->count();

        $indicators = [
            [
                'code' => 'invoice_issued_with_delay',
                'label' => 'Facturas emitidas fora do prazo',
                'value' => $fiscalAlertCount($fiscalAlertByCode, 'invoice_issued_with_delay'),
                'severity' => 'high',
                'source' => 'fiscal_alerts',
            ],
            [
                'code' => 'documents_without_valid_nuit',
                'label' => 'Documentos sem NUIT válido',
                'value' => $fiscalAlertCount($fiscalAlertByCode, 'documents_without_valid_nuit'),
                'severity' => 'high',
                'source' => 'fiscal_alerts',
            ],
            [
                'code' => 'documents_without_exemption_reason',
                'label' => 'Linhas isentas sem motivo legal',
                'value' => $fiscalAlertCount($fiscalAlertByCode, 'documents_without_exemption_reason'),
                'severity' => 'high',
                'source' => 'fiscal_alerts',
            ],
            [
                'code' => 'saft_missing_for_period',
                'label' => 'SAF-T pendente para o período',
                'value' => $fiscalAlertCount($fiscalAlertByCode, 'saft_missing_for_period'),
                'severity' => 'critical',
                'source' => 'fiscal_alerts',
            ],
            [
                'code' => 'exchange_domestic_fx_violations',
                'label' => 'Violações cambiais domésticas',
                'value' => (int) data_get($exchangeReport, 'summary.domestic_fx_violations', 0),
                'severity' => 'critical',
                'source' => 'exchange_control',
            ],
            [
                'code' => 'exchange_missing_documentation',
                'label' => 'Operações cambiais sem documentação',
                'value' => (int) data_get($exchangeReport, 'summary.missing_fx_documentation', 0),
                'severity' => 'high',
                'source' => 'exchange_control',
            ],
            [
                'code' => 'exchange_pending_repatriation',
                'label' => 'Receitas de exportação sem repatriamento completo',
                'value' => (int) data_get($exchangeReport, 'summary.pending_repatriation_count', 0),
                'severity' => 'high',
                'source' => 'exchange_control',
            ],
            [
                'code' => 'gifim_pending_alerts',
                'label' => 'Alertas GIFiM por comunicar',
                'value' => (int) data_get($gifimReport, 'summary.pending_alerts', 0),
                'severity' => 'critical',
                'source' => 'gifim',
            ],
            [
                'code' => 'gifim_missing_approval_reference',
                'label' => 'Operações GIFiM sem aprovação reforçada',
                'value' => (int) data_get($gifimReport, 'summary.missing_high_value_approval_reference', 0),
                'severity' => 'high',
                'source' => 'gifim',
            ],
            [
                'code' => 'international_withholding_pending_state_payment',
                'label' => 'Retenções internacionais pendentes de entrega ao Estado',
                'value' => (int) data_get($internationalWithholdingReport, 'summary.pending_state_payment_count', 0),
                'severity' => 'high',
                'source' => 'international_withholding',
            ],
            [
                'code' => 'international_withholding_missing_documents',
                'label' => 'Retenções internacionais sem documentação de suporte',
                'value' => (int) data_get($internationalWithholdingReport, 'summary.missing_supporting_documents_count', 0),
                'severity' => 'high',
                'source' => 'international_withholding',
            ],
            [
                'code' => 'electronic_money_limit_exceeded',
                'label' => 'Contas de moeda electrónica acima do limite',
                'value' => (int) data_get($electronicMoneyReport, 'summary.monthly_limit_exceeded', 0),
                'severity' => 'critical',
                'source' => 'electronic_money',
            ],
            [
                'code' => 'cash_closing_missing_today',
                'label' => 'Fecho diário de caixa em falta',
                'value' => $cashClosingMissingCount,
                'severity' => 'medium',
                'source' => 'cash_closing',
            ],
        ];

        $activeIndicators = collect($indicators)
            ->filter(static fn (array $indicator): bool => (int) ($indicator['value'] ?? 0) > 0)
            ->values();

        $weightBySeverity = [
            'critical' => 20,
            'high' => 12,
            'medium' => 6,
            'low' => 2,
        ];
        $riskScore = 0;
        foreach ($activeIndicators as $indicator) {
            $severity = strtolower((string) ($indicator['severity'] ?? 'low'));
            $weight = $weightBySeverity[$severity] ?? 2;
            $riskScore += min((int) ($indicator['value'] ?? 0), 10) * $weight;
        }
        $riskScore = min(100, $riskScore);

        $riskLevel = 'low';
        if ($riskScore >= 75) {
            $riskLevel = 'critical';
        } elseif ($riskScore >= 45) {
            $riskLevel = 'high';
        } elseif ($riskScore >= 20) {
            $riskLevel = 'medium';
        }

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'due_soon_days' => $dueSoonDays,
            'summary' => [
                'risk_score' => $riskScore,
                'risk_level' => $riskLevel,
                'total_indicators' => count($indicators),
                'active_indicators' => $activeIndicators->count(),
                'critical_indicators' => $activeIndicators->where('severity', 'critical')->count(),
                'high_indicators' => $activeIndicators->where('severity', 'high')->count(),
                'medium_indicators' => $activeIndicators->where('severity', 'medium')->count(),
                'low_indicators' => $activeIndicators->where('severity', 'low')->count(),
            ],
            'indicators' => $indicators,
            'active_indicators' => $activeIndicators->all(),
            'details' => [
                'fiscal_alerts' => $fiscalAlerts,
                'exchange_control' => $exchangeReport,
                'gifim' => $gifimReport,
                'international_withholding' => $internationalWithholdingReport,
                'electronic_money' => $electronicMoneyReport,
                'cash_closings' => $cashClosingReport,
            ],
        ];
    }

    public function getMozambiqueGifimComplianceReport($filters = []): array
    {
        $fromDate = $filters['from_date'] ?? date('Y-01-01');
        $toDate = $filters['to_date'] ?? date('Y-12-31');
        $companyId = $this->companyId();

        $operations = collect();

        if (Schema::hasTable('vendor_payments') && Schema::hasColumn('vendor_payments', 'payment_date')) {
            $hasStatus = Schema::hasColumn('vendor_payments', 'status');
            $hasGifimAlertRequired = Schema::hasColumn('vendor_payments', 'gifim_alert_required');
            $hasGifimAlertCategory = Schema::hasColumn('vendor_payments', 'gifim_alert_category');
            $hasGifimAlertStatus = Schema::hasColumn('vendor_payments', 'gifim_alert_status');
            $hasGifimReference = Schema::hasColumn('vendor_payments', 'gifim_reference');
            $hasGifimReportedAt = Schema::hasColumn('vendor_payments', 'gifim_reported_at');
            $hasGifimReportedBy = Schema::hasColumn('vendor_payments', 'gifim_reported_by');
            $hasGifimSubmittedDocument = Schema::hasColumn('vendor_payments', 'gifim_submitted_document');
            $hasGifimJustification = Schema::hasColumn('vendor_payments', 'gifim_justification');
            $hasHighValueApprovalReference = Schema::hasColumn('vendor_payments', 'high_value_approval_reference');

            $vendorQuery = DB::table('vendor_payments as vp')
                ->leftJoin('vendors as v', function ($join) use ($companyId): void {
                    $join->on('v.user_id', '=', 'vp.vendor_id')
                        ->where('v.created_by', '=', $companyId);
                })
                ->leftJoin('users as u', 'u.id', '=', 'vp.vendor_id')
                ->where('vp.created_by', $companyId)
                ->whereBetween('vp.payment_date', [$fromDate, $toDate])
                ->select([
                    'vp.id',
                    'vp.payment_number',
                    'vp.payment_date',
                    'vp.payment_method',
                    'vp.payment_amount',
                    'vp.amount_mzn',
                    'vp.currency_code',
                    'vp.reference_number',
                    'v.company_name as counterparty_company_name',
                    'u.name as counterparty_name',
                    DB::raw($hasStatus ? 'vp.status as payment_status' : "'pending' as payment_status"),
                    DB::raw($hasGifimAlertRequired ? 'vp.gifim_alert_required as gifim_alert_required' : 'NULL as gifim_alert_required'),
                    DB::raw($hasGifimAlertCategory ? 'vp.gifim_alert_category as gifim_alert_category' : 'NULL as gifim_alert_category'),
                    DB::raw($hasGifimAlertStatus ? 'vp.gifim_alert_status as gifim_alert_status' : 'NULL as gifim_alert_status'),
                    DB::raw($hasGifimReference ? 'vp.gifim_reference as gifim_reference' : 'NULL as gifim_reference'),
                    DB::raw($hasGifimReportedAt ? 'vp.gifim_reported_at as gifim_reported_at' : 'NULL as gifim_reported_at'),
                    DB::raw($hasGifimReportedBy ? 'vp.gifim_reported_by as gifim_reported_by' : 'NULL as gifim_reported_by'),
                    DB::raw($hasGifimSubmittedDocument ? 'vp.gifim_submitted_document as gifim_submitted_document' : 'NULL as gifim_submitted_document'),
                    DB::raw($hasGifimJustification ? 'vp.gifim_justification as gifim_justification' : 'NULL as gifim_justification'),
                    DB::raw($hasHighValueApprovalReference ? 'vp.high_value_approval_reference as high_value_approval_reference' : 'NULL as high_value_approval_reference'),
                ]);

            if ($hasStatus) {
                $vendorQuery->where('vp.status', '!=', 'cancelled');
            }

            $vendorOperations = $vendorQuery
                ->orderBy('vp.payment_date')
                ->orderBy('vp.id')
                ->get()
                ->map(function ($row): array {
                    $amountMzn = round((float) ($row->amount_mzn ?? $row->payment_amount ?? 0), 2);
                    $paymentMethod = strtolower(trim((string) ($row->payment_method ?? 'other')));
                    $storedCategory = strtolower(trim((string) ($row->gifim_alert_category ?? '')));
                    $storedCategory = in_array($storedCategory, ['cash_threshold', 'electronic_threshold'], true)
                        ? $storedCategory
                        : null;
                    $computedCategory = $this->resolveGifimThresholdCategoryForPayment($paymentMethod, $amountMzn);
                    $alertCategory = $storedCategory ?? $computedCategory;

                    $storedRequired = $row->gifim_alert_required;
                    $alertRequired = (bool) $storedRequired || $alertCategory !== null;
                    $alertStatus = $this->normalizeGifimStatus((string) ($row->gifim_alert_status ?? ''), $alertRequired);

                    $highValueApprovalReference = trim((string) ($row->high_value_approval_reference ?? ''));
                    $gifimReference = trim((string) ($row->gifim_reference ?? ''));
                    $gifimSubmittedDocument = trim((string) ($row->gifim_submitted_document ?? ''));
                    $missingApprovalReference = $alertRequired && $highValueApprovalReference === '';
                    $missingCommunicationEvidence = $alertStatus === 'communicated'
                        && ($gifimReference === '' || $gifimSubmittedDocument === '');

                    return [
                        'direction' => 'outbound',
                        'payment_id' => (int) $row->id,
                        'payment_reference' => (string) ($row->payment_number ?? ('VP-' . $row->id)),
                        'payment_date' => (string) ($row->payment_date ?? ''),
                        'payment_method' => $paymentMethod,
                        'counterparty' => (string) ($row->counterparty_company_name ?: $row->counterparty_name ?: ''),
                        'currency_code' => strtoupper((string) ($row->currency_code ?? 'MZN')),
                        'amount_mzn' => $amountMzn,
                        'status' => (string) ($row->payment_status ?? 'pending'),
                        'gifim_alert_required' => $alertRequired,
                        'gifim_alert_category' => $alertCategory,
                        'gifim_alert_status' => $alertStatus,
                        'gifim_reference' => $gifimReference !== '' ? $gifimReference : null,
                        'gifim_reported_at' => $row->gifim_reported_at,
                        'gifim_reported_by' => $row->gifim_reported_by,
                        'gifim_submitted_document' => $gifimSubmittedDocument !== '' ? $gifimSubmittedDocument : null,
                        'gifim_justification' => trim((string) ($row->gifim_justification ?? '')) ?: null,
                        'high_value_approval_reference' => $highValueApprovalReference !== '' ? $highValueApprovalReference : null,
                        'missing_high_value_approval_reference' => $missingApprovalReference,
                        'missing_communication_evidence' => $missingCommunicationEvidence,
                    ];
                });

            $operations = $operations->concat($vendorOperations);
        }

        if (Schema::hasTable('customer_payments') && Schema::hasColumn('customer_payments', 'payment_date')) {
            $hasStatus = Schema::hasColumn('customer_payments', 'status');
            $hasGifimAlertRequired = Schema::hasColumn('customer_payments', 'gifim_alert_required');
            $hasGifimAlertCategory = Schema::hasColumn('customer_payments', 'gifim_alert_category');
            $hasGifimAlertStatus = Schema::hasColumn('customer_payments', 'gifim_alert_status');
            $hasGifimReference = Schema::hasColumn('customer_payments', 'gifim_reference');
            $hasGifimReportedAt = Schema::hasColumn('customer_payments', 'gifim_reported_at');
            $hasGifimReportedBy = Schema::hasColumn('customer_payments', 'gifim_reported_by');
            $hasGifimSubmittedDocument = Schema::hasColumn('customer_payments', 'gifim_submitted_document');
            $hasGifimJustification = Schema::hasColumn('customer_payments', 'gifim_justification');
            $hasHighValueApprovalReference = Schema::hasColumn('customer_payments', 'high_value_approval_reference');

            $customerQuery = DB::table('customer_payments as cp')
                ->leftJoin('customers as c', function ($join) use ($companyId): void {
                    $join->on('c.user_id', '=', 'cp.customer_id')
                        ->where('c.created_by', '=', $companyId);
                })
                ->leftJoin('users as u', 'u.id', '=', 'cp.customer_id')
                ->where('cp.created_by', $companyId)
                ->whereBetween('cp.payment_date', [$fromDate, $toDate])
                ->select([
                    'cp.id',
                    'cp.payment_number',
                    'cp.payment_date',
                    'cp.payment_method',
                    'cp.payment_amount',
                    'cp.amount_mzn',
                    'cp.currency_code',
                    'cp.reference_number',
                    'c.company_name as counterparty_company_name',
                    'u.name as counterparty_name',
                    DB::raw($hasStatus ? 'cp.status as payment_status' : "'pending' as payment_status"),
                    DB::raw($hasGifimAlertRequired ? 'cp.gifim_alert_required as gifim_alert_required' : 'NULL as gifim_alert_required'),
                    DB::raw($hasGifimAlertCategory ? 'cp.gifim_alert_category as gifim_alert_category' : 'NULL as gifim_alert_category'),
                    DB::raw($hasGifimAlertStatus ? 'cp.gifim_alert_status as gifim_alert_status' : 'NULL as gifim_alert_status'),
                    DB::raw($hasGifimReference ? 'cp.gifim_reference as gifim_reference' : 'NULL as gifim_reference'),
                    DB::raw($hasGifimReportedAt ? 'cp.gifim_reported_at as gifim_reported_at' : 'NULL as gifim_reported_at'),
                    DB::raw($hasGifimReportedBy ? 'cp.gifim_reported_by as gifim_reported_by' : 'NULL as gifim_reported_by'),
                    DB::raw($hasGifimSubmittedDocument ? 'cp.gifim_submitted_document as gifim_submitted_document' : 'NULL as gifim_submitted_document'),
                    DB::raw($hasGifimJustification ? 'cp.gifim_justification as gifim_justification' : 'NULL as gifim_justification'),
                    DB::raw($hasHighValueApprovalReference ? 'cp.high_value_approval_reference as high_value_approval_reference' : 'NULL as high_value_approval_reference'),
                ]);

            if ($hasStatus) {
                $customerQuery->where('cp.status', '!=', 'cancelled');
            }

            $customerOperations = $customerQuery
                ->orderBy('cp.payment_date')
                ->orderBy('cp.id')
                ->get()
                ->map(function ($row): array {
                    $amountMzn = round((float) ($row->amount_mzn ?? $row->payment_amount ?? 0), 2);
                    $paymentMethod = strtolower(trim((string) ($row->payment_method ?? 'other')));
                    $storedCategory = strtolower(trim((string) ($row->gifim_alert_category ?? '')));
                    $storedCategory = in_array($storedCategory, ['cash_threshold', 'electronic_threshold'], true)
                        ? $storedCategory
                        : null;
                    $computedCategory = $this->resolveGifimThresholdCategoryForPayment($paymentMethod, $amountMzn);
                    $alertCategory = $storedCategory ?? $computedCategory;

                    $storedRequired = $row->gifim_alert_required;
                    $alertRequired = (bool) $storedRequired || $alertCategory !== null;
                    $alertStatus = $this->normalizeGifimStatus((string) ($row->gifim_alert_status ?? ''), $alertRequired);

                    $highValueApprovalReference = trim((string) ($row->high_value_approval_reference ?? ''));
                    $gifimReference = trim((string) ($row->gifim_reference ?? ''));
                    $gifimSubmittedDocument = trim((string) ($row->gifim_submitted_document ?? ''));
                    $missingApprovalReference = $alertRequired && $highValueApprovalReference === '';
                    $missingCommunicationEvidence = $alertStatus === 'communicated'
                        && ($gifimReference === '' || $gifimSubmittedDocument === '');

                    return [
                        'direction' => 'inbound',
                        'payment_id' => (int) $row->id,
                        'payment_reference' => (string) ($row->payment_number ?? ('CP-' . $row->id)),
                        'payment_date' => (string) ($row->payment_date ?? ''),
                        'payment_method' => $paymentMethod,
                        'counterparty' => (string) ($row->counterparty_company_name ?: $row->counterparty_name ?: ''),
                        'currency_code' => strtoupper((string) ($row->currency_code ?? 'MZN')),
                        'amount_mzn' => $amountMzn,
                        'status' => (string) ($row->payment_status ?? 'pending'),
                        'gifim_alert_required' => $alertRequired,
                        'gifim_alert_category' => $alertCategory,
                        'gifim_alert_status' => $alertStatus,
                        'gifim_reference' => $gifimReference !== '' ? $gifimReference : null,
                        'gifim_reported_at' => $row->gifim_reported_at,
                        'gifim_reported_by' => $row->gifim_reported_by,
                        'gifim_submitted_document' => $gifimSubmittedDocument !== '' ? $gifimSubmittedDocument : null,
                        'gifim_justification' => trim((string) ($row->gifim_justification ?? '')) ?: null,
                        'high_value_approval_reference' => $highValueApprovalReference !== '' ? $highValueApprovalReference : null,
                        'missing_high_value_approval_reference' => $missingApprovalReference,
                        'missing_communication_evidence' => $missingCommunicationEvidence,
                    ];
                });

            $operations = $operations->concat($customerOperations);
        }

        $operations = $operations
            ->sortBy([
                ['payment_date', 'asc'],
                ['payment_id', 'asc'],
            ])
            ->values();

        $pendingAlerts = $operations
            ->filter(static fn (array $row): bool => (bool) ($row['gifim_alert_required'] ?? false))
            ->filter(static fn (array $row): bool => (string) ($row['gifim_alert_status'] ?? '') !== 'communicated')
            ->values();

        $communicatedAlerts = $operations
            ->filter(static fn (array $row): bool => (string) ($row['gifim_alert_status'] ?? '') === 'communicated')
            ->values();

        $missingApprovalReference = $operations
            ->filter(static fn (array $row): bool => (bool) ($row['missing_high_value_approval_reference'] ?? false))
            ->values();

        $missingCommunicationEvidence = $operations
            ->filter(static fn (array $row): bool => (bool) ($row['missing_communication_evidence'] ?? false))
            ->values();

        $requiresAttention = $operations
            ->filter(static fn (array $row): bool => (bool) ($row['gifim_alert_required'] ?? false))
            ->filter(static fn (array $row): bool => (string) ($row['gifim_alert_status'] ?? '') !== 'communicated'
                || (bool) ($row['missing_high_value_approval_reference'] ?? false)
                || (bool) ($row['missing_communication_evidence'] ?? false))
            ->values();

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'summary' => [
                'total_operations' => $operations->count(),
                'total_alert_required' => $operations->where('gifim_alert_required', true)->count(),
                'cash_threshold_alerts' => $operations->where('gifim_alert_category', 'cash_threshold')->count(),
                'electronic_threshold_alerts' => $operations->where('gifim_alert_category', 'electronic_threshold')->count(),
                'pending_alerts' => $pendingAlerts->count(),
                'communicated_alerts' => $communicatedAlerts->count(),
                'missing_high_value_approval_reference' => $missingApprovalReference->count(),
                'missing_communication_evidence' => $missingCommunicationEvidence->count(),
                'outbound_operations' => $operations->where('direction', 'outbound')->count(),
                'inbound_operations' => $operations->where('direction', 'inbound')->count(),
            ],
            'operations' => $operations->all(),
            'pending_alerts' => $pendingAlerts->take(100)->values()->all(),
            'communicated_alerts' => $communicatedAlerts->take(100)->values()->all(),
            'requires_attention' => $requiresAttention->take(100)->values()->all(),
        ];
    }

    public function getMozambiqueElectronicMoneyComplianceReport($filters = []): array
    {
        $fromDate = $filters['from_date'] ?? date('Y-01-01');
        $toDate = $filters['to_date'] ?? date('Y-12-31');

        $snapshot = $this->getElectronicMoneyComplianceSnapshot(
            $fromDate,
            $toDate,
            $this->companyId()
        );

        return array_merge([
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ], $snapshot);
    }

    public function getMozambiqueFiscalComplianceAlerts($filters = []): array
    {
        $fromDate = $filters['from_date'] ?? date('Y-01-01');
        $toDate = $filters['to_date'] ?? date('Y-12-31');
        $dueSoonDays = max(1, min(30, (int) ($filters['due_soon_days'] ?? 7)));
        $today = now()->toDateString();
        $dueSoonLimit = now()->addDays($dueSoonDays)->toDateString();
        $companyId = $this->companyId();

        $alerts = [];
        $addAlert = static function (
            array &$alerts,
            string $code,
            string $rf,
            string $severity,
            string $category,
            int $count,
            string $message,
            array $samples = [],
            array $metadata = []
        ): void {
            if ($count <= 0) {
                return;
            }

            $alerts[] = [
                'code' => $code,
                'rf' => $rf,
                'severity' => $severity,
                'category' => $category,
                'count' => $count,
                'message' => $message,
                'samples' => array_values($samples),
                'metadata' => $metadata,
            ];
        };

        if (
            Schema::hasTable('sales_invoices')
            && Schema::hasColumn('sales_invoices', 'invoice_date')
            && Schema::hasColumn('sales_invoices', 'issued_with_delay')
        ) {
            $lateInvoiceQuery = DB::table('sales_invoices')
                ->where('created_by', $companyId)
                ->whereBetween('invoice_date', [$fromDate, $toDate])
                ->where('issued_with_delay', true);

            if (Schema::hasColumn('sales_invoices', 'status')) {
                $lateInvoiceQuery->whereIn('status', ['posted', 'partial', 'paid', 'cancelled']);
            }

            $lateInvoices = (clone $lateInvoiceQuery)
                ->orderByDesc('invoice_date')
                ->limit(10)
                ->get([
                    'invoice_number',
                    'invoice_date',
                    'fiscal_issue_deadline',
                    'late_issue_reason',
                ]);

            $addAlert(
                $alerts,
                'invoice_issued_with_delay',
                'RF078/RF013',
                'high',
                'faturacao',
                (int) (clone $lateInvoiceQuery)->count(),
                'Existem facturas emitidas fora do prazo legal (5.º dia útil após a operação).',
                $lateInvoices->map(static function ($row): array {
                    return [
                        'document' => $row->invoice_number,
                        'invoice_date' => $row->invoice_date,
                        'deadline' => $row->fiscal_issue_deadline,
                        'reason' => $row->late_issue_reason,
                    ];
                })->values()->all()
            );
        }

        $missingNuit = $this->collectMissingNuitDocuments($fromDate, $toDate, $companyId);
        $addAlert(
            $alerts,
            'documents_without_valid_nuit',
            'RF078/RF006',
            'high',
            'faturacao',
            $missingNuit['count'],
            'Foram detectados documentos fiscais sem NUIT válido na contraparte.',
            $missingNuit['samples'],
            ['by_source' => $missingNuit['by_source']]
        );

        $missingClassification = $this->collectMissingCounterpartyFiscalClassificationDocuments($fromDate, $toDate, $companyId);
        $addAlert(
            $alerts,
            'counterparties_with_incomplete_fiscal_classification',
            'RF005/RF009',
            'high',
            'compliance',
            $missingClassification['count'],
            'Existem documentos fiscais associados a clientes/fornecedores com classificação fiscal incompleta.',
            $missingClassification['samples'],
            ['by_source' => $missingClassification['by_source']]
        );

        if (
            Schema::hasTable('sales_invoice_items')
            && Schema::hasTable('sales_invoices')
            && Schema::hasColumn('sales_invoice_items', 'vat_code')
            && Schema::hasColumn('sales_invoice_items', 'tax_exemption_reason')
            && Schema::hasColumn('sales_invoices', 'invoice_date')
        ) {
            $missingReasonQuery = DB::table('sales_invoice_items as sii')
                ->join('sales_invoices as si', 'si.id', '=', 'sii.invoice_id')
                ->where('si.created_by', $companyId)
                ->whereBetween('si.invoice_date', [$fromDate, $toDate])
                ->where(function ($query): void {
                    $query->whereNull('sii.tax_exemption_reason')
                        ->orWhereRaw("TRIM(COALESCE(sii.tax_exemption_reason, '')) = ''");
                });

            if (Schema::hasColumn('sales_invoices', 'status')) {
                $missingReasonQuery->whereIn('si.status', ['posted', 'partial', 'paid', 'cancelled']);
            }

            $joinedVatCodes = false;
            if (
                Schema::hasTable('mz_vat_codes')
                && Schema::hasColumn('mz_vat_codes', 'code')
                && Schema::hasColumn('mz_vat_codes', 'type')
            ) {
                $missingReasonQuery->leftJoin('mz_vat_codes as mvc', 'mvc.code', '=', 'sii.vat_code');
                $joinedVatCodes = true;
            }

            $missingReasonQuery->where(function ($query) use ($joinedVatCodes): void {
                $query->whereIn(DB::raw("UPPER(COALESCE(sii.vat_code, ''))"), ['ISE', 'ISENTO', 'NSU', 'NAO_SUJEITO', 'NÃO_SUJEITO']);

                if ($joinedVatCodes) {
                    $query->orWhereIn('mvc.type', ['exempt', 'not_subject']);
                }
            });

            $missingReasonSamples = (clone $missingReasonQuery)
                ->orderByDesc('si.invoice_date')
                ->limit(10)
                ->get([
                    'si.invoice_number',
                    'si.invoice_date',
                    'sii.vat_code',
                ]);

            $addAlert(
                $alerts,
                'documents_without_exemption_reason',
                'RF078/RF023',
                'high',
                'faturacao',
                (int) (clone $missingReasonQuery)->count(),
                'Existem linhas isentas/não sujeitas sem motivo legal de isenção.',
                $missingReasonSamples->map(static function ($row): array {
                    return [
                        'document' => $row->invoice_number,
                        'invoice_date' => $row->invoice_date,
                        'vat_code' => $row->vat_code,
                    ];
                })->values()->all()
            );
        }

        if (
            Schema::hasTable('credit_notes')
            && Schema::hasColumn('credit_notes', 'credit_note_date')
            && Schema::hasColumn('credit_notes', 'invoice_id')
            && Schema::hasColumn('credit_notes', 'return_id')
            && Schema::hasColumn('credit_notes', 'rectification_reference')
        ) {
            $creditNoteOriginQuery = DB::table('credit_notes')
                ->where('created_by', $companyId)
                ->whereBetween('credit_note_date', [$fromDate, $toDate])
                ->whereNull('invoice_id')
                ->whereNull('return_id')
                ->where(function ($query): void {
                    $query->whereNull('rectification_reference')
                        ->orWhereRaw("TRIM(COALESCE(rectification_reference, '')) = ''");
                });

            if (Schema::hasColumn('credit_notes', 'status')) {
                $creditNoteOriginQuery->whereIn('status', ['approved', 'partial', 'applied', 'cancelled']);
            }

            $creditNoteSamples = (clone $creditNoteOriginQuery)
                ->orderByDesc('credit_note_date')
                ->limit(10)
                ->get(['credit_note_number', 'credit_note_date']);

            $addAlert(
                $alerts,
                'credit_notes_without_origin_document',
                'RF078/RF017',
                'medium',
                'faturacao',
                (int) (clone $creditNoteOriginQuery)->count(),
                'Existem notas de crédito sem referência ao documento de origem.',
                $creditNoteSamples->map(static function ($row): array {
                    return [
                        'document' => $row->credit_note_number,
                        'date' => $row->credit_note_date,
                    ];
                })->values()->all()
            );
        }

        $seriesGap = $this->detectSalesInvoiceSeriesGaps($fromDate, $toDate, $companyId);
        $addAlert(
            $alerts,
            'document_series_gaps_detected',
            'RF078/RF015',
            'medium',
            'faturacao',
            $seriesGap['missing_sequences'],
            'Foram detectadas falhas de sequência em séries documentais de faturação.',
            $seriesGap['samples'],
            ['series_with_gaps' => $seriesGap['series_with_gaps']]
        );

        $calendarAlerts = $this->buildFiscalCalendarDeadlineAlerts($companyId, $today, $dueSoonLimit);
        foreach ($calendarAlerts as $calendarAlert) {
            $addAlert(
                $alerts,
                $calendarAlert['code'],
                $calendarAlert['rf'],
                $calendarAlert['severity'],
                $calendarAlert['category'],
                (int) $calendarAlert['count'],
                $calendarAlert['message'],
                $calendarAlert['samples'] ?? [],
                $calendarAlert['metadata'] ?? []
            );
        }

        $submissionBacklog = $this->collectFiscalSubmissionBacklog($fromDate, $toDate, $companyId);
        $addAlert(
            $alerts,
            'fiscal_submission_backlog',
            'RF079/RF033',
            'high',
            'compliance',
            $submissionBacklog['total'],
            'Existem documentos fiscais pendentes/rejeitados para submissão.',
            $submissionBacklog['samples'],
            ['by_source' => $submissionBacklog['by_source']]
        );

        $fxCompliance = $this->getMozambiqueExchangeControlReport([
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);

        $addAlert(
            $alerts,
            'domestic_fx_operations_detected',
            'RF081/RF058',
            'high',
            'cambial',
            (int) data_get($fxCompliance, 'summary.domestic_fx_violations', 0),
            'Foram detectadas operações domésticas em moeda estrangeira sem conversão para MZN.',
            array_slice((array) data_get($fxCompliance, 'domestic_fx_violations', []), 0, 10)
        );

        $addAlert(
            $alerts,
            'fx_operations_without_documentation',
            'RF081/RF061',
            'high',
            'cambial',
            (int) data_get($fxCompliance, 'summary.missing_fx_documentation', 0),
            'Existem operações cambiais sem documentação obrigatória de conformidade.',
            array_slice((array) data_get($fxCompliance, 'missing_documentation', []), 0, 10)
        );

        $addAlert(
            $alerts,
            'export_repatriation_pending',
            'RF081/RF059',
            'medium',
            'cambial',
            (int) data_get($fxCompliance, 'summary.pending_repatriation_count', 0),
            'Existem receitas de exportação com repatriamento pendente.',
            array_slice((array) data_get($fxCompliance, 'pending_repatriation', []), 0, 10)
        );

        $gifimCompliance = $this->getMozambiqueGifimComplianceReport([
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);

        $addAlert(
            $alerts,
            'gifim_high_value_pending_communication',
            'RF064/RF066',
            'high',
            'compliance',
            (int) data_get($gifimCompliance, 'summary.pending_alerts', 0),
            'Existem operações de alto valor que requerem comunicação ao GIFiM e continuam pendentes.',
            array_slice((array) data_get($gifimCompliance, 'pending_alerts', []), 0, 10)
        );

        $addAlert(
            $alerts,
            'gifim_high_value_without_approval_reference',
            'RF065',
            'high',
            'compliance',
            (int) data_get($gifimCompliance, 'summary.missing_high_value_approval_reference', 0),
            'Existem operações de alto valor sem referência de aprovação reforçada.',
            array_slice((array) data_get($gifimCompliance, 'requires_attention', []), 0, 10)
        );

        $addAlert(
            $alerts,
            'gifim_communicated_without_evidence',
            'RF066',
            'medium',
            'compliance',
            (int) data_get($gifimCompliance, 'summary.missing_communication_evidence', 0),
            'Existem operações marcadas como comunicadas ao GIFiM sem evidência documental completa.',
            array_slice((array) data_get($gifimCompliance, 'requires_attention', []), 0, 10)
        );

        $electronicMoneyCompliance = $this->getElectronicMoneyComplianceSnapshot($fromDate, $toDate, $companyId);
        $addAlert(
            $alerts,
            'electronic_money_accounts_missing_classification',
            'RF067/RF068',
            'high',
            'compliance',
            (int) data_get($electronicMoneyCompliance, 'summary.missing_classification', 0),
            'Existem contas de moeda electrónica sem entidade ou nível devidamente classificados.',
            array_slice((array) data_get($electronicMoneyCompliance, 'missing_classification', []), 0, 10)
        );

        $addAlert(
            $alerts,
            'electronic_money_accounts_limit_exceeded',
            'RF070',
            'high',
            'compliance',
            (int) data_get($electronicMoneyCompliance, 'summary.monthly_limit_exceeded', 0),
            'Existem contas de moeda electrónica que excederam o limite mensal configurado.',
            array_slice((array) data_get($electronicMoneyCompliance, 'monthly_limit_exceeded', []), 0, 10)
        );

        $addAlert(
            $alerts,
            'electronic_money_accounts_limit_near_threshold',
            'RF070',
            'medium',
            'compliance',
            (int) data_get($electronicMoneyCompliance, 'summary.monthly_limit_near_threshold', 0),
            'Existem contas de moeda electrónica próximas do limite mensal configurado (>=90%).',
            array_slice((array) data_get($electronicMoneyCompliance, 'monthly_limit_near_threshold', []), 0, 10)
        );

        if (Schema::hasTable('fiscal_export_histories')) {
            $pendingSaftSubmissionQuery = DB::table('fiscal_export_histories')
                ->where('company_id', $companyId)
                ->where('export_type', 'saft_xml')
                ->where(function ($query): void {
                    $query->whereNull('submitted_at')
                        ->orWhereIn('status', ['generated', 'pending', 'rejected']);
                })
                ->where(function ($query) use ($fromDate, $toDate): void {
                    $query->whereBetween('created_at', ["{$fromDate} 00:00:00", "{$toDate} 23:59:59"])
                        ->orWhere(function ($q2) use ($fromDate, $toDate): void {
                            $q2->whereDate('period_start', '<=', $toDate)
                                ->whereDate('period_end', '>=', $fromDate);
                        });
                });

            $pendingSaftSamples = (clone $pendingSaftSubmissionQuery)
                ->orderByDesc('id')
                ->limit(10)
                ->get(['file_name', 'period_start', 'period_end', 'status']);

            $addAlert(
                $alerts,
                'saft_generated_not_submitted',
                'RF079/RF032',
                'high',
                'compliance',
                (int) (clone $pendingSaftSubmissionQuery)->count(),
                'Existem exportações SAF-T geradas sem confirmação de submissão.',
                $pendingSaftSamples->map(static function ($row): array {
                    return [
                        'file' => $row->file_name,
                        'period_start' => $row->period_start,
                        'period_end' => $row->period_end,
                        'status' => $row->status,
                    ];
                })->values()->all()
            );

            $periodSaftGenerated = DB::table('fiscal_export_histories')
                ->where('company_id', $companyId)
                ->where('export_type', 'saft_xml')
                ->whereDate('period_start', '<=', $toDate)
                ->whereDate('period_end', '>=', $fromDate)
                ->exists();

            $documentCountForPeriod = 0;
            if (Schema::hasTable('sales_invoices') && Schema::hasColumn('sales_invoices', 'invoice_date')) {
                $documentCountForPeriod += (int) DB::table('sales_invoices')
                    ->where('created_by', $companyId)
                    ->whereBetween('invoice_date', [$fromDate, $toDate])
                    ->whereIn('status', ['posted', 'partial', 'paid', 'cancelled'])
                    ->count();
            }
            if (Schema::hasTable('purchase_invoices') && Schema::hasColumn('purchase_invoices', 'invoice_date')) {
                $documentCountForPeriod += (int) DB::table('purchase_invoices')
                    ->where('created_by', $companyId)
                    ->whereBetween('invoice_date', [$fromDate, $toDate])
                    ->whereIn('status', ['posted', 'partial', 'paid', 'cancelled'])
                    ->count();
            }
            if (Schema::hasTable('pos') && Schema::hasColumn('pos', 'pos_date')) {
                $posQuery = DB::table('pos')
                    ->where('created_by', $companyId)
                    ->whereBetween('pos_date', [$fromDate, $toDate]);
                if (Schema::hasColumn('pos', 'status')) {
                    $posQuery->where('status', 'completed');
                }
                if (Schema::hasColumn('pos', 'is_cancelled')) {
                    $posQuery->where('is_cancelled', false);
                }
                $documentCountForPeriod += (int) $posQuery->count();
            }

            if ($documentCountForPeriod > 0 && !$periodSaftGenerated) {
                $addAlert(
                    $alerts,
                    'saft_missing_for_period',
                    'RF079/RF029',
                    'medium',
                    'compliance',
                    1,
                    'Não foi encontrada exportação SAF-T para o período selecionado, apesar de haver documentos fiscais.',
                    [],
                    ['documents_in_period' => $documentCountForPeriod]
                );
            }
        }

        $severityOrder = ['critical' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
        usort($alerts, static function (array $a, array $b) use ($severityOrder): int {
            $aWeight = $severityOrder[$a['severity']] ?? 99;
            $bWeight = $severityOrder[$b['severity']] ?? 99;

            if ($aWeight === $bWeight) {
                if ((int) $a['count'] === (int) $b['count']) {
                    return strcmp((string) $a['code'], (string) $b['code']);
                }

                return (int) $b['count'] <=> (int) $a['count'];
            }

            return $aWeight <=> $bWeight;
        });

        $summary = [
            'total_alerts' => count($alerts),
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0,
        ];
        foreach ($alerts as $alert) {
            $severity = strtolower((string) ($alert['severity'] ?? ''));
            if (array_key_exists($severity, $summary)) {
                $summary[$severity]++;
            }
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'today' => $today,
            'due_soon_days' => $dueSoonDays,
            'summary' => $summary,
            'alerts' => $alerts,
        ];
    }

    private function getElectronicMoneyComplianceSnapshot(string $fromDate, string $toDate, int $companyId): array
    {
        if (
            !Schema::hasTable('bank_accounts')
            || !Schema::hasColumn('bank_accounts', 'is_electronic_money_account')
            || !Schema::hasColumn('bank_accounts', 'electronic_money_entity')
            || !Schema::hasColumn('bank_accounts', 'electronic_money_level')
            || !Schema::hasColumn('bank_accounts', 'electronic_money_monthly_limit_mzn')
            || !Schema::hasColumn('bank_accounts', 'electronic_money_limit_exempt_for_enterprise')
        ) {
            return [
                'summary' => [
                    'electronic_money_accounts' => 0,
                    'missing_classification' => 0,
                    'monthly_limit_exceeded' => 0,
                    'monthly_limit_near_threshold' => 0,
                ],
                'missing_classification' => [],
                'monthly_limit_exceeded' => [],
                'monthly_limit_near_threshold' => [],
            ];
        }

        $accounts = DB::table('bank_accounts')
            ->where('created_by', $companyId)
            ->where('is_electronic_money_account', true)
            ->orderBy('id')
            ->get([
                'id',
                'account_number',
                'account_name',
                'bank_name',
                'electronic_money_entity',
                'electronic_money_level',
                'electronic_money_monthly_limit_mzn',
                'electronic_money_daily_limit_mzn',
                'electronic_money_limit_exempt_for_enterprise',
            ]);

        if ($accounts->isEmpty()) {
            return [
                'summary' => [
                    'electronic_money_accounts' => 0,
                    'missing_classification' => 0,
                    'monthly_limit_exceeded' => 0,
                    'monthly_limit_near_threshold' => 0,
                ],
                'missing_classification' => [],
                'monthly_limit_exceeded' => [],
                'monthly_limit_near_threshold' => [],
            ];
        }

        $usageByAccount = [];
        $accumulateUsage = function (string $tableName, string $dateColumn) use (&$usageByAccount, $companyId, $fromDate, $toDate): void {
            if (
                !Schema::hasTable($tableName)
                || !Schema::hasColumn($tableName, 'bank_account_id')
                || !Schema::hasColumn($tableName, $dateColumn)
                || !Schema::hasColumn($tableName, 'created_by')
            ) {
                return;
            }

            $amountColumn = Schema::hasColumn($tableName, 'amount_mzn') ? 'amount_mzn' : 'payment_amount';
            if (!Schema::hasColumn($tableName, $amountColumn)) {
                return;
            }

            $query = DB::table($tableName)
                ->where('created_by', $companyId)
                ->whereBetween($dateColumn, [$fromDate, $toDate]);

            if (Schema::hasColumn($tableName, 'status')) {
                $query->where('status', '!=', 'cancelled');
            }

            $rows = $query
                ->selectRaw("bank_account_id, COALESCE(SUM({$amountColumn}), 0) as total_amount")
                ->groupBy('bank_account_id')
                ->get();

            foreach ($rows as $row) {
                $accountId = (int) ($row->bank_account_id ?? 0);
                if ($accountId <= 0) {
                    continue;
                }

                $usageByAccount[$accountId] = (float) ($usageByAccount[$accountId] ?? 0) + (float) ($row->total_amount ?? 0);
            }
        };

        $accumulateUsage('vendor_payments', 'payment_date');
        $accumulateUsage('customer_payments', 'payment_date');

        $missingClassification = [];
        $monthlyLimitExceeded = [];
        $monthlyLimitNearThreshold = [];

        foreach ($accounts as $account) {
            $entity = trim((string) ($account->electronic_money_entity ?? ''));
            $level = strtoupper(trim((string) ($account->electronic_money_level ?? '')));
            $isExempt = (bool) ($account->electronic_money_limit_exempt_for_enterprise ?? false);
            $monthlyLimit = (float) ($account->electronic_money_monthly_limit_mzn ?? 0);
            $usageMzn = round((float) ($usageByAccount[(int) $account->id] ?? 0), 2);

            $sampleBase = [
                'bank_account_id' => (int) $account->id,
                'account_number' => (string) ($account->account_number ?? ''),
                'account_name' => (string) ($account->account_name ?? ''),
                'bank_name' => (string) ($account->bank_name ?? ''),
                'electronic_money_entity' => $entity !== '' ? $entity : null,
                'electronic_money_level' => $level !== '' ? $level : null,
            ];

            if ($entity === '' || $level === '') {
                $missingClassification[] = $sampleBase;
            }

            if ($isExempt || $monthlyLimit <= 0) {
                continue;
            }

            $usageRatio = $monthlyLimit > 0 ? round($usageMzn / $monthlyLimit, 4) : 0.0;
            $sample = array_merge($sampleBase, [
                'usage_mzn' => $usageMzn,
                'monthly_limit_mzn' => round($monthlyLimit, 2),
                'usage_ratio' => $usageRatio,
            ]);

            if ($usageMzn > $monthlyLimit) {
                $monthlyLimitExceeded[] = $sample;
                continue;
            }

            if ($usageRatio >= 0.9) {
                $monthlyLimitNearThreshold[] = $sample;
            }
        }

        return [
            'summary' => [
                'electronic_money_accounts' => $accounts->count(),
                'missing_classification' => count($missingClassification),
                'monthly_limit_exceeded' => count($monthlyLimitExceeded),
                'monthly_limit_near_threshold' => count($monthlyLimitNearThreshold),
            ],
            'missing_classification' => $missingClassification,
            'monthly_limit_exceeded' => $monthlyLimitExceeded,
            'monthly_limit_near_threshold' => $monthlyLimitNearThreshold,
        ];
    }

    private function resolveReverseChargeVatRate(): float
    {
        if (
            !Schema::hasTable('mz_vat_codes')
            || !Schema::hasColumn('mz_vat_codes', 'type')
            || !Schema::hasColumn('mz_vat_codes', 'rate')
        ) {
            return 16.0;
        }

        $query = DB::table('mz_vat_codes')->where('type', 'reverse_charge');
        if (Schema::hasColumn('mz_vat_codes', 'is_active')) {
            $query->where('is_active', true);
        }
        if (Schema::hasColumn('mz_vat_codes', 'effective_from')) {
            $query->where(function ($sub): void {
                $sub->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', now()->toDateString());
            });
        }
        if (Schema::hasColumn('mz_vat_codes', 'effective_to')) {
            $query->where(function ($sub): void {
                $sub->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', now()->toDateString());
            });
        }

        $rate = $query
            ->orderByDesc('rate')
            ->value('rate');

        return round((float) ($rate ?? 16.0), 2);
    }

    private function isCashAccountType(string $accountType): bool
    {
        $normalized = strtolower(trim($accountType));
        if ($normalized === '') {
            return false;
        }

        $cashTypes = [
            'cash',
            'petty_cash',
            'petty-cash',
            'cashbox',
            'cash_box',
            'caixa',
            'caixa_menor',
        ];

        if (in_array($normalized, $cashTypes, true)) {
            return true;
        }

        return str_contains($normalized, 'cash')
            || str_contains($normalized, 'caixa');
    }

    private function periodKeyFromDate(string $date): ?string
    {
        $date = trim($date);
        if ($date === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($date)->format('Y-m');
        } catch (\Throwable $e) {
            $timestamp = strtotime($date);
            if ($timestamp === false) {
                return null;
            }

            return date('Y-m', $timestamp);
        }
    }

    private function isExemptVatCode(string $vatCode): bool
    {
        $normalized = strtolower(trim($vatCode));
        if ($normalized === '') {
            return false;
        }

        $knownExemptCodes = [
            'isento',
            'isento_iva',
            'isento-iva',
            'isento_sem_direito',
            'isento-sem-direito',
            'exempt',
            'nao_sujeito',
            'nao-sujeito',
            'não_sujeito',
            'não-sujeito',
            'zero',
            'zero_rate',
            'zero-rate',
        ];

        if (in_array($normalized, $knownExemptCodes, true)) {
            return true;
        }

        return str_contains($normalized, 'isent')
            || str_contains($normalized, 'exempt')
            || str_contains($normalized, 'nao_suje')
            || str_contains($normalized, 'não_suje');
    }

    private function isDigitalDescriptor(string $value): bool
    {
        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            return false;
        }

        $keywords = [
            'digital',
            'cloud',
            'saas',
            'software',
            'streaming',
            'plataforma',
            'electronic',
            'eletron',
            'eletrón',
            'e-service',
            'eservice',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function isMozambiqueCountry(string $country): bool
    {
        $normalized = strtoupper(trim($country));
        $normalized = str_replace(['Á', 'À', 'Â', 'Ã', 'Ç', 'É', 'Ê', 'Í', 'Ó', 'Ô', 'Õ', 'Ú'], ['A', 'A', 'A', 'A', 'C', 'E', 'E', 'I', 'O', 'O', 'O', 'U'], $normalized);

        return in_array($normalized, ['MZ', 'MOZAMBIQUE', 'MOCAMBIQUE', 'MOZ', 'REPUBLIC OF MOZAMBIQUE'], true);
    }

    private function resolveGifimThresholdCategoryForPayment(string $paymentMethod, float $amountMzn): ?string
    {
        $paymentMethod = strtolower(trim($paymentMethod));
        if ($paymentMethod === 'cash' && $amountMzn >= 250000) {
            return 'cash_threshold';
        }

        $electronicMethods = ['bank_transfer', 'cheque', 'card', 'mobile_money', 'other'];
        if (in_array($paymentMethod, $electronicMethods, true) && $amountMzn >= 750000) {
            return 'electronic_threshold';
        }

        return null;
    }

    private function normalizeGifimStatus(string $status, bool $alertRequired): string
    {
        $status = strtolower(trim($status));
        if (!in_array($status, ['not_required', 'pending', 'communicated'], true)) {
            $status = '';
        }

        if ($alertRequired) {
            return $status === 'communicated' ? 'communicated' : 'pending';
        }

        return 'not_required';
    }

    private function collectMissingNuitDocuments(string $fromDate, string $toDate, int $companyId): array
    {
        $sources = [
            [
                'table' => 'sales_invoices',
                'date_column' => 'invoice_date',
                'number_column' => 'invoice_number',
                'party_id_column' => 'customer_id',
                'party_table' => 'customers',
                'status_values' => ['posted', 'partial', 'paid', 'cancelled'],
                'label' => 'sales_invoices',
            ],
            [
                'table' => 'purchase_invoices',
                'date_column' => 'invoice_date',
                'number_column' => 'invoice_number',
                'party_id_column' => 'vendor_id',
                'party_table' => 'vendors',
                'status_values' => ['posted', 'partial', 'paid', 'cancelled'],
                'label' => 'purchase_invoices',
            ],
        ];

        $total = 0;
        $bySource = [];
        $samples = [];

        foreach ($sources as $source) {
            if (
                !Schema::hasTable($source['table'])
                || !Schema::hasColumn($source['table'], $source['date_column'])
                || !Schema::hasColumn($source['table'], $source['number_column'])
                || !Schema::hasColumn($source['table'], $source['party_id_column'])
                || !Schema::hasColumn($source['table'], 'created_by')
            ) {
                continue;
            }

            $hasCounterpartySnapshot = Schema::hasColumn($source['table'], 'counterparty_snapshot');

            $partyTaxNumbers = collect();
            if (
                Schema::hasTable($source['party_table'])
                && Schema::hasColumn($source['party_table'], 'created_by')
                && Schema::hasColumn($source['party_table'], 'user_id')
                && Schema::hasColumn($source['party_table'], 'tax_number')
            ) {
                $partyTaxNumbers = DB::table($source['party_table'])
                    ->where('created_by', $companyId)
                    ->pluck('tax_number', 'user_id');
            }

            $query = DB::table($source['table'])
                ->where('created_by', $companyId)
                ->whereBetween($source['date_column'], [$fromDate, $toDate])
                ->select(['id', $source['number_column'], $source['party_id_column']]);

            if ($hasCounterpartySnapshot) {
                $query->addSelect('counterparty_snapshot');
            }

            if (Schema::hasColumn($source['table'], 'status')) {
                $query->whereIn('status', $source['status_values']);
            }

            $query->orderBy('id')->chunkById(500, function ($rows) use ($source, $partyTaxNumbers, &$total, &$bySource, &$samples): void {
                foreach ($rows as $row) {
                    $snapshot = $this->normaliseSnapshot($row->counterparty_snapshot ?? null);
                    $partyId = (int) ($row->{$source['party_id_column']} ?? 0);

                    $candidateTaxNumber = $this->pickFirstNonEmpty([
                        data_get($snapshot, 'tax_number'),
                        data_get($snapshot, 'nuit'),
                        $partyTaxNumbers[$partyId] ?? null,
                    ], '');

                    if (MozambiqueTaxNumber::isValidNuit($candidateTaxNumber)) {
                        continue;
                    }

                    $total++;
                    $bySource[$source['label']] = ($bySource[$source['label']] ?? 0) + 1;

                    if (count($samples) < 15) {
                        $samples[] = [
                            'source' => $source['label'],
                            'document' => (string) ($row->{$source['number_column']} ?? ('ID ' . $row->id)),
                            'nuit' => $candidateTaxNumber,
                        ];
                    }
                }
            });
        }

        ksort($bySource);

        return [
            'count' => $total,
            'by_source' => $bySource,
            'samples' => $samples,
        ];
    }

    private function collectMissingCounterpartyFiscalClassificationDocuments(string $fromDate, string $toDate, int $companyId): array
    {
        $sources = [
            [
                'table' => 'sales_invoices',
                'date_column' => 'invoice_date',
                'number_column' => 'invoice_number',
                'party_id_column' => 'customer_id',
                'party_table' => 'customers',
                'label' => 'sales_invoices',
                'residency_column' => 'fiscal_residency_status',
                'type_column' => 'customer_type',
                'operation_column' => 'operation_type',
                'country_column' => 'fiscal_country',
                'currency_column' => 'billing_currency_code',
                'vat_regime_column' => 'vat_regime',
            ],
            [
                'table' => 'purchase_invoices',
                'date_column' => 'invoice_date',
                'number_column' => 'invoice_number',
                'party_id_column' => 'vendor_id',
                'party_table' => 'vendors',
                'label' => 'purchase_invoices',
                'residency_column' => 'fiscal_residency_status',
                'type_column' => 'vendor_type',
                'operation_column' => 'supply_type',
                'country_column' => 'fiscal_country',
                'currency_column' => 'payment_currency_code',
                'vat_regime_column' => 'vat_regime',
            ],
        ];

        $total = 0;
        $bySource = [];
        $samples = [];

        foreach ($sources as $source) {
            if (
                !Schema::hasTable($source['table'])
                || !Schema::hasTable($source['party_table'])
                || !Schema::hasColumn($source['table'], $source['date_column'])
                || !Schema::hasColumn($source['table'], $source['number_column'])
                || !Schema::hasColumn($source['table'], $source['party_id_column'])
                || !Schema::hasColumn($source['table'], 'created_by')
                || !Schema::hasColumn($source['party_table'], 'user_id')
                || !Schema::hasColumn($source['party_table'], 'created_by')
                || !Schema::hasColumn($source['party_table'], $source['residency_column'])
                || !Schema::hasColumn($source['party_table'], $source['type_column'])
                || !Schema::hasColumn($source['party_table'], $source['operation_column'])
                || !Schema::hasColumn($source['party_table'], $source['country_column'])
                || !Schema::hasColumn($source['party_table'], $source['currency_column'])
                || !Schema::hasColumn($source['party_table'], $source['vat_regime_column'])
            ) {
                continue;
            }

            $query = DB::table("{$source['table']} as d")
                ->leftJoin("{$source['party_table']} as p", function ($join) use ($companyId, $source): void {
                    $join->on('p.user_id', '=', 'd.' . $source['party_id_column'])
                        ->where('p.created_by', '=', $companyId);
                })
                ->leftJoin('users as u', 'u.id', '=', 'd.' . $source['party_id_column'])
                ->where('d.created_by', $companyId)
                ->whereBetween('d.' . $source['date_column'], [$fromDate, $toDate])
                ->select([
                    'd.id',
                    'd.' . $source['number_column'] . ' as document_number',
                    'd.' . $source['date_column'] . ' as document_date',
                    'p.company_name as counterparty_company_name',
                    'u.name as counterparty_name',
                    DB::raw('p.' . $source['residency_column'] . ' as fiscal_residency_status'),
                    DB::raw('p.' . $source['type_column'] . ' as counterparty_fiscal_type'),
                    DB::raw('p.' . $source['operation_column'] . ' as counterparty_operation_type'),
                    DB::raw('p.' . $source['country_column'] . ' as fiscal_country'),
                    DB::raw('p.' . $source['currency_column'] . ' as billing_currency_code'),
                    DB::raw('p.' . $source['vat_regime_column'] . ' as vat_regime'),
                ]);

            if (Schema::hasColumn($source['table'], 'status')) {
                $query->whereIn('d.status', ['posted', 'partial', 'paid', 'cancelled']);
            }

            $rows = $query
                ->orderBy('d.id')
                ->get();

            foreach ($rows as $row) {
                $missingFields = [];
                $residencyStatus = strtolower(trim((string) ($row->fiscal_residency_status ?? '')));
                $counterpartyType = strtolower(trim((string) ($row->counterparty_fiscal_type ?? '')));
                $operationType = strtolower(trim((string) ($row->counterparty_operation_type ?? '')));
                $fiscalCountry = trim((string) ($row->fiscal_country ?? ''));
                $currencyCode = strtoupper(trim((string) ($row->billing_currency_code ?? '')));
                $vatRegime = strtolower(trim((string) ($row->vat_regime ?? '')));

                if (!in_array($residencyStatus, ['resident', 'non_resident'], true)) {
                    $missingFields[] = $source['residency_column'];
                }

                if ($counterpartyType === '') {
                    $missingFields[] = $source['type_column'];
                }

                if ($operationType === '') {
                    $missingFields[] = $source['operation_column'];
                }

                if ($residencyStatus === 'non_resident' && $fiscalCountry === '') {
                    $missingFields[] = $source['country_column'];
                }

                if ($residencyStatus === 'non_resident' && $currencyCode === '') {
                    $missingFields[] = $source['currency_column'];
                }

                if (in_array($counterpartyType, ['exempt', 'special_regime'], true) && $vatRegime === '') {
                    $missingFields[] = $source['vat_regime_column'];
                }

                if ($missingFields === []) {
                    continue;
                }

                $missingFields = array_values(array_unique($missingFields));
                $total++;
                $bySource[$source['label']] = ($bySource[$source['label']] ?? 0) + 1;

                if (count($samples) < 15) {
                    $samples[] = [
                        'source' => $source['label'],
                        'document' => (string) ($row->document_number ?? ('ID ' . $row->id)),
                        'document_date' => (string) ($row->document_date ?? ''),
                        'counterparty' => trim((string) ($row->counterparty_company_name ?: $row->counterparty_name ?: '')),
                        'missing_fields' => $missingFields,
                    ];
                }
            }
        }

        ksort($bySource);

        return [
            'count' => $total,
            'by_source' => $bySource,
            'samples' => $samples,
        ];
    }

    private function detectSalesInvoiceSeriesGaps(string $fromDate, string $toDate, int $companyId): array
    {
        if (
            !Schema::hasTable('sales_invoices')
            || !Schema::hasColumn('sales_invoices', 'document_sequence')
            || !Schema::hasColumn('sales_invoices', 'document_series')
            || !Schema::hasColumn('sales_invoices', 'document_type')
            || !Schema::hasColumn('sales_invoices', 'invoice_date')
        ) {
            return [
                'missing_sequences' => 0,
                'series_with_gaps' => 0,
                'samples' => [],
            ];
        }

        $query = DB::table('sales_invoices')
            ->where('created_by', $companyId)
            ->whereBetween('invoice_date', [$fromDate, $toDate])
            ->whereNotNull('document_sequence')
            ->select('document_type', 'document_series', 'document_sequence');

        if (Schema::hasColumn('sales_invoices', 'status')) {
            $query->whereIn('status', ['posted', 'partial', 'paid', 'cancelled']);
        }

        $rows = $query->get();
        $groups = [];
        foreach ($rows as $row) {
            $documentType = trim((string) ($row->document_type ?? 'UNK'));
            $documentSeries = trim((string) ($row->document_series ?? 'DEFAULT'));
            $sequence = (int) ($row->document_sequence ?? 0);

            if ($sequence <= 0) {
                continue;
            }

            $key = $documentType . '|' . $documentSeries;
            $groups[$key][] = $sequence;
        }

        $missingSequences = 0;
        $seriesWithGaps = 0;
        $samples = [];

        foreach ($groups as $seriesKey => $sequences) {
            $unique = array_values(array_unique(array_map('intval', $sequences)));
            sort($unique);

            $seriesMissing = 0;
            for ($index = 1, $length = count($unique); $index < $length; $index++) {
                $gap = $unique[$index] - $unique[$index - 1] - 1;
                if ($gap > 0) {
                    $seriesMissing += $gap;
                }
            }

            if ($seriesMissing <= 0) {
                continue;
            }

            $missingSequences += $seriesMissing;
            $seriesWithGaps++;

            if (count($samples) < 10) {
                [$documentType, $documentSeries] = explode('|', $seriesKey, 2);
                $samples[] = [
                    'document_type' => $documentType,
                    'document_series' => $documentSeries,
                    'missing_sequences' => $seriesMissing,
                    'first_sequence' => $unique[0] ?? null,
                    'last_sequence' => $unique[count($unique) - 1] ?? null,
                ];
            }
        }

        return [
            'missing_sequences' => $missingSequences,
            'series_with_gaps' => $seriesWithGaps,
            'samples' => $samples,
        ];
    }

    private function buildFiscalCalendarDeadlineAlerts(int $companyId, string $today, string $dueSoonLimit): array
    {
        if (
            !Schema::hasTable('fiscal_calendar_events')
            || !Schema::hasColumn('fiscal_calendar_events', 'obligation_type')
            || !Schema::hasColumn('fiscal_calendar_events', 'due_date')
            || !Schema::hasColumn('fiscal_calendar_events', 'status')
        ) {
            return [];
        }

        $query = DB::table('fiscal_calendar_events')
            ->whereNotIn('status', ['completed', 'not_applicable']);

        if (Schema::hasColumn('fiscal_calendar_events', 'company_id')) {
            $query->where('company_id', $companyId);
        } elseif (Schema::hasColumn('fiscal_calendar_events', 'created_by')) {
            $query->where('created_by', $companyId);
        } else {
            return [];
        }

        $rows = $query
            ->whereIn('obligation_type', ['vat', 'withholding', 'saft', 'irpc', 'annual_accounts'])
            ->orderBy('due_date')
            ->get(['id', 'code', 'title', 'obligation_type', 'due_date', 'status']);

        $definitions = [
            'vat' => ['overdue_code' => 'vat_deadline_overdue', 'soon_code' => 'vat_deadline_due_soon'],
            'withholding' => ['overdue_code' => 'withholding_deadline_overdue', 'soon_code' => 'withholding_deadline_due_soon'],
            'saft' => ['overdue_code' => 'saft_deadline_overdue', 'soon_code' => 'saft_deadline_due_soon'],
            'irpc' => ['overdue_code' => 'irpc_deadline_overdue', 'soon_code' => 'irpc_deadline_due_soon'],
            'annual_accounts' => ['overdue_code' => 'annual_accounts_deadline_overdue', 'soon_code' => 'annual_accounts_deadline_due_soon'],
        ];

        $alerts = [];
        foreach ($definitions as $obligation => $codes) {
            $overdue = $rows
                ->where('obligation_type', $obligation)
                ->filter(static fn ($row) => (string) $row->due_date < $today)
                ->values();

            if ($overdue->count() > 0) {
                $alerts[] = [
                    'code' => $codes['overdue_code'],
                    'rf' => 'RF079/RF095',
                    'severity' => 'high',
                    'category' => 'compliance',
                    'count' => $overdue->count(),
                    'message' => strtoupper($obligation) . ': existem obrigações com prazo vencido.',
                    'samples' => $overdue->take(8)->map(static function ($row): array {
                        return [
                            'code' => $row->code,
                            'title' => $row->title,
                            'due_date' => $row->due_date,
                            'status' => $row->status,
                        ];
                    })->values()->all(),
                ];
            }

            $dueSoon = $rows
                ->where('obligation_type', $obligation)
                ->filter(static fn ($row) => (string) $row->due_date >= $today && (string) $row->due_date <= $dueSoonLimit)
                ->values();

            if ($dueSoon->count() > 0) {
                $alerts[] = [
                    'code' => $codes['soon_code'],
                    'rf' => 'RF079/RF095',
                    'severity' => 'medium',
                    'category' => 'compliance',
                    'count' => $dueSoon->count(),
                    'message' => strtoupper($obligation) . ': existem obrigações com vencimento próximo.',
                    'samples' => $dueSoon->take(8)->map(static function ($row): array {
                        return [
                            'code' => $row->code,
                            'title' => $row->title,
                            'due_date' => $row->due_date,
                            'status' => $row->status,
                        ];
                    })->values()->all(),
                    'metadata' => ['window_until' => $dueSoonLimit],
                ];
            }
        }

        return $alerts;
    }

    private function collectFiscalSubmissionBacklog(string $fromDate, string $toDate, int $companyId): array
    {
        $sources = [
            ['table' => 'sales_invoices', 'date_column' => 'invoice_date', 'label' => 'sales_invoices', 'number_column' => 'invoice_number'],
            ['table' => 'purchase_invoices', 'date_column' => 'invoice_date', 'label' => 'purchase_invoices', 'number_column' => 'invoice_number'],
            ['table' => 'pos', 'date_column' => 'pos_date', 'label' => 'pos_sales', 'number_column' => 'sale_number'],
        ];

        $total = 0;
        $bySource = [];
        $samples = [];

        foreach ($sources as $source) {
            if (
                !Schema::hasTable($source['table'])
                || !Schema::hasColumn($source['table'], 'fiscal_submission_status')
                || !Schema::hasColumn($source['table'], $source['date_column'])
                || !Schema::hasColumn($source['table'], $source['number_column'])
            ) {
                continue;
            }

            $query = DB::table($source['table'])
                ->where('created_by', $companyId)
                ->whereBetween($source['date_column'], [$fromDate, $toDate])
                ->whereIn('fiscal_submission_status', ['pending', 'rejected']);

            if (Schema::hasColumn($source['table'], 'status') && $source['table'] === 'pos') {
                $query->where('status', 'completed');
            }

            if (Schema::hasColumn($source['table'], 'is_cancelled')) {
                $query->where('is_cancelled', false);
            }

            $count = (int) (clone $query)->count();
            if ($count <= 0) {
                continue;
            }

            $total += $count;
            $bySource[$source['label']] = $count;

            $columns = [$source['number_column'], $source['date_column'], 'fiscal_submission_status'];
            $sampleRows = (clone $query)
                ->orderByDesc($source['date_column'])
                ->limit(5)
                ->get($columns);

            foreach ($sampleRows as $row) {
                if (count($samples) >= 15) {
                    break;
                }

                $samples[] = [
                    'source' => $source['label'],
                    'document' => (string) ($row->{$source['number_column']} ?? ''),
                    'date' => (string) ($row->{$source['date_column']} ?? ''),
                    'status' => (string) ($row->fiscal_submission_status ?? ''),
                ];
            }
        }

        ksort($bySource);

        return [
            'total' => $total,
            'by_source' => $bySource,
            'samples' => $samples,
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
        $companySettings = $this->companySettings();
        $companyId = $this->companyId();

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
            ->where('created_by', $companyId)
            ->whereIn('status', ['posted', 'partial', 'paid', 'cancelled'])
            ->whereBetween('invoice_date', [$fromDate, $toDate])
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get($salesInvoiceColumns);

        $purchaseInvoices = DB::table('purchase_invoices')
            ->where('created_by', $companyId)
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
                ->where('created_by', $companyId)
                ->whereIn('user_id', $salesInvoices->pluck('customer_id')->filter()->all())
                ->pluck('tax_number', 'user_id');
        }

        $vendorTaxNumbers = collect();
        if (Schema::hasTable('vendors')) {
            $vendorTaxNumbers = DB::table('vendors')
                ->where('created_by', $companyId)
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
                (string) $companyId,
            ], (string) $companyId);

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
                (string) $companyId,
            ], (string) $companyId);

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
        $companyId = $this->companyId();
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

        $companySettings = $this->companySettings();
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
                ->where('created_by', $companyId)
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
                ->where('created_by', $companyId)
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
                ->where('created_by', $companyId)
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
                ->where('created_by', $companyId)
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
                ->where('created_by', $companyId)
                ->where('is_active', true)
                ->whereDate('effective_from', '<=', $today)
                ->where(function ($query) use ($today) {
                    $query->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', $today);
                })
                ->exists();

            $hasActiveMinimumWage = DB::table('mz_minimum_wages')
                ->where('created_by', $companyId)
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
                ->where('created_by', $companyId)
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
                ->where('created_by', $companyId)
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
                ->where('created_by', $companyId)
                ->whereIn('fiscal_submission_status', ['pending', 'rejected'])
                ->count();

            $purchaseBacklog = DB::table('purchase_invoices')
                ->where('created_by', $companyId)
                ->whereIn('fiscal_submission_status', ['pending', 'rejected'])
                ->count();

            $posBacklog = 0;
            if (Schema::hasTable('pos') && Schema::hasColumn('pos', 'fiscal_submission_status')) {
                $posBacklogQuery = DB::table('pos')
                    ->where('created_by', $companyId)
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
                ->where('created_by', $companyId)
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
                ->where('company_id', $companyId)
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
                ->where('created_by', $companyId)
                ->where('payment_method', 'mobile_money')
                ->where(function ($query) {
                    $query->whereNull('mobile_money_provider')
                        ->orWhereNull('mobile_money_number')
                        ->orWhere('mobile_money_provider', '')
                        ->orWhere('mobile_money_number', '');
                })
                ->count();

            $invalidVendorMobileMoney = DB::table('vendor_payments')
                ->where('created_by', $companyId)
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
                    ->where('created_by', $companyId)
                    ->count();
                $pilotRegistryStats['active'] = MozPilotCompany::query()
                    ->where('created_by', $companyId)
                    ->where('status', 'active')
                    ->count();
                $pilotRegistryStats['completed'] = MozPilotCompany::query()
                    ->where('created_by', $companyId)
                    ->where('status', 'completed')
                    ->count();
                $pilotRegistryStats['validated_real'] = MozPilotCompany::query()
                    ->where('created_by', $companyId)
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
                    ->where('created_by', $companyId)
                    ->where('domain', 'payroll')
                    ->count();
                $validationCaseStats['payroll_validated'] = MozPilotValidationCase::query()
                    ->where('created_by', $companyId)
                    ->where('domain', 'payroll')
                    ->where('result', 'passed')
                    ->whereNotNull('executed_at')
                    ->whereNotNull('evidence_ref')
                    ->where('evidence_ref', '!=', '')
                    ->count();
                $validationCaseStats['accounting_total'] = MozPilotValidationCase::query()
                    ->where('created_by', $companyId)
                    ->where('domain', 'accounting')
                    ->count();
                $validationCaseStats['accounting_validated'] = MozPilotValidationCase::query()
                    ->where('created_by', $companyId)
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

        $backupRestoreStatus = strtolower((string) ($attestations['backup_restore_status'] ?? 'not_started'));
        $backupRestoreCompleted = $backupRestoreStatus === 'completed'
            && !empty($attestations['backup_restore_tested_at'])
            && !empty($attestations['backup_restore_evidence_ref']);
        $backupRestoreCheckStatus = 'fail';
        if ($backupRestoreCompleted) {
            $backupRestoreCheckStatus = 'pass';
        } elseif ($backupRestoreStatus === 'in_progress') {
            $backupRestoreCheckStatus = 'warn';
        }
        $addCheck(
            'operations.backup_restore.final',
            'Backup and restore verification',
            $backupRestoreCheckStatus,
            $backupRestoreCompleted
                ? 'Backup and restore test is completed, dated and linked to evidence.'
                : ($backupRestoreStatus === 'in_progress'
                    ? 'Backup/restore verification is in progress.'
                    : 'Backup/restore verification evidence is missing.'),
            true,
            [
                'status' => $attestations['backup_restore_status'],
                'tested_at' => $attestations['backup_restore_tested_at'],
                'evidence_ref' => $attestations['backup_restore_evidence_ref'],
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

        $saftXsdRequired = (bool) config('sce.saft.require_xsd_validation', false);
        $saftXsdPath = trim((string) config('sce.saft.xsd_path', ''));
        $saftXsdAvailable = !$saftXsdRequired || ($saftXsdPath !== '' && is_file($saftXsdPath));
        $addCheck(
            'exports.saft_xsd_validation_config',
            'SAF-T XSD validation configuration',
            $saftXsdAvailable ? 'pass' : 'fail',
            $saftXsdAvailable
                ? ($saftXsdRequired
                    ? 'Official SAF-T XSD validation is enabled and the configured schema file exists.'
                    : 'Official SAF-T XSD validation is not enforced by configuration.')
                : 'SAF-T XSD validation is required, but SAFT_MZ_XSD_PATH is missing or unreadable.',
            true,
            [
                'required' => $saftXsdRequired,
                'path_configured' => $saftXsdPath !== '',
                'path_exists' => $saftXsdPath !== '' && is_file($saftXsdPath),
            ]
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
            'backup_restore_verified' => $backupRestoreCompleted,
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
                && $backupRestoreCompleted
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
        $stringSetting = fn (string $key, string $default = ''): string => (string) $this->companySetting($key, $default);

        $intSetting = fn (string $key, int $default = 0): int => (int) $this->companySetting($key, $default);

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
            'backup_restore_status' => $stringSetting('mz_go_live_backup_restore_status', 'not_started'),
            'backup_restore_tested_at' => $stringSetting('mz_go_live_backup_restore_tested_at'),
            'backup_restore_evidence_ref' => $stringSetting('mz_go_live_backup_restore_evidence_ref'),
            'backup_restore_notes' => $stringSetting('mz_go_live_backup_restore_notes'),
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
        $companyId = $this->companyId();

        $customers = DB::table('users')
            ->where('created_by', $companyId)
            ->where('type', 'client')
            ->select('id', 'name', 'email')
            ->get();

        $customerIds = $customers->pluck('id')->all();
        $invoiceTotalsByCustomer = collect();
        $returnTotalsByCustomer = collect();

        if (!empty($customerIds)) {
            $invoiceTotalsByCustomer = DB::table('sales_invoices')
                ->where('created_by', $companyId)
                ->whereIn('customer_id', $customerIds)
                ->whereIn('status', ['posted', 'partial', 'paid'])
                ->whereDate('invoice_date', '<=', $asOfDate)
                ->selectRaw('customer_id, COALESCE(SUM(total_amount),0) as total_invoiced, COALESCE(SUM(balance_amount),0) as balance')
                ->groupBy('customer_id')
                ->get()
                ->keyBy('customer_id');

            $returnTotalsByCustomer = DB::table('sales_invoice_returns')
                ->where('created_by', $companyId)
                ->whereIn('customer_id', $customerIds)
                ->whereIn('status', ['approved', 'completed'])
                ->whereDate('return_date', '<=', $asOfDate)
                ->selectRaw('customer_id, COALESCE(SUM(total_amount),0) as total_returns')
                ->groupBy('customer_id')
                ->get()
                ->keyBy('customer_id');
        }

        $balances = [];
        $totalBalance = 0;

        foreach ($customers as $customer) {
            $invoiceTotals = $invoiceTotalsByCustomer->get($customer->id);
            $returnTotals = $returnTotalsByCustomer->get($customer->id);

            $invoiced = (float) ($invoiceTotals->total_invoiced ?? 0);
            $returns = (float) ($returnTotals->total_returns ?? 0);
            $balance = (float) ($invoiceTotals->balance ?? 0);

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
        $companyId = $this->companyId();

        $vendors = DB::table('users')
            ->where('created_by', $companyId)
            ->where('type', 'vendor')
            ->select('id', 'name', 'email')
            ->get();

        $vendorIds = $vendors->pluck('id')->all();
        $invoiceTotalsByVendor = collect();
        $returnTotalsByVendor = collect();

        if (!empty($vendorIds)) {
            $invoiceTotalsByVendor = DB::table('purchase_invoices')
                ->where('created_by', $companyId)
                ->whereIn('vendor_id', $vendorIds)
                ->whereIn('status', ['posted', 'partial', 'paid'])
                ->whereDate('invoice_date', '<=', $asOfDate)
                ->selectRaw('vendor_id, COALESCE(SUM(total_amount),0) as total_billed, COALESCE(SUM(balance_amount),0) as balance')
                ->groupBy('vendor_id')
                ->get()
                ->keyBy('vendor_id');

            $returnTotalsByVendor = DB::table('purchase_returns')
                ->where('created_by', $companyId)
                ->whereIn('vendor_id', $vendorIds)
                ->whereIn('status', ['approved', 'completed'])
                ->whereDate('return_date', '<=', $asOfDate)
                ->selectRaw('vendor_id, COALESCE(SUM(total_amount),0) as total_returns')
                ->groupBy('vendor_id')
                ->get()
                ->keyBy('vendor_id');
        }

        $balances = [];
        $totalBalance = 0;

        foreach ($vendors as $vendor) {
            $invoiceTotals = $invoiceTotalsByVendor->get($vendor->id);
            $returnTotals = $returnTotalsByVendor->get($vendor->id);

            $billed = (float) ($invoiceTotals->total_billed ?? 0);
            $returns = (float) ($returnTotals->total_returns ?? 0);
            $balance = (float) ($invoiceTotals->balance ?? 0);

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
        $companyId = $this->companyId();

        $customer = DB::table('users')
            ->leftJoin('customers', function ($join) use ($companyId) {
                $join->on('customers.user_id', '=', 'users.id')
                    ->where('customers.created_by', $companyId);
            })
            ->where('users.id', $customerId)
            ->where('users.type', 'client')
            ->where('users.created_by', $companyId)
            ->select('users.id', 'users.name', 'users.email', 'customers.company_name', 'customers.tax_number')
            ->first();

        if (!$customer) {
            return null;
        }

        $invoicesQuery = DB::table('sales_invoices')
            ->where('created_by', $companyId)
            ->where('customer_id', $customerId)
            ->whereIn('status', ['posted', 'partial', 'paid'])
            ->select('invoice_number', 'invoice_date as date', 'due_date', 'subtotal', 'tax_amount', 'total_amount', 'balance_amount', 'status');

        if ($startDate) $invoicesQuery->where('invoice_date', '>=', $startDate);
        if ($endDate) $invoicesQuery->where('invoice_date', '<=', $endDate);
        $invoices = $invoicesQuery->orderBy('invoice_date', 'desc')->get();

        $returnsQuery = DB::table('sales_invoice_returns')
            ->where('created_by', $companyId)
            ->where('customer_id', $customerId)
            ->whereIn('status', ['approved', 'completed'])
            ->select('return_number', 'return_date as date', 'subtotal', 'tax_amount', 'total_amount', 'status');

        if ($startDate) $returnsQuery->where('return_date', '>=', $startDate);
        if ($endDate) $returnsQuery->where('return_date', '<=', $endDate);
        $returns = $returnsQuery->orderBy('return_date', 'desc')->get();

        $creditNotesQuery = DB::table('credit_notes')
            ->where('created_by', $companyId)
            ->where('customer_id', $customerId)
            ->whereIn('status', ['approved', 'partial', 'applied'])
            ->select('credit_note_number', 'credit_note_date as date', 'total_amount', 'applied_amount', 'balance_amount', 'status');

        if ($startDate) $creditNotesQuery->where('credit_note_date', '>=', $startDate);
        if ($endDate) $creditNotesQuery->where('credit_note_date', '<=', $endDate);
        $creditNotes = $creditNotesQuery->orderBy('credit_note_date', 'desc')->get();

        $paymentsQuery = DB::table('customer_payments')
            ->leftJoin('bank_accounts', 'customer_payments.bank_account_id', '=', 'bank_accounts.id')
            ->where('customer_payments.created_by', $companyId)
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
        $companyId = $this->companyId();

        $vendor = DB::table('users')
            ->leftJoin('vendors', function ($join) use ($companyId) {
                $join->on('vendors.user_id', '=', 'users.id')
                    ->where('vendors.created_by', $companyId);
            })
            ->where('users.id', $vendorId)
            ->where('users.type', 'vendor')
            ->where('users.created_by', $companyId)
            ->select('users.id', 'users.name', 'users.email', 'vendors.company_name', 'vendors.tax_number')
            ->first();

        if (!$vendor) {
            return null;
        }

        $invoicesQuery = DB::table('purchase_invoices')
            ->where('created_by', $companyId)
            ->where('vendor_id', $vendorId)
            ->whereIn('status', ['posted', 'partial', 'paid'])
            ->select('invoice_number', 'invoice_date as date', 'due_date', 'subtotal', 'tax_amount', 'total_amount', 'balance_amount', 'status');

        if ($startDate) $invoicesQuery->where('invoice_date', '>=', $startDate);
        if ($endDate) $invoicesQuery->where('invoice_date', '<=', $endDate);
        $invoices = $invoicesQuery->orderBy('invoice_date', 'desc')->get();

        $returnsQuery = DB::table('purchase_returns')
            ->where('created_by', $companyId)
            ->where('vendor_id', $vendorId)
            ->whereIn('status', ['approved', 'completed'])
            ->select('return_number', 'return_date as date', 'subtotal', 'tax_amount', 'total_amount', 'status');

        if ($startDate) $returnsQuery->where('return_date', '>=', $startDate);
        if ($endDate) $returnsQuery->where('return_date', '<=', $endDate);
        $returns = $returnsQuery->orderBy('return_date', 'desc')->get();

        $debitNotesQuery = DB::table('debit_notes')
            ->where('created_by', $companyId)
            ->where('vendor_id', $vendorId)
            ->whereIn('status', ['approved', 'partial', 'applied'])
            ->select('debit_note_number', 'debit_note_date as date', 'total_amount', 'applied_amount', 'balance_amount', 'status');

        if ($startDate) $debitNotesQuery->where('debit_note_date', '>=', $startDate);
        if ($endDate) $debitNotesQuery->where('debit_note_date', '<=', $endDate);
        $debitNotes = $debitNotesQuery->orderBy('debit_note_date', 'desc')->get();

        $paymentsQuery = DB::table('vendor_payments')
            ->leftJoin('bank_accounts', 'vendor_payments.bank_account_id', '=', 'bank_accounts.id')
            ->where('vendor_payments.created_by', $companyId)
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

    public function getCashClosings(): array
    {
        $cashAccounts = [];
        if (
            Schema::hasTable('bank_accounts')
            && Schema::hasColumn('bank_accounts', 'created_by')
            && Schema::hasColumn('bank_accounts', 'current_balance')
            && Schema::hasColumn('bank_accounts', 'account_type')
        ) {
            $query = DB::table('bank_accounts')
                ->where('created_by', creatorId());

            if (Schema::hasColumn('bank_accounts', 'is_active')) {
                $query->where('is_active', true);
            }

            $rows = $query
                ->orderBy('account_name')
                ->get([
                    'id',
                    'account_number',
                    'account_name',
                    'bank_name',
                    'account_type',
                    'current_balance',
                ]);

            foreach ($rows as $row) {
                if (!$this->isCashAccountType((string) ($row->account_type ?? ''))) {
                    continue;
                }

                $cashAccounts[] = [
                    'bank_account_id' => (int) ($row->id ?? 0),
                    'account_number' => (string) ($row->account_number ?? ''),
                    'account_name' => (string) ($row->account_name ?? ''),
                    'bank_name' => (string) ($row->bank_name ?? ''),
                    'account_type' => strtolower(trim((string) ($row->account_type ?? ''))),
                    'current_balance_mzn' => round((float) ($row->current_balance ?? 0), 2),
                ];
            }
        }

        if (!Schema::hasTable('mz_cash_closings')) {
            return [
                'latest_closed_until' => null,
                'cash_accounts' => array_values($cashAccounts),
                'closings' => [],
            ];
        }

        $rows = MozCashClosing::query()
            ->where('created_by', creatorId())
            ->with(['cashAccount:id,account_name,account_number,bank_name,account_type', 'closedBy:id,name', 'reopenedBy:id,name'])
            ->orderByDesc('closing_date')
            ->orderByDesc('id')
            ->limit(60)
            ->get();

        $latestClosedUntil = $rows
            ->where('status', 'closed')
            ->max(fn ($item) => optional($item->closing_date)->toDateString());

        return [
            'latest_closed_until' => $latestClosedUntil ?: null,
            'cash_accounts' => array_values($cashAccounts),
            'closings' => $rows->map(function (MozCashClosing $closing): array {
                return [
                    'id' => $closing->id,
                    'bank_account_id' => $closing->bank_account_id,
                    'cash_account_name' => $closing->cashAccount?->account_name,
                    'cash_account_number' => $closing->cashAccount?->account_number,
                    'cash_account_type' => $closing->cashAccount?->account_type,
                    'closing_date' => optional($closing->closing_date)->toDateString(),
                    'status' => $closing->status,
                    'opening_balance_mzn' => (float) $closing->opening_balance_mzn,
                    'cash_in_mzn' => (float) $closing->cash_in_mzn,
                    'cash_out_mzn' => (float) $closing->cash_out_mzn,
                    'expected_balance_mzn' => (float) $closing->expected_balance_mzn,
                    'counted_balance_mzn' => (float) $closing->counted_balance_mzn,
                    'variance_mzn' => (float) $closing->variance_mzn,
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

    public function buildCashClosingSnapshot(int $bankAccountId, string $closingDate, float $countedBalanceMzn): array
    {
        $companyId = creatorId();
        $closingDate = Carbon::parse($closingDate)->toDateString();

        $bankAccount = BankAccount::query()
            ->where('id', $bankAccountId)
            ->where('created_by', $companyId)
            ->first();

        if (!$bankAccount || !$this->isCashAccountType((string) $bankAccount->account_type)) {
            throw new \InvalidArgumentException(__('Selected account is not configured as a cashbox or petty cash account.'));
        }

        $baseQuery = BankTransaction::query()
            ->where('created_by', $companyId)
            ->where('bank_account_id', $bankAccountId)
            ->where('transaction_status', '!=', 'cancelled');

        $previousTransaction = (clone $baseQuery)
            ->whereDate('transaction_date', '<', $closingDate)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->first();

        $dayTransactions = (clone $baseQuery)
            ->whereDate('transaction_date', $closingDate)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $latestTransaction = (clone $baseQuery)
            ->whereDate('transaction_date', '<=', $closingDate)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->first();

        $openingBalance = (float) ($previousTransaction?->running_balance ?? $bankAccount->opening_balance ?? 0);
        $cashIn = round((float) $dayTransactions->where('transaction_type', 'credit')->sum('amount'), 2);
        $cashOut = round((float) $dayTransactions->where('transaction_type', 'debit')->sum('amount'), 2);
        $expectedClosing = round($openingBalance + $cashIn - $cashOut, 2);
        $ledgerBalance = (float) ($latestTransaction?->running_balance ?? $expectedClosing);

        return [
            'cash_account' => [
                'bank_account_id' => $bankAccount->id,
                'account_number' => (string) $bankAccount->account_number,
                'account_name' => (string) $bankAccount->account_name,
                'bank_name' => (string) $bankAccount->bank_name,
                'account_type' => (string) $bankAccount->account_type,
            ],
            'closing_date' => $closingDate,
            'opening_balance_mzn' => round($openingBalance, 2),
            'cash_in_mzn' => $cashIn,
            'cash_out_mzn' => $cashOut,
            'expected_balance_mzn' => $expectedClosing,
            'ledger_balance_mzn' => round($ledgerBalance, 2),
            'counted_balance_mzn' => round($countedBalanceMzn, 2),
            'variance_mzn' => round($countedBalanceMzn - $expectedClosing, 2),
            'movement_count' => $dayTransactions->count(),
            'transactions' => $dayTransactions->take(100)->map(static function (BankTransaction $transaction): array {
                return [
                    'id' => $transaction->id,
                    'transaction_date' => optional($transaction->transaction_date)->toDateString(),
                    'transaction_type' => $transaction->transaction_type,
                    'reference_number' => $transaction->reference_number,
                    'description' => $transaction->description,
                    'amount' => (float) $transaction->amount,
                    'running_balance' => (float) $transaction->running_balance,
                    'transaction_status' => $transaction->transaction_status,
                ];
            })->values()->all(),
            'generated_at' => now()->toDateTimeString(),
            'generated_by' => auth()->id(),
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

    /**
     * @param \Illuminate\Support\Collection<int, object> $dossierByOperation
     * @return array<string, mixed>
     */
    private function attachExchangeDossierMetadata(array $operation, $dossierByOperation): array
    {
        $paymentId = (int) ($operation['payment_id'] ?? 0);
        $paymentType = (string) ($operation['payment_type'] ?? '');
        $direction = (string) ($operation['direction'] ?? '');

        $dossierRequired = (bool) ($operation['is_international'] ?? false)
            || (bool) ($operation['is_export_receipt'] ?? false);

        if (!$dossierRequired || $paymentId <= 0 || $paymentType === '' || $direction === '') {
            $operation['dossier_required'] = $dossierRequired;
            $operation['dossier_complete'] = !$dossierRequired;
            $operation['dossier_missing_documents'] = [];
            $operation['dossier_documents'] = [];
            $operation['missing_fx_documentation'] = (bool) ($operation['missing_fx_documentation'] ?? false);

            return $operation;
        }

        $operationKey = $this->buildExchangeDossierOperationKey($direction, $paymentType, $paymentId);
        $dossierRow = $dossierByOperation->get($operationKey);

        $requiredDocuments = $this->resolveRequiredExchangeDocuments($operation);
        $documents = [];
        $missingDocuments = $requiredDocuments;
        $dossierComplete = false;

        if ($dossierRow) {
            $documents = json_decode((string) ($dossierRow->documents ?? '[]'), true);
            if (!is_array($documents)) {
                $documents = [];
            }

            $configuredMissing = json_decode((string) ($dossierRow->missing_documents ?? '[]'), true);
            if (is_array($configuredMissing) && !empty($configuredMissing)) {
                $missingDocuments = array_values(array_filter(array_map('strval', $configuredMissing)));
            } else {
                $missingDocuments = array_values(array_filter($requiredDocuments, function (string $field) use ($documents): bool {
                    return trim((string) ($documents[$field] ?? '')) === '';
                }));
            }

            $dossierComplete = (bool) ($dossierRow->is_complete ?? false) && count($missingDocuments) === 0;
        }

        $operation['dossier_required'] = true;
        $operation['dossier_complete'] = $dossierComplete;
        $operation['dossier_missing_documents'] = $missingDocuments;
        $operation['dossier_documents'] = $documents;
        $operation['missing_fx_documentation'] = (bool) ($operation['missing_fx_documentation'] ?? false)
            || !$dossierComplete;

        return $operation;
    }

    /**
     * @return array<int, string>
     */
    private function resolveRequiredExchangeDocuments(array $operation): array
    {
        $required = [
            'contract_reference',
            'invoice_reference',
            'bank_settlement_reference',
        ];

        $direction = strtolower((string) ($operation['direction'] ?? ''));
        if ($direction === 'inbound' && (bool) ($operation['is_export_receipt'] ?? false)) {
            $required[] = 'transport_document_reference';
            $required[] = 'customs_declaration_reference';
        }

        $withholdingTreatment = strtolower((string) ($operation['withholding_tax_treatment'] ?? ''));
        if ($direction === 'outbound' && in_array($withholdingTreatment, ['withheld', 'adt_reduced'], true)) {
            $required[] = 'withholding_receipt_reference';
            $required[] = 'fx_authorization_reference';
        }

        return array_values(array_unique($required));
    }

    private function buildExchangeDossierOperationKey(string $direction, string $paymentType, int $paymentId): string
    {
        return sprintf('%s|%s|%d', strtolower(trim($direction)), strtolower(trim($paymentType)), $paymentId);
    }

    private function resolveCompanyTaxLabel(): string
    {
        $settings = $this->companySettings();
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

    private function companyId(): int
    {
        if ($this->cachedCompanyId === null) {
            $this->cachedCompanyId = (int) creatorId();
        }

        return $this->cachedCompanyId;
    }

    private function companySettings(): array
    {
        if ($this->cachedCompanySettings === null) {
            $this->cachedCompanySettings = getCompanyAllSetting($this->companyId());
        }

        return $this->cachedCompanySettings;
    }

    private function companySetting(string $key, $default = null)
    {
        if (!array_key_exists($key, $this->cachedCompanySettingValues)) {
            $this->cachedCompanySettingValues[$key] = company_setting($key, $this->companyId());
        }

        $value = $this->cachedCompanySettingValues[$key];

        return $value ?? $default;
    }
}
