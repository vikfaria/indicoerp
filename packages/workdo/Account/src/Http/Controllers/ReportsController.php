<?php

namespace Workdo\Account\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FiscalExportHistory;
use App\Services\SaftExportService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\ExchangeControlDossierService;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\MozFiscalClosing;
use Workdo\Account\Models\MozCashClosing;
use Workdo\Account\Models\MozPilotCompany;
use Workdo\Account\Models\MozPilotValidationCase;
use Workdo\Account\Models\CustomerPayment;
use Workdo\Account\Models\ExchangeControlDossier;
use Workdo\Account\Models\VendorPayment;
use Workdo\Account\Services\ReportService;
use Workdo\Account\Services\AccountCacheService;

class ReportsController extends Controller
{
    protected $reportService;
    private SaftExportService $saftExportService;
    private ExchangeControlDossierService $exchangeControlDossierService;

    public function __construct(
        ReportService $reportService,
        SaftExportService $saftExportService,
        ExchangeControlDossierService $exchangeControlDossierService
    )
    {
        $this->reportService = $reportService;
        $this->saftExportService = $saftExportService;
        $this->exchangeControlDossierService = $exchangeControlDossierService;
    }

    public function index()
    {
        if ($this->canAccessReportsIndex()) {
            $currentYear = date('Y');
            $financialYear = [
                'year_start_date' => "$currentYear-01-01",
                'year_end_date' => "$currentYear-12-31",
            ];

            return Inertia::render('Account/Reports/Index', [
                'financialYear' => $financialYear,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function invoiceAging(Request $request)
    {
        if (!$this->canAccessReport(['view-invoice-aging'])) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $filters = [
            'as_of_date' => $request->as_of_date ?: date('Y-m-d'),
        ];

        $data = $this->rememberReportPayload(
            'invoice-aging',
            $filters,
            fn () => $this->reportService->getInvoiceAging($filters),
            $request
        );
        return response()->json($data);
    }

    public function billAging(Request $request)
    {
        if (!$this->canAccessReport(['view-bill-aging'])) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $filters = [
            'as_of_date' => $request->as_of_date ?: date('Y-m-d'),
        ];

        $data = $this->rememberReportPayload(
            'bill-aging',
            $filters,
            fn () => $this->reportService->getBillAging($filters),
            $request
        );
        return response()->json($data);
    }

    public function taxSummary(Request $request)
    {
        if (!$this->canAccessReport(['view-tax-summary'])) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $filters = $this->resolveDateFilters($request);
        $data = $this->rememberReportPayload(
            'tax-summary',
            $filters,
            fn () => $this->reportService->getTaxSummary($filters),
            $request
        );

        return response()->json($data);
    }

    public function mozambiqueFiscalMap(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $currentYear = date('Y');
        $filters = [
            'from_date' => $request->from_date ?: "$currentYear-01-01",
            'to_date' => $request->to_date ?: "$currentYear-12-31",
        ];

        $data = $this->rememberReportPayload(
            'mz-fiscal-map',
            $filters,
            fn () => $this->reportService->getMozambiqueFiscalMap($filters),
            $request
        );

        return response()
            ->json($data)
            ->header('X-SCE-Canonical-Route', route('sce.tax.vat-map'));
    }

    public function exportMozambiqueFiscalMap(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return back()->with('error', __('Permission denied'));
        }

        $currentYear = date('Y');
        $filters = [
            'from_date' => $request->from_date ?: "$currentYear-01-01",
            'to_date' => $request->to_date ?: "$currentYear-12-31",
        ];

        $data = $this->rememberReportPayload(
            'mz-fiscal-map',
            $filters,
            fn () => $this->reportService->getMozambiqueFiscalMap($filters),
            $request
        );

        $rows = [
            ['section', 'metric', 'value'],
            ['period', 'from_date', $data['from_date']],
            ['period', 'to_date', $data['to_date']],
            ['sales', 'documents', (string) $data['sales']['documents']],
            ['sales', 'taxable_base', number_format((float) $data['sales']['taxable_base'], 2, '.', '')],
            ['sales', 'tax_amount', number_format((float) $data['sales']['tax_amount'], 2, '.', '')],
            ['sales', 'total_amount', number_format((float) $data['sales']['total_amount'], 2, '.', '')],
            ['pos_sales', 'documents', (string) ($data['pos_sales']['documents'] ?? 0)],
            ['pos_sales', 'taxable_base', number_format((float) ($data['pos_sales']['taxable_base'] ?? 0), 2, '.', '')],
            ['pos_sales', 'tax_amount', number_format((float) ($data['pos_sales']['tax_amount'] ?? 0), 2, '.', '')],
            ['pos_sales', 'total_amount', number_format((float) ($data['pos_sales']['total_amount'] ?? 0), 2, '.', '')],
            ['purchases', 'documents', (string) $data['purchases']['documents']],
            ['purchases', 'taxable_base', number_format((float) $data['purchases']['taxable_base'], 2, '.', '')],
            ['purchases', 'tax_amount', number_format((float) $data['purchases']['tax_amount'], 2, '.', '')],
            ['purchases', 'total_amount', number_format((float) $data['purchases']['total_amount'], 2, '.', '')],
            ['credit_notes', 'tax_amount', number_format((float) $data['credit_notes']['tax_amount'], 2, '.', '')],
            ['debit_notes', 'tax_amount', number_format((float) $data['debit_notes']['tax_amount'], 2, '.', '')],
            ['vat', 'output_vat', number_format((float) $data['vat']['output_vat'], 2, '.', '')],
            ['vat', 'input_vat', number_format((float) $data['vat']['input_vat'], 2, '.', '')],
            ['vat', 'net_vat_payable', number_format((float) $data['vat']['net_vat_payable'], 2, '.', '')],
        ];

        foreach ($data['fiscal_status']['sales'] as $status => $total) {
            $rows[] = ['fiscal_status_sales', (string) $status, (string) $total];
        }

        foreach (($data['fiscal_status']['pos'] ?? []) as $status => $total) {
            $rows[] = ['fiscal_status_pos', (string) $status, (string) $total];
        }

        foreach ($data['fiscal_status']['purchases'] as $status => $total) {
            $rows[] = ['fiscal_status_purchases', (string) $status, (string) $total];
        }

        if (!empty($data['tax_account_mapping'])) {
            foreach ($data['tax_account_mapping'] as $key => $value) {
                $rows[] = ['tax_account_mapping', (string) $key, (string) ($value ?? '')];
            }
        }

        $csv = '';
        foreach ($rows as $row) {
            $csv .= '"' . str_replace('"', '""', $row[0]) . '","' .
                str_replace('"', '""', $row[1]) . '","' .
                str_replace('"', '""', $row[2]) . '"' . "\n";
        }

        $filename = sprintf(
            'mozambique-fiscal-map-%s-to-%s.csv',
            $filters['from_date'],
            $filters['to_date']
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'X-SCE-Canonical-Route' => route('sce.tax.vat-map'),
        ]);
    }

    public function mozambiqueVatDeclaration(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $filters = $this->resolveDateFilters($request);
        $data = $this->rememberReportPayload(
            'mz-vat-declaration',
            $filters,
            fn () => $this->reportService->getMozambiqueVatDeclaration($filters),
            $request
        );

        return response()
            ->json($data)
            ->header('X-SCE-Canonical-Route', route('sce.tax.vat-map'));
    }

    public function exportMozambiqueVatDeclaration(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return back()->with('error', __('Permission denied'));
        }

        $filters = $this->resolveDateFilters($request);
        $data = $this->rememberReportPayload(
            'mz-vat-declaration',
            $filters,
            fn () => $this->reportService->getMozambiqueVatDeclaration($filters),
            $request
        );

        $rows = [
            ['section', 'metric', 'value'],
            ['period', 'from_date', $data['from_date']],
            ['period', 'to_date', $data['to_date']],
            ['totals', 'sales_vat', number_format((float) $data['totals']['sales_vat'], 2, '.', '')],
            ['totals', 'pos_vat', number_format((float) ($data['totals']['pos_vat'] ?? 0), 2, '.', '')],
            ['totals', 'purchase_vat', number_format((float) $data['totals']['purchase_vat'], 2, '.', '')],
            ['totals', 'credit_notes_vat', number_format((float) $data['totals']['credit_notes_vat'], 2, '.', '')],
            ['totals', 'debit_notes_vat', number_format((float) $data['totals']['debit_notes_vat'], 2, '.', '')],
            ['totals', 'output_vat', number_format((float) $data['totals']['output_vat'], 2, '.', '')],
            ['totals', 'input_vat', number_format((float) $data['totals']['input_vat'], 2, '.', '')],
            ['totals', 'deductible_input_vat', number_format((float) ($data['totals']['deductible_input_vat'] ?? 0), 2, '.', '')],
            ['totals', 'non_deductible_input_vat', number_format((float) ($data['totals']['non_deductible_input_vat'] ?? 0), 2, '.', '')],
            ['totals', 'net_vat_payable', number_format((float) $data['totals']['net_vat_payable'], 2, '.', '')],
            ['', '', ''],
            ['monthly', 'period', 'sales_vat|pos_vat|purchase_vat|credit_notes_vat|debit_notes_vat|output_vat|input_vat|deductible_input_vat|non_deductible_input_vat|net_vat_payable'],
        ];

        foreach ($data['monthly'] as $month) {
            $rows[] = [
                'monthly',
                (string) $month['period'],
                implode('|', [
                    number_format((float) $month['sales_vat'], 2, '.', ''),
                    number_format((float) ($month['pos_vat'] ?? 0), 2, '.', ''),
                    number_format((float) $month['purchase_vat'], 2, '.', ''),
                    number_format((float) $month['credit_notes_vat'], 2, '.', ''),
                    number_format((float) $month['debit_notes_vat'], 2, '.', ''),
                    number_format((float) $month['output_vat'], 2, '.', ''),
                    number_format((float) $month['input_vat'], 2, '.', ''),
                    number_format((float) ($month['deductible_input_vat'] ?? 0), 2, '.', ''),
                    number_format((float) ($month['non_deductible_input_vat'] ?? 0), 2, '.', ''),
                    number_format((float) $month['net_vat_payable'], 2, '.', ''),
                ]),
            ];
        }

        $csv = '';
        foreach ($rows as $row) {
            $csv .= '"' . str_replace('"', '""', $row[0]) . '","' .
                str_replace('"', '""', $row[1]) . '","' .
                str_replace('"', '""', $row[2]) . '"' . "\n";
        }

        $filename = sprintf(
            'mozambique-vat-declaration-%s-to-%s.csv',
            $filters['from_date'],
            $filters['to_date']
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'X-SCE-Canonical-Route' => route('sce.tax.vat-map'),
        ]);
    }

    public function mozambiqueFiscalSubmissionRegister(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $currentYear = date('Y');
        $filters = [
            'from_date' => $request->from_date ?: "$currentYear-01-01",
            'to_date' => $request->to_date ?: "$currentYear-12-31",
        ];

        $data = $this->rememberReportPayload(
            'mz-fiscal-submission-register',
            $filters,
            fn () => $this->reportService->getMozambiqueFiscalSubmissionRegister($filters),
            $request
        );
        return response()->json($data);
    }

    public function exportMozambiqueFiscalSubmissionRegister(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return back()->with('error', __('Permission denied'));
        }

        $currentYear = date('Y');
        $filters = [
            'from_date' => $request->from_date ?: "$currentYear-01-01",
            'to_date' => $request->to_date ?: "$currentYear-12-31",
        ];

        $data = $this->rememberReportPayload(
            'mz-fiscal-submission-register',
            $filters,
            fn () => $this->reportService->getMozambiqueFiscalSubmissionRegister($filters),
            $request
        );

        $rows = [
            ['from_date', $data['from_date']],
            ['to_date', $data['to_date']],
            ['', ''],
            ['period', 'document_group', 'fiscal_status', 'total'],
        ];

        foreach ($data['rows'] as $row) {
            $rows[] = [
                (string) $row['period'],
                (string) $row['document_group'],
                (string) $row['fiscal_status'],
                (string) $row['total'],
            ];
        }

        $csv = '';
        foreach ($rows as $row) {
            $csv .= '"' . str_replace('"', '""', $row[0] ?? '') . '","' .
                str_replace('"', '""', $row[1] ?? '') . '","' .
                str_replace('"', '""', $row[2] ?? '') . '","' .
                str_replace('"', '""', $row[3] ?? '') . '"' . "\n";
        }

        $filename = sprintf(
            'mozambique-fiscal-submission-register-%s-to-%s.csv',
            $filters['from_date'],
            $filters['to_date']
        );

        $this->logFiscalExport(
            'fiscal_submission_register_csv',
            $filters['from_date'],
            $filters['to_date'],
            $filename,
            $csv,
            ['rows' => count($data['rows'] ?? [])]
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function mozambiqueFiscalComplianceAlerts(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $filters = $this->resolveDateFilters($request);
        $filters['due_soon_days'] = max(1, min(30, (int) $request->integer('due_soon_days', 7)));

        $data = $this->rememberReportPayload(
            'mz-fiscal-compliance-alerts',
            $filters,
            fn () => $this->reportService->getMozambiqueFiscalComplianceAlerts($filters),
            $request
        );

        return response()->json($data);
    }

    public function mozambiqueExchangeControlReport(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $filters = $this->resolveDateFilters($request);

        $data = $this->rememberReportPayload(
            'mz-exchange-control-report',
            $filters,
            fn () => $this->reportService->getMozambiqueExchangeControlReport($filters),
            $request
        );

        return response()->json($data);
    }

    public function exportMozambiqueExchangeControlReport(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return back()->with('error', __('Permission denied'));
        }

        $filters = $this->resolveDateFilters($request);

        $data = $this->rememberReportPayload(
            'mz-exchange-control-report',
            $filters,
            fn () => $this->reportService->getMozambiqueExchangeControlReport($filters),
            $request
        );

        $rows = [
            ['section', 'metric', 'value'],
            ['period', 'from_date', (string) ($data['from_date'] ?? '')],
            ['period', 'to_date', (string) ($data['to_date'] ?? '')],
            ['summary', 'total_operations', (string) data_get($data, 'summary.total_operations', 0)],
            ['summary', 'outbound_count', (string) data_get($data, 'summary.outbound_count', 0)],
            ['summary', 'inbound_count', (string) data_get($data, 'summary.inbound_count', 0)],
            ['summary', 'outbound_amount_mzn', number_format((float) data_get($data, 'summary.outbound_amount_mzn', 0), 2, '.', '')],
            ['summary', 'inbound_amount_mzn', number_format((float) data_get($data, 'summary.inbound_amount_mzn', 0), 2, '.', '')],
            ['summary', 'domestic_fx_violations', (string) data_get($data, 'summary.domestic_fx_violations', 0)],
            ['summary', 'missing_fx_documentation', (string) data_get($data, 'summary.missing_fx_documentation', 0)],
            ['summary', 'pending_repatriation_count', (string) data_get($data, 'summary.pending_repatriation_count', 0)],
            ['summary', 'completed_repatriation_count', (string) data_get($data, 'summary.completed_repatriation_count', 0)],
            ['', '', ''],
            ['operations', 'reference', 'direction|operation_type|date|counterparty|country|residency|currency|amount_mzn|status|domestic_fx_violation|missing_fx_documentation|repatriation_status'],
        ];

        foreach ((array) data_get($data, 'outbound_payments', []) as $operation) {
            $rows[] = [
                'operations',
                (string) data_get($operation, 'reference', ''),
                implode('|', [
                    (string) data_get($operation, 'direction', ''),
                    (string) data_get($operation, 'operation_type', ''),
                    (string) data_get($operation, 'date', ''),
                    (string) data_get($operation, 'counterparty', ''),
                    (string) data_get($operation, 'counterparty_country', ''),
                    (string) data_get($operation, 'counterparty_residency_status', ''),
                    (string) data_get($operation, 'currency_code', ''),
                    number_format((float) data_get($operation, 'amount_mzn', 0), 2, '.', ''),
                    (string) data_get($operation, 'status', ''),
                    data_get($operation, 'domestic_fx_violation', false) ? 'yes' : 'no',
                    data_get($operation, 'missing_fx_documentation', false) ? 'yes' : 'no',
                    (string) data_get($operation, 'repatriation_status', ''),
                ]),
            ];
        }

        foreach ((array) data_get($data, 'inbound_receipts', []) as $operation) {
            $rows[] = [
                'operations',
                (string) data_get($operation, 'reference', ''),
                implode('|', [
                    (string) data_get($operation, 'direction', ''),
                    (string) data_get($operation, 'operation_type', ''),
                    (string) data_get($operation, 'date', ''),
                    (string) data_get($operation, 'counterparty', ''),
                    (string) data_get($operation, 'counterparty_country', ''),
                    (string) data_get($operation, 'counterparty_residency_status', ''),
                    (string) data_get($operation, 'currency_code', ''),
                    number_format((float) data_get($operation, 'amount_mzn', 0), 2, '.', ''),
                    (string) data_get($operation, 'status', ''),
                    data_get($operation, 'domestic_fx_violation', false) ? 'yes' : 'no',
                    data_get($operation, 'missing_fx_documentation', false) ? 'yes' : 'no',
                    (string) data_get($operation, 'repatriation_status', ''),
                ]),
            ];
        }

        $csv = '';
        foreach ($rows as $row) {
            $csv .= '"' . str_replace('"', '""', $row[0] ?? '') . '","' .
                str_replace('"', '""', $row[1] ?? '') . '","' .
                str_replace('"', '""', $row[2] ?? '') . '"' . "\n";
        }

        $filename = sprintf(
            'mozambique-exchange-control-report-%s-to-%s.csv',
            $filters['from_date'],
            $filters['to_date']
        );

        $this->logFiscalExport(
            'fx_compliance_report_csv',
            $filters['from_date'],
            $filters['to_date'],
            $filename,
            $csv,
            ['rows' => count($rows)]
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function mozambiqueReverseChargeReport(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $filters = $this->resolveDateFilters($request);
        $data = $this->rememberReportPayload(
            'mz-reverse-charge-report',
            $filters,
            fn () => $this->reportService->getMozambiqueReverseChargeReport($filters),
            $request
        );

        return response()->json($data);
    }

    public function exportMozambiqueReverseChargeReport(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return back()->with('error', __('Permission denied'));
        }

        $filters = $this->resolveDateFilters($request);
        $data = $this->rememberReportPayload(
            'mz-reverse-charge-report',
            $filters,
            fn () => $this->reportService->getMozambiqueReverseChargeReport($filters),
            $request
        );

        $rows = [
            ['section', 'metric', 'value'],
            ['period', 'from_date', (string) ($data['from_date'] ?? '')],
            ['period', 'to_date', (string) ($data['to_date'] ?? '')],
            ['summary', 'vat_rate', number_format((float) data_get($data, 'vat_rate', 0), 2, '.', '')],
            ['summary', 'total_operations', (string) data_get($data, 'summary.total_operations', 0)],
            ['summary', 'total_base_amount_mzn', number_format((float) data_get($data, 'summary.total_base_amount_mzn', 0), 2, '.', '')],
            ['summary', 'total_vat_liquidated_mzn', number_format((float) data_get($data, 'summary.total_vat_liquidated_mzn', 0), 2, '.', '')],
            ['summary', 'total_vat_supported_mzn', number_format((float) data_get($data, 'summary.total_vat_supported_mzn', 0), 2, '.', '')],
            ['summary', 'missing_supplier_tax_identifier_count', (string) data_get($data, 'summary.missing_supplier_tax_identifier_count', 0)],
            ['summary', 'missing_service_type_count', (string) data_get($data, 'summary.missing_service_type_count', 0)],
            ['summary', 'missing_supplier_country_count', (string) data_get($data, 'summary.missing_supplier_country_count', 0)],
            ['', '', ''],
            ['operations', 'payment_reference', 'payment_date|supplier|supplier_country|supplier_residency|supplier_tax_identifier|service_type|currency|base_amount_mzn|vat_rate|vat_liquidated_mzn|vat_supported_mzn'],
        ];

        foreach ((array) data_get($data, 'operations', []) as $operation) {
            $rows[] = [
                'operations',
                (string) data_get($operation, 'payment_reference', ''),
                implode('|', [
                    (string) data_get($operation, 'payment_date', ''),
                    (string) data_get($operation, 'supplier', ''),
                    (string) data_get($operation, 'supplier_country', ''),
                    (string) data_get($operation, 'supplier_residency_status', ''),
                    (string) data_get($operation, 'supplier_tax_identifier', ''),
                    (string) data_get($operation, 'service_type', ''),
                    (string) data_get($operation, 'currency_code', ''),
                    number_format((float) data_get($operation, 'base_amount_mzn', 0), 2, '.', ''),
                    number_format((float) data_get($operation, 'vat_rate', 0), 2, '.', ''),
                    number_format((float) data_get($operation, 'vat_liquidated_mzn', 0), 2, '.', ''),
                    number_format((float) data_get($operation, 'vat_supported_mzn', 0), 2, '.', ''),
                ]),
            ];
        }

        $csv = '';
        foreach ($rows as $row) {
            $csv .= '"' . str_replace('"', '""', $row[0] ?? '') . '","' .
                str_replace('"', '""', $row[1] ?? '') . '","' .
                str_replace('"', '""', $row[2] ?? '') . '"' . "\n";
        }

        $filename = sprintf(
            'mozambique-reverse-charge-report-%s-to-%s.csv',
            $filters['from_date'],
            $filters['to_date']
        );

        $this->logFiscalExport(
            'reverse_charge_report_csv',
            $filters['from_date'],
            $filters['to_date'],
            $filename,
            $csv,
            ['rows' => count($rows)]
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function mozambiqueInternationalWithholdingReport(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $filters = $this->resolveDateFilters($request);
        $data = $this->rememberReportPayload(
            'mz-international-withholding-report',
            $filters,
            fn () => $this->reportService->getMozambiqueInternationalWithholdingReport($filters),
            $request
        );

        return response()->json($data);
    }

    public function exportMozambiqueInternationalWithholdingReport(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return back()->with('error', __('Permission denied'));
        }

        $filters = $this->resolveDateFilters($request);
        $data = $this->rememberReportPayload(
            'mz-international-withholding-report',
            $filters,
            fn () => $this->reportService->getMozambiqueInternationalWithholdingReport($filters),
            $request
        );

        $rows = [
            ['section', 'metric', 'value'],
            ['period', 'from_date', (string) ($data['from_date'] ?? '')],
            ['period', 'to_date', (string) ($data['to_date'] ?? '')],
            ['summary', 'total_operations', (string) data_get($data, 'summary.total_operations', 0)],
            ['summary', 'total_gross_amount', number_format((float) data_get($data, 'summary.total_gross_amount', 0), 2, '.', '')],
            ['summary', 'total_withholding_amount', number_format((float) data_get($data, 'summary.total_withholding_amount', 0), 2, '.', '')],
            ['summary', 'total_net_amount', number_format((float) data_get($data, 'summary.total_net_amount', 0), 2, '.', '')],
            ['summary', 'adt_applied_count', (string) data_get($data, 'summary.adt_applied_count', 0)],
            ['summary', 'pending_state_payment_count', (string) data_get($data, 'summary.pending_state_payment_count', 0)],
            ['summary', 'missing_supporting_documents_count', (string) data_get($data, 'summary.missing_supporting_documents_count', 0)],
            ['', '', ''],
            ['operations', 'document_reference', 'transaction_date|beneficiary|country|residency|beneficiary_tax_identifier|income_type|withholding_treatment|gross_amount|withholding_rate|withholding_amount|net_amount|status|adt_applied|adt_certificate|fiscal_compliance_reference|financial_approval_reference|fx_authorization_reference'],
        ];

        foreach ((array) data_get($data, 'operations', []) as $operation) {
            $rows[] = [
                'operations',
                (string) data_get($operation, 'document_reference', ''),
                implode('|', [
                    (string) data_get($operation, 'transaction_date', ''),
                    (string) data_get($operation, 'beneficiary', ''),
                    (string) data_get($operation, 'beneficiary_country', ''),
                    (string) data_get($operation, 'beneficiary_residency_status', ''),
                    (string) data_get($operation, 'beneficiary_tax_identifier', ''),
                    (string) data_get($operation, 'income_type', ''),
                    (string) data_get($operation, 'withholding_treatment', ''),
                    number_format((float) data_get($operation, 'gross_amount', 0), 2, '.', ''),
                    number_format((float) data_get($operation, 'withholding_rate', 0), 2, '.', ''),
                    number_format((float) data_get($operation, 'withholding_amount', 0), 2, '.', ''),
                    number_format((float) data_get($operation, 'net_amount', 0), 2, '.', ''),
                    (string) data_get($operation, 'status', ''),
                    data_get($operation, 'adt_applied', false) ? 'yes' : 'no',
                    (string) data_get($operation, 'adt_certificate_reference', ''),
                    (string) data_get($operation, 'fiscal_compliance_reference', ''),
                    (string) data_get($operation, 'financial_approval_reference', ''),
                    (string) data_get($operation, 'fx_authorization_reference', ''),
                ]),
            ];
        }

        $csv = '';
        foreach ($rows as $row) {
            $csv .= '"' . str_replace('"', '""', $row[0] ?? '') . '","' .
                str_replace('"', '""', $row[1] ?? '') . '","' .
                str_replace('"', '""', $row[2] ?? '') . '"' . "\n";
        }

        $filename = sprintf(
            'mozambique-international-withholding-report-%s-to-%s.csv',
            $filters['from_date'],
            $filters['to_date']
        );

        $this->logFiscalExport(
            'international_withholding_report_csv',
            $filters['from_date'],
            $filters['to_date'],
            $filename,
            $csv,
            ['rows' => count($rows)]
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function mozambiqueInvoicingReport(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $filters = $this->resolveDateFilters($request);
        $data = $this->rememberReportPayload(
            'mz-invoicing-report',
            $filters,
            fn () => $this->reportService->getMozambiqueInvoicingReport($filters),
            $request
        );

        return response()->json($data);
    }

    public function exportMozambiqueInvoicingReport(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return back()->with('error', __('Permission denied'));
        }

        $filters = $this->resolveDateFilters($request);
        $data = $this->rememberReportPayload(
            'mz-invoicing-report',
            $filters,
            fn () => $this->reportService->getMozambiqueInvoicingReport($filters),
            $request
        );

        $rows = [
            ['section', 'metric', 'value'],
            ['period', 'from_date', (string) ($data['from_date'] ?? '')],
            ['period', 'to_date', (string) ($data['to_date'] ?? '')],
            ['summary', 'total_documents', (string) data_get($data, 'summary.total_documents', 0)],
            ['summary', 'total_amount', number_format((float) data_get($data, 'summary.total_amount', 0), 2, '.', '')],
            ['summary', 'total_tax_amount', number_format((float) data_get($data, 'summary.total_tax_amount', 0), 2, '.', '')],
            ['summary', 'total_exempt_amount', number_format((float) data_get($data, 'summary.total_exempt_amount', 0), 2, '.', '')],
            ['summary', 'digital_operations_count', (string) data_get($data, 'summary.digital_operations_count', 0)],
            ['summary', 'cancelled_documents_count', (string) data_get($data, 'summary.cancelled_documents_count', 0)],
            ['', '', ''],
            ['by_status', 'status', 'count'],
        ];

        foreach ((array) data_get($data, 'by_status', []) as $status => $count) {
            $rows[] = ['by_status', (string) $status, (string) $count];
        }

        $rows[] = ['', '', ''];
        $rows[] = ['by_document_type', 'document_type', 'count'];
        foreach ((array) data_get($data, 'by_document_type', []) as $type => $count) {
            $rows[] = ['by_document_type', (string) $type, (string) $count];
        }

        $rows[] = ['', '', ''];
        $rows[] = ['by_currency', 'currency', 'documents|total_amount'];
        foreach ((array) data_get($data, 'by_currency', []) as $entry) {
            $rows[] = [
                'by_currency',
                (string) data_get($entry, 'currency_code', ''),
                implode('|', [
                    (string) data_get($entry, 'documents', 0),
                    number_format((float) data_get($entry, 'total_amount', 0), 2, '.', ''),
                ]),
            ];
        }

        $rows[] = ['', '', ''];
        $rows[] = ['operations', 'invoice_number', 'invoice_date|document_type|series|status|fiscal_submission_status|customer|currency_code|total_amount|tax_amount|exempt_amount|is_digital_operation'];
        foreach ((array) data_get($data, 'operations', []) as $entry) {
            $rows[] = [
                'operations',
                (string) data_get($entry, 'invoice_number', ''),
                implode('|', [
                    (string) data_get($entry, 'invoice_date', ''),
                    (string) data_get($entry, 'document_type', ''),
                    (string) data_get($entry, 'series', ''),
                    (string) data_get($entry, 'status', ''),
                    (string) data_get($entry, 'fiscal_submission_status', ''),
                    (string) data_get($entry, 'customer', ''),
                    (string) data_get($entry, 'currency_code', ''),
                    number_format((float) data_get($entry, 'total_amount', 0), 2, '.', ''),
                    number_format((float) data_get($entry, 'tax_amount', 0), 2, '.', ''),
                    number_format((float) data_get($entry, 'exempt_amount', 0), 2, '.', ''),
                    data_get($entry, 'is_digital_operation', false) ? 'yes' : 'no',
                ]),
            ];
        }

        $csv = '';
        foreach ($rows as $row) {
            $csv .= '"' . str_replace('"', '""', $row[0] ?? '') . '","' .
                str_replace('"', '""', $row[1] ?? '') . '","' .
                str_replace('"', '""', $row[2] ?? '') . '"' . "\n";
        }

        $filename = sprintf(
            'mozambique-invoicing-report-%s-to-%s.csv',
            $filters['from_date'],
            $filters['to_date']
        );

        $this->logFiscalExport(
            'invoicing_report_csv',
            $filters['from_date'],
            $filters['to_date'],
            $filename,
            $csv,
            ['rows' => count($rows)]
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function mozambiqueCurrencyReport(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $filters = $this->resolveDateFilters($request);
        $data = $this->rememberReportPayload(
            'mz-currency-report',
            $filters,
            fn () => $this->reportService->getMozambiqueCurrencyReport($filters),
            $request
        );

        return response()->json($data);
    }

    public function exportMozambiqueCurrencyReport(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return back()->with('error', __('Permission denied'));
        }

        $filters = $this->resolveDateFilters($request);
        $data = $this->rememberReportPayload(
            'mz-currency-report',
            $filters,
            fn () => $this->reportService->getMozambiqueCurrencyReport($filters),
            $request
        );

        $rows = [
            ['section', 'metric', 'value'],
            ['period', 'from_date', (string) ($data['from_date'] ?? '')],
            ['period', 'to_date', (string) ($data['to_date'] ?? '')],
            ['summary', 'total_operations', (string) data_get($data, 'summary.total_operations', 0)],
            ['summary', 'foreign_currency_operations_count', (string) data_get($data, 'summary.foreign_currency_operations_count', 0)],
            ['summary', 'international_payments_count', (string) data_get($data, 'summary.international_payments_count', 0)],
            ['summary', 'international_receipts_count', (string) data_get($data, 'summary.international_receipts_count', 0)],
            ['summary', 'export_receipts_count', (string) data_get($data, 'summary.export_receipts_count', 0)],
            ['summary', 'export_receipts_amount_mzn', number_format((float) data_get($data, 'summary.export_receipts_amount_mzn', 0), 2, '.', '')],
            ['summary', 'repatriated_amount_mzn', number_format((float) data_get($data, 'summary.repatriated_amount_mzn', 0), 2, '.', '')],
            ['summary', 'pending_repatriation_amount_mzn', number_format((float) data_get($data, 'summary.pending_repatriation_amount_mzn', 0), 2, '.', '')],
            ['summary', 'pending_repatriation_count', (string) data_get($data, 'summary.pending_repatriation_count', 0)],
            ['summary', 'domestic_fx_violations', (string) data_get($data, 'summary.domestic_fx_violations', 0)],
            ['summary', 'missing_fx_documentation', (string) data_get($data, 'summary.missing_fx_documentation', 0)],
            ['summary', 'missing_dossier_count', (string) data_get($data, 'summary.missing_dossier_count', 0)],
            ['summary', 'total_fx_difference_amount_mzn', number_format((float) data_get($data, 'summary.total_fx_difference_amount_mzn', 0), 2, '.', '')],
            ['', '', ''],
            ['currency_breakdown', 'currency_code', 'operations|amount_mzn|foreign_amount'],
        ];

        foreach ((array) data_get($data, 'currency_breakdown', []) as $entry) {
            $rows[] = [
                'currency_breakdown',
                (string) data_get($entry, 'currency_code', ''),
                implode('|', [
                    (string) data_get($entry, 'operations', 0),
                    number_format((float) data_get($entry, 'amount_mzn', 0), 2, '.', ''),
                    number_format((float) data_get($entry, 'foreign_amount', 0), 2, '.', ''),
                ]),
            ];
        }

        $rows[] = ['', '', ''];
        $rows[] = ['pending_repatriation', 'reference', 'date|counterparty|currency_code|amount_mzn|repatriated_amount_mzn|pending_amount_mzn|repatriation_status'];
        foreach ((array) data_get($data, 'pending_repatriation', []) as $entry) {
            $rows[] = [
                'pending_repatriation',
                (string) data_get($entry, 'reference', ''),
                implode('|', [
                    (string) data_get($entry, 'date', ''),
                    (string) data_get($entry, 'counterparty', ''),
                    (string) data_get($entry, 'currency_code', ''),
                    number_format((float) data_get($entry, 'amount_mzn', 0), 2, '.', ''),
                    number_format((float) data_get($entry, 'repatriated_amount_mzn', 0), 2, '.', ''),
                    number_format((float) data_get($entry, 'pending_amount_mzn', 0), 2, '.', ''),
                    (string) data_get($entry, 'repatriation_status', ''),
                ]),
            ];
        }

        $csv = '';
        foreach ($rows as $row) {
            $csv .= '"' . str_replace('"', '""', $row[0] ?? '') . '","' .
                str_replace('"', '""', $row[1] ?? '') . '","' .
                str_replace('"', '""', $row[2] ?? '') . '"' . "\n";
        }

        $filename = sprintf(
            'mozambique-currency-report-%s-to-%s.csv',
            $filters['from_date'],
            $filters['to_date']
        );

        $this->logFiscalExport(
            'currency_report_csv',
            $filters['from_date'],
            $filters['to_date'],
            $filename,
            $csv,
            ['rows' => count($rows)]
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function mozambiqueTreasuryReport(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $filters = $this->resolveDateFilters($request);
        $filters['as_of_date'] = $request->as_of_date ?: $filters['to_date'];

        $data = $this->rememberReportPayload(
            'mz-treasury-report',
            $filters,
            fn () => $this->reportService->getMozambiqueTreasuryReport($filters),
            $request
        );

        return response()->json($data);
    }

    public function exportMozambiqueTreasuryReport(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return back()->with('error', __('Permission denied'));
        }

        $filters = $this->resolveDateFilters($request);
        $filters['as_of_date'] = $request->as_of_date ?: $filters['to_date'];

        $data = $this->rememberReportPayload(
            'mz-treasury-report',
            $filters,
            fn () => $this->reportService->getMozambiqueTreasuryReport($filters),
            $request
        );

        $rows = [
            ['section', 'metric', 'value'],
            ['period', 'from_date', (string) ($data['from_date'] ?? '')],
            ['period', 'to_date', (string) ($data['to_date'] ?? '')],
            ['period', 'as_of_date', (string) ($data['as_of_date'] ?? '')],
            ['summary', 'bank_balance_mzn', number_format((float) data_get($data, 'summary.bank_balance_mzn', 0), 2, '.', '')],
            ['summary', 'cash_balance_mzn', number_format((float) data_get($data, 'summary.cash_balance_mzn', 0), 2, '.', '')],
            ['summary', 'cashbox_account_count', (string) data_get($data, 'summary.cashbox_account_count', 0)],
            ['summary', 'cashbox_balance_mzn', number_format((float) data_get($data, 'summary.cashbox_balance_mzn', 0), 2, '.', '')],
            ['summary', 'petty_cash_account_count', (string) data_get($data, 'summary.petty_cash_account_count', 0)],
            ['summary', 'petty_cash_balance_mzn', number_format((float) data_get($data, 'summary.petty_cash_balance_mzn', 0), 2, '.', '')],
            ['summary', 'total_liquidity_mzn', number_format((float) data_get($data, 'summary.total_liquidity_mzn', 0), 2, '.', '')],
            ['summary', 'accounts_receivable_open_mzn', number_format((float) data_get($data, 'summary.accounts_receivable_open_mzn', 0), 2, '.', '')],
            ['summary', 'accounts_payable_open_mzn', number_format((float) data_get($data, 'summary.accounts_payable_open_mzn', 0), 2, '.', '')],
            ['summary', 'period_receipts_mzn', number_format((float) data_get($data, 'summary.period_receipts_mzn', 0), 2, '.', '')],
            ['summary', 'period_payments_mzn', number_format((float) data_get($data, 'summary.period_payments_mzn', 0), 2, '.', '')],
            ['summary', 'period_net_cash_flow_mzn', number_format((float) data_get($data, 'summary.period_net_cash_flow_mzn', 0), 2, '.', '')],
            ['summary', 'projected_inflows_mzn', number_format((float) data_get($data, 'summary.projected_inflows_mzn', 0), 2, '.', '')],
            ['summary', 'projected_outflows_mzn', number_format((float) data_get($data, 'summary.projected_outflows_mzn', 0), 2, '.', '')],
            ['summary', 'projected_net_cash_flow_mzn', number_format((float) data_get($data, 'summary.projected_net_cash_flow_mzn', 0), 2, '.', '')],
            ['', '', ''],
            ['monthly_realized_flow', 'period', 'receipts_mzn|payments_mzn|net_flow_mzn'],
        ];

        foreach ((array) data_get($data, 'monthly_realized_flow', []) as $entry) {
            $rows[] = [
                'monthly_realized_flow',
                (string) data_get($entry, 'period', ''),
                implode('|', [
                    number_format((float) data_get($entry, 'receipts_mzn', 0), 2, '.', ''),
                    number_format((float) data_get($entry, 'payments_mzn', 0), 2, '.', ''),
                    number_format((float) data_get($entry, 'net_flow_mzn', 0), 2, '.', ''),
                ]),
            ];
        }

        $rows[] = ['', '', ''];
        $rows[] = ['monthly_projected_flow', 'period', 'projected_inflows_mzn|projected_outflows_mzn|projected_net_mzn'];
        foreach ((array) data_get($data, 'monthly_projected_flow', []) as $entry) {
            $rows[] = [
                'monthly_projected_flow',
                (string) data_get($entry, 'period', ''),
                implode('|', [
                    number_format((float) data_get($entry, 'projected_inflows_mzn', 0), 2, '.', ''),
                    number_format((float) data_get($entry, 'projected_outflows_mzn', 0), 2, '.', ''),
                    number_format((float) data_get($entry, 'projected_net_mzn', 0), 2, '.', ''),
                ]),
            ];
        }

        $csv = '';
        foreach ($rows as $row) {
            $csv .= '"' . str_replace('"', '""', $row[0] ?? '') . '","' .
                str_replace('"', '""', $row[1] ?? '') . '","' .
                str_replace('"', '""', $row[2] ?? '') . '"' . "\n";
        }

        $filename = sprintf(
            'mozambique-treasury-report-%s-to-%s.csv',
            $filters['from_date'],
            $filters['to_date']
        );

        $this->logFiscalExport(
            'treasury_report_csv',
            $filters['from_date'],
            $filters['to_date'],
            $filename,
            $csv,
            ['rows' => count($rows)]
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function mozambiqueFinancialComplianceDashboard(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $filters = $this->resolveDateFilters($request);
        $filters['due_soon_days'] = max(1, min(30, (int) $request->integer('due_soon_days', 7)));

        $data = $this->rememberReportPayload(
            'mz-financial-compliance-dashboard',
            $filters,
            fn () => $this->reportService->getMozambiqueFinancialComplianceDashboard($filters),
            $request
        );

        return response()->json($data);
    }

    public function exportMozambiqueFinancialComplianceDashboard(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return back()->with('error', __('Permission denied'));
        }

        $filters = $this->resolveDateFilters($request);
        $filters['due_soon_days'] = max(1, min(30, (int) $request->integer('due_soon_days', 7)));

        $data = $this->rememberReportPayload(
            'mz-financial-compliance-dashboard',
            $filters,
            fn () => $this->reportService->getMozambiqueFinancialComplianceDashboard($filters),
            $request
        );

        $rows = [
            ['section', 'metric', 'value'],
            ['period', 'from_date', (string) ($data['from_date'] ?? '')],
            ['period', 'to_date', (string) ($data['to_date'] ?? '')],
            ['summary', 'risk_score', (string) data_get($data, 'summary.risk_score', 0)],
            ['summary', 'risk_level', (string) data_get($data, 'summary.risk_level', 'low')],
            ['summary', 'total_indicators', (string) data_get($data, 'summary.total_indicators', 0)],
            ['summary', 'active_indicators', (string) data_get($data, 'summary.active_indicators', 0)],
            ['summary', 'critical_indicators', (string) data_get($data, 'summary.critical_indicators', 0)],
            ['summary', 'high_indicators', (string) data_get($data, 'summary.high_indicators', 0)],
            ['summary', 'medium_indicators', (string) data_get($data, 'summary.medium_indicators', 0)],
            ['summary', 'low_indicators', (string) data_get($data, 'summary.low_indicators', 0)],
            ['', '', ''],
            ['active_indicators', 'code', 'label|severity|value|source'],
        ];

        foreach ((array) data_get($data, 'active_indicators', []) as $indicator) {
            $rows[] = [
                'active_indicators',
                (string) data_get($indicator, 'code', ''),
                implode('|', [
                    (string) data_get($indicator, 'label', ''),
                    (string) data_get($indicator, 'severity', ''),
                    (string) data_get($indicator, 'value', 0),
                    (string) data_get($indicator, 'source', ''),
                ]),
            ];
        }

        $csv = '';
        foreach ($rows as $row) {
            $csv .= '"' . str_replace('"', '""', $row[0] ?? '') . '","' .
                str_replace('"', '""', $row[1] ?? '') . '","' .
                str_replace('"', '""', $row[2] ?? '') . '"' . "\n";
        }

        $filename = sprintf(
            'mozambique-financial-compliance-dashboard-%s-to-%s.csv',
            $filters['from_date'],
            $filters['to_date']
        );

        $this->logFiscalExport(
            'financial_compliance_dashboard_csv',
            $filters['from_date'],
            $filters['to_date'],
            $filename,
            $csv,
            ['rows' => count($rows)]
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function markMozambiqueExchangeRepatriation(Request $request)
    {
        if (!Auth::user()->can('manage-account-reports')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $validated = $request->validate([
            'payment_id' => ['required', 'integer', 'min:1'],
            'repatriation_status' => ['required', 'in:pending,partial,completed'],
            'repatriated_amount_mzn' => ['nullable', 'numeric', 'min:0'],
            'fx_compliance_reference' => ['required', 'string', 'max:120'],
            'export_reference' => ['nullable', 'string', 'max:120'],
            'intermediary_bank' => ['nullable', 'string', 'max:120'],
            'receipt_origin_country' => ['nullable', 'string', 'max:120'],
        ]);

        $payment = CustomerPayment::query()
            ->where('created_by', creatorId())
            ->where('id', (int) $validated['payment_id'])
            ->first();

        if (!$payment) {
            return response()->json(['message' => __('Payment not found for the selected company.')], 404);
        }

        if (!(bool) $payment->is_export_receipt) {
            return response()->json([
                'message' => __('Only export receipts can be updated for repatriation control.'),
                'errors' => [
                    'payment_id' => [
                        __('Only export receipts can be updated for repatriation control.'),
                    ],
                ],
            ], 422);
        }

        $finalExportReference = trim((string) (
            $validated['export_reference']
            ?? $payment->export_reference
            ?? ''
        ));
        $finalIntermediaryBank = trim((string) (
            $validated['intermediary_bank']
            ?? $payment->intermediary_bank
            ?? ''
        ));
        $finalReceiptOriginCountry = trim((string) (
            $validated['receipt_origin_country']
            ?? $payment->receipt_origin_country
            ?? ''
        ));

        $status = (string) $validated['repatriation_status'];
        $repatriatedAmount = round((float) ($validated['repatriated_amount_mzn'] ?? 0), 2);
        $totalAmountMzn = round((float) ($payment->amount_mzn ?? $payment->payment_amount ?? 0), 2);

        if (in_array($status, ['partial', 'completed'], true) && $repatriatedAmount <= 0) {
            return response()->json([
                'message' => __('Partial or completed repatriation requires a repatriated amount greater than zero.'),
                'errors' => [
                    'repatriated_amount_mzn' => [
                        __('Partial or completed repatriation requires a repatriated amount greater than zero.'),
                    ],
                ],
            ], 422);
        }

        if ($status === 'completed' && $repatriatedAmount + 0.01 < $totalAmountMzn) {
            return response()->json([
                'message' => __('Completed repatriation must cover the full received amount in MZN.'),
                'errors' => [
                    'repatriated_amount_mzn' => [
                        __('Completed repatriation must cover the full received amount in MZN.'),
                    ],
                ],
            ], 422);
        }

        if (in_array($status, ['partial', 'completed'], true)) {
            $missingFields = [];

            if ($finalExportReference === '') {
                $missingFields['export_reference'] = [
                    __('Export reference is required to mark repatriation as partial or completed.'),
                ];
            }

            if ($finalIntermediaryBank === '') {
                $missingFields['intermediary_bank'] = [
                    __('Intermediary bank is required to mark repatriation as partial or completed.'),
                ];
            }

            if ($finalReceiptOriginCountry === '') {
                $missingFields['receipt_origin_country'] = [
                    __('Receipt origin country is required to mark repatriation as partial or completed.'),
                ];
            }

            if ($missingFields !== []) {
                return response()->json([
                    'message' => __('Export repatriation requires a complete documentary trail before it can be marked as partial or completed.'),
                    'errors' => $missingFields,
                ], 422);
            }
        }

        $payment->repatriation_status = $status;
        $payment->repatriated_amount_mzn = $status === 'pending' && $repatriatedAmount <= 0
            ? null
            : $repatriatedAmount;
        $payment->fx_compliance_reference = trim((string) $validated['fx_compliance_reference']);
        $payment->export_reference = $finalExportReference !== ''
            ? $finalExportReference
            : $payment->export_reference;

        $payment->intermediary_bank = $finalIntermediaryBank !== ''
            ? $finalIntermediaryBank
            : $payment->intermediary_bank;
        $payment->receipt_origin_country = $finalReceiptOriginCountry !== ''
            ? $finalReceiptOriginCountry
            : $payment->receipt_origin_country;

        $payment->save();
        $this->exchangeControlDossierService->syncInboundCustomerPayment($payment);
        AccountCacheService::bumpForCompany((int) creatorId());

        return response()->json([
            'message' => __('Repatriation status updated successfully.'),
            'data' => [
                'payment_id' => (int) $payment->id,
                'payment_number' => $payment->payment_number,
                'repatriation_status' => $payment->repatriation_status,
                'repatriated_amount_mzn' => (float) ($payment->repatriated_amount_mzn ?? 0),
                'fx_compliance_reference' => $payment->fx_compliance_reference,
            ],
        ]);
    }

    public function upsertMozambiqueExchangeDossier(Request $request)
    {
        if (!Auth::user()->can('manage-account-reports')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $validated = $request->validate([
            'direction' => ['required', 'in:outbound,inbound'],
            'payment_id' => ['required', 'integer', 'min:1'],
            'contract_reference' => ['nullable', 'string', 'max:255'],
            'invoice_reference' => ['nullable', 'string', 'max:255'],
            'transport_document_reference' => ['nullable', 'string', 'max:255'],
            'customs_declaration_reference' => ['nullable', 'string', 'max:255'],
            'bank_settlement_reference' => ['nullable', 'string', 'max:255'],
            'withholding_receipt_reference' => ['nullable', 'string', 'max:255'],
            'fx_authorization_reference' => ['nullable', 'string', 'max:255'],
            'correspondence_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $direction = (string) $validated['direction'];
        $paymentId = (int) $validated['payment_id'];
        $modelClass = $direction === 'outbound' ? VendorPayment::class : CustomerPayment::class;
        $paymentType = $direction === 'outbound' ? 'vendor_payment' : 'customer_payment';

        /** @var \Workdo\Account\Models\VendorPayment|\Workdo\Account\Models\CustomerPayment|null $payment */
        $payment = $modelClass::query()
            ->where('created_by', creatorId())
            ->where('id', $paymentId)
            ->first();

        if (!$payment) {
            return response()->json(['message' => __('Payment not found for the selected company.')], 404);
        }

        $dossier = ExchangeControlDossier::query()->firstOrNew([
            'company_id' => creatorId(),
            'direction' => $direction,
            'payment_type' => $paymentType,
            'payment_id' => $paymentId,
        ]);

        $documents = is_array($dossier->documents) ? $dossier->documents : [];
        $documentKeys = [
            'contract_reference',
            'invoice_reference',
            'transport_document_reference',
            'customs_declaration_reference',
            'bank_settlement_reference',
            'withholding_receipt_reference',
            'fx_authorization_reference',
            'correspondence_reference',
        ];

        foreach ($documentKeys as $key) {
            if ($request->has($key)) {
                $documents[$key] = trim((string) $request->input($key, ''));
            }
        }

        if ($direction === 'outbound' && trim((string) ($documents['fx_authorization_reference'] ?? '')) === '') {
            $documents['fx_authorization_reference'] = trim((string) ($payment->fx_authorization_reference ?? ''));
        }

        if ($direction === 'inbound' && trim((string) ($documents['invoice_reference'] ?? '')) === '') {
            $documents['invoice_reference'] = trim((string) ($payment->export_reference ?? $payment->reference_number ?? ''));
        }

        if ($direction === 'inbound' && trim((string) ($documents['bank_settlement_reference'] ?? '')) === '') {
            $documents['bank_settlement_reference'] = trim((string) ($payment->fx_compliance_reference ?? ''));
        }

        $required = $this->resolveExchangeDossierRequirements($direction, $payment);
        $missing = array_values(array_filter($required, static function (string $field) use ($documents): bool {
            return trim((string) ($documents[$field] ?? '')) === '';
        }));
        $isComplete = count($missing) === 0;

        $dossier->payment_reference = (string) ($payment->payment_number ?: $payment->reference_number ?: $payment->id);
        $dossier->operation_date = $payment->payment_date;
        $dossier->counterparty_name = $direction === 'outbound'
            ? optional($payment->vendor)->name
            : optional($payment->customer)->name;
        $dossier->counterparty_country = $direction === 'outbound'
            ? (string) ($payment->beneficiary_country ?? '')
            : (string) ($payment->receipt_origin_country ?? '');
        $dossier->currency_code = (string) ($payment->currency_code ?? 'MZN');
        $dossier->amount_mzn = round((float) ($payment->amount_mzn ?? $payment->payment_amount ?? 0), 2);
        $dossier->documents = $documents;
        $dossier->required_documents = $required;
        $dossier->missing_documents = $missing;
        $dossier->is_complete = $isComplete;
        $dossier->completed_at = $isComplete ? now() : null;
        $dossier->completed_by = $isComplete ? Auth::id() : null;
        $dossier->created_by = creatorId();
        $dossier->updated_by = Auth::id();
        $dossier->save();

        AccountCacheService::bumpForCompany((int) creatorId());

        return response()->json([
            'message' => __('Exchange-control dossier saved successfully.'),
            'data' => [
                'id' => (int) $dossier->id,
                'direction' => $dossier->direction,
                'payment_id' => (int) $dossier->payment_id,
                'payment_type' => $dossier->payment_type,
                'is_complete' => (bool) $dossier->is_complete,
                'missing_documents' => $dossier->missing_documents ?? [],
                'required_documents' => $dossier->required_documents ?? [],
                'documents' => $dossier->documents ?? [],
            ],
        ]);
    }

    public function mozambiqueGifimComplianceReport(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $filters = $this->resolveDateFilters($request);

        $data = $this->rememberReportPayload(
            'mz-gifim-compliance-report',
            $filters,
            fn () => $this->reportService->getMozambiqueGifimComplianceReport($filters),
            $request
        );

        return response()->json($data);
    }

    public function exportMozambiqueGifimComplianceReport(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return back()->with('error', __('Permission denied'));
        }

        $filters = $this->resolveDateFilters($request);

        $data = $this->rememberReportPayload(
            'mz-gifim-compliance-report',
            $filters,
            fn () => $this->reportService->getMozambiqueGifimComplianceReport($filters),
            $request
        );

        $rows = [
            ['section', 'metric', 'value'],
            ['period', 'from_date', (string) ($data['from_date'] ?? '')],
            ['period', 'to_date', (string) ($data['to_date'] ?? '')],
            ['summary', 'total_operations', (string) data_get($data, 'summary.total_operations', 0)],
            ['summary', 'total_alert_required', (string) data_get($data, 'summary.total_alert_required', 0)],
            ['summary', 'cash_threshold_alerts', (string) data_get($data, 'summary.cash_threshold_alerts', 0)],
            ['summary', 'electronic_threshold_alerts', (string) data_get($data, 'summary.electronic_threshold_alerts', 0)],
            ['summary', 'pending_alerts', (string) data_get($data, 'summary.pending_alerts', 0)],
            ['summary', 'communicated_alerts', (string) data_get($data, 'summary.communicated_alerts', 0)],
            ['summary', 'missing_high_value_approval_reference', (string) data_get($data, 'summary.missing_high_value_approval_reference', 0)],
            ['summary', 'missing_communication_evidence', (string) data_get($data, 'summary.missing_communication_evidence', 0)],
            ['', '', ''],
            ['operations', 'payment_reference', 'direction|payment_date|counterparty|payment_method|currency|amount_mzn|gifim_category|gifim_status|approval_reference|gifim_reference|submitted_document'],
        ];

        foreach ((array) data_get($data, 'operations', []) as $operation) {
            $rows[] = [
                'operations',
                (string) data_get($operation, 'payment_reference', ''),
                implode('|', [
                    (string) data_get($operation, 'direction', ''),
                    (string) data_get($operation, 'payment_date', ''),
                    (string) data_get($operation, 'counterparty', ''),
                    (string) data_get($operation, 'payment_method', ''),
                    (string) data_get($operation, 'currency_code', ''),
                    number_format((float) data_get($operation, 'amount_mzn', 0), 2, '.', ''),
                    (string) data_get($operation, 'gifim_alert_category', ''),
                    (string) data_get($operation, 'gifim_alert_status', ''),
                    (string) data_get($operation, 'high_value_approval_reference', ''),
                    (string) data_get($operation, 'gifim_reference', ''),
                    (string) data_get($operation, 'gifim_submitted_document', ''),
                ]),
            ];
        }

        $csv = '';
        foreach ($rows as $row) {
            $csv .= '"' . str_replace('"', '""', $row[0] ?? '') . '","' .
                str_replace('"', '""', $row[1] ?? '') . '","' .
                str_replace('"', '""', $row[2] ?? '') . '"' . "\n";
        }

        $filename = sprintf(
            'mozambique-gifim-compliance-report-%s-to-%s.csv',
            $filters['from_date'],
            $filters['to_date']
        );

        $this->logFiscalExport(
            'gifim_compliance_report_csv',
            $filters['from_date'],
            $filters['to_date'],
            $filename,
            $csv,
            ['rows' => count($rows)]
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function markMozambiqueGifimCommunication(Request $request)
    {
        if (!Auth::user()->can('manage-account-reports')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $validated = $request->validate([
            'direction' => ['required', 'in:outbound,inbound'],
            'payment_id' => ['required', 'integer', 'min:1'],
            'gifim_reference' => ['required', 'string', 'max:120'],
            'gifim_submitted_document' => ['required', 'string', 'max:255'],
            'gifim_reported_at' => ['nullable', 'date'],
            'gifim_justification' => ['nullable', 'string', 'max:255'],
            'high_value_approval_reference' => ['nullable', 'string', 'max:120'],
        ]);

        $modelClass = $validated['direction'] === 'outbound'
            ? VendorPayment::class
            : CustomerPayment::class;

        $payment = $modelClass::query()
            ->where('created_by', creatorId())
            ->where('id', (int) $validated['payment_id'])
            ->first();

        if (!$payment) {
            return response()->json(['message' => __('Payment not found for the selected company.')], 404);
        }

        $amountMzn = round((float) ($payment->amount_mzn ?? $payment->payment_amount ?? 0), 2);
        $thresholdCategory = $this->resolveGifimThresholdCategoryForPayment((string) ($payment->payment_method ?? ''), $amountMzn);
        $storedCategory = in_array((string) ($payment->gifim_alert_category ?? ''), ['cash_threshold', 'electronic_threshold'], true)
            ? (string) $payment->gifim_alert_category
            : null;
        $alertRequired = $thresholdCategory !== null
            || (bool) $payment->gifim_alert_required
            || $storedCategory !== null;

        if (
            !$alertRequired
            || in_array((string) ($payment->status ?? ''), ['cancelled'], true)
        ) {
            return response()->json([
                'message' => __('Only GIFiM-relevant active operations can be marked as communicated.'),
                'errors' => [
                    'payment_id' => [
                        __('Only GIFiM-relevant active operations can be marked as communicated.'),
                    ],
                ],
            ], 422);
        }

        $approvalReference = trim((string) ($validated['high_value_approval_reference'] ?? ''));
        if ($approvalReference === '') {
            $approvalReference = trim((string) ($payment->high_value_approval_reference ?? ''));
        }

        if ($approvalReference === '') {
            return response()->json([
                'message' => __('High-value operations require an approval reference before confirming GIFiM communication.'),
                'errors' => [
                    'high_value_approval_reference' => [
                        __('High-value operations require an approval reference before confirming GIFiM communication.'),
                    ],
                ],
            ], 422);
        }

        $payment->gifim_alert_required = $alertRequired;
        $payment->gifim_alert_category = $thresholdCategory ?? $storedCategory;
        $payment->gifim_alert_status = 'communicated';
        $payment->gifim_reference = trim((string) $validated['gifim_reference']);
        $payment->gifim_reported_at = $validated['gifim_reported_at'] ?? now();
        $payment->gifim_reported_by = Auth::id();
        $payment->gifim_submitted_document = trim((string) $validated['gifim_submitted_document']);
        $payment->gifim_justification = !empty($validated['gifim_justification'])
            ? trim((string) $validated['gifim_justification'])
            : $payment->gifim_justification;
        $payment->high_value_approval_reference = $approvalReference !== '' ? $approvalReference : null;
        $payment->save();

        $reportedAt = $payment->gifim_reported_at
            ? $payment->gifim_reported_at->toDateTimeString()
            : now()->toDateTimeString();
        $referenceLabel = (string) ($payment->reference_number ?: $payment->payment_number ?: $payment->id);
        $safeReference = (string) preg_replace('/[^a-zA-Z0-9\-_]+/', '-', $referenceLabel);
        if ($safeReference === '') {
            $safeReference = 'payment-' . (string) $payment->id;
        }
        $fileName = sprintf(
            'mozambique-gifim-communication-%s-%s.json',
            $validated['direction'],
            $safeReference
        );
        $payload = [
            'direction' => $validated['direction'],
            'payment_id' => (int) $payment->id,
            'payment_reference' => $referenceLabel,
            'gifim_alert_category' => $payment->gifim_alert_category,
            'gifim_reference' => $payment->gifim_reference,
            'gifim_submitted_document' => $payment->gifim_submitted_document,
            'high_value_approval_reference' => $payment->high_value_approval_reference,
            'reported_at' => $reportedAt,
        ];

        $this->logFiscalExport(
            'gifim_communication_notice',
            substr($reportedAt, 0, 10),
            substr($reportedAt, 0, 10),
            $fileName,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            $payload
        );

        return response()->json([
            'message' => __('GIFiM communication status updated successfully.'),
            'data' => [
                'direction' => $validated['direction'],
                'payment_id' => (int) $payment->id,
                'gifim_alert_required' => (bool) $payment->gifim_alert_required,
                'gifim_alert_category' => $payment->gifim_alert_category,
                'gifim_alert_status' => $payment->gifim_alert_status,
                'gifim_reference' => $payment->gifim_reference,
                'gifim_reported_at' => optional($payment->gifim_reported_at)->toDateTimeString(),
                'gifim_reported_by' => $payment->gifim_reported_by,
            ],
        ]);
    }

    public function mozambiqueElectronicMoneyComplianceReport(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $filters = $this->resolveDateFilters($request);

        $data = $this->rememberReportPayload(
            'mz-electronic-money-compliance-report',
            $filters,
            fn () => $this->reportService->getMozambiqueElectronicMoneyComplianceReport($filters),
            $request
        );

        return response()->json($data);
    }

    public function exportMozambiqueElectronicMoneyComplianceReport(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return back()->with('error', __('Permission denied'));
        }

        $filters = $this->resolveDateFilters($request);

        $data = $this->rememberReportPayload(
            'mz-electronic-money-compliance-report',
            $filters,
            fn () => $this->reportService->getMozambiqueElectronicMoneyComplianceReport($filters),
            $request
        );

        $rows = [
            ['section', 'metric', 'value'],
            ['period', 'from_date', (string) ($data['from_date'] ?? '')],
            ['period', 'to_date', (string) ($data['to_date'] ?? '')],
            ['summary', 'electronic_money_accounts', (string) data_get($data, 'summary.electronic_money_accounts', 0)],
            ['summary', 'missing_classification', (string) data_get($data, 'summary.missing_classification', 0)],
            ['summary', 'enterprise_exemption_misconfigured', (string) data_get($data, 'summary.enterprise_exemption_misconfigured', 0)],
            ['summary', 'monthly_limit_exceeded', (string) data_get($data, 'summary.monthly_limit_exceeded', 0)],
            ['summary', 'monthly_limit_near_threshold', (string) data_get($data, 'summary.monthly_limit_near_threshold', 0)],
            ['', '', ''],
            ['missing_classification', 'account', 'account_number|account_name|bank_name|electronic_money_entity|electronic_money_level'],
        ];

        foreach ((array) data_get($data, 'missing_classification', []) as $entry) {
            $rows[] = [
                'missing_classification',
                (string) data_get($entry, 'account_number', ''),
                implode('|', [
                    (string) data_get($entry, 'account_number', ''),
                    (string) data_get($entry, 'account_name', ''),
                    (string) data_get($entry, 'bank_name', ''),
                    (string) data_get($entry, 'electronic_money_entity', ''),
                    (string) data_get($entry, 'electronic_money_level', ''),
                ]),
            ];
        }

        $rows[] = ['', '', ''];
        $rows[] = ['enterprise_exemption_misconfigured', 'account', 'account_number|account_name|bank_name|company_classification|electronic_money_account_purpose|requires_attention_reason'];
        foreach ((array) data_get($data, 'enterprise_exemption_misconfigured', []) as $entry) {
            $rows[] = [
                'enterprise_exemption_misconfigured',
                (string) data_get($entry, 'account_number', ''),
                implode('|', [
                    (string) data_get($entry, 'account_number', ''),
                    (string) data_get($entry, 'account_name', ''),
                    (string) data_get($entry, 'bank_name', ''),
                    (string) data_get($entry, 'company_classification', ''),
                    (string) data_get($entry, 'electronic_money_account_purpose', ''),
                    (string) data_get($entry, 'requires_attention_reason', ''),
                ]),
            ];
        }

        $rows[] = ['', '', ''];
        $rows[] = ['monthly_limit_exceeded', 'account', 'account_number|usage_mzn|monthly_limit_mzn|usage_ratio'];
        foreach ((array) data_get($data, 'monthly_limit_exceeded', []) as $entry) {
            $rows[] = [
                'monthly_limit_exceeded',
                (string) data_get($entry, 'account_number', ''),
                implode('|', [
                    (string) data_get($entry, 'account_number', ''),
                    number_format((float) data_get($entry, 'usage_mzn', 0), 2, '.', ''),
                    number_format((float) data_get($entry, 'monthly_limit_mzn', 0), 2, '.', ''),
                    number_format((float) data_get($entry, 'usage_ratio', 0), 4, '.', ''),
                ]),
            ];
        }

        $rows[] = ['', '', ''];
        $rows[] = ['monthly_limit_near_threshold', 'account', 'account_number|usage_mzn|monthly_limit_mzn|usage_ratio'];
        foreach ((array) data_get($data, 'monthly_limit_near_threshold', []) as $entry) {
            $rows[] = [
                'monthly_limit_near_threshold',
                (string) data_get($entry, 'account_number', ''),
                implode('|', [
                    (string) data_get($entry, 'account_number', ''),
                    number_format((float) data_get($entry, 'usage_mzn', 0), 2, '.', ''),
                    number_format((float) data_get($entry, 'monthly_limit_mzn', 0), 2, '.', ''),
                    number_format((float) data_get($entry, 'usage_ratio', 0), 4, '.', ''),
                ]),
            ];
        }

        $csv = '';
        foreach ($rows as $row) {
            $csv .= '"' . str_replace('"', '""', $row[0] ?? '') . '","' .
                str_replace('"', '""', $row[1] ?? '') . '","' .
                str_replace('"', '""', $row[2] ?? '') . '"' . "\n";
        }

        $filename = sprintf(
            'mozambique-electronic-money-compliance-report-%s-to-%s.csv',
            $filters['from_date'],
            $filters['to_date']
        );

        $this->logFiscalExport(
            'electronic_money_compliance_report_csv',
            $filters['from_date'],
            $filters['to_date'],
            $filename,
            $csv,
            ['rows' => count($rows)]
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function mozambiqueCostCenterAnalysis(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary') && !Auth::user()->can('manage-account-reports')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $currentYear = date('Y');
        $filters = [
            'from_date' => $request->from_date ?: "$currentYear-01-01",
            'to_date' => $request->to_date ?: "$currentYear-12-31",
            'reference_period' => $request->reference_period ?: now()->format('Y-m'),
        ];

        $data = $this->rememberReportPayload(
            'mz-cost-center-analysis',
            $filters,
            fn () => $this->reportService->getMozambiqueCostCenterAnalysis($filters),
            $request
        );

        return response()->json($data);
    }

    public function exportMozambiqueCostCenterAnalysis(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary') && !Auth::user()->can('manage-account-reports')) {
            return back()->with('error', __('Permission denied'));
        }

        $currentYear = date('Y');
        $filters = [
            'from_date' => $request->from_date ?: "$currentYear-01-01",
            'to_date' => $request->to_date ?: "$currentYear-12-31",
            'reference_period' => $request->reference_period ?: now()->format('Y-m'),
        ];

        $data = $this->rememberReportPayload(
            'mz-cost-center-analysis',
            $filters,
            fn () => $this->reportService->getMozambiqueCostCenterAnalysis($filters),
            $request
        );

        $rows = [
            ['section', 'metric', 'value'],
            ['period', 'from_date', (string) ($data['from_date'] ?? '')],
            ['period', 'to_date', (string) ($data['to_date'] ?? '')],
            ['period', 'reference_period', (string) ($data['reference_period'] ?? '')],
            ['summary', 'journal_lines', (string) data_get($data, 'summary.journal_lines', 0)],
            ['summary', 'journals', (string) data_get($data, 'summary.journals', 0)],
            ['summary', 'cost_centers', (string) data_get($data, 'summary.cost_centers', 0)],
            ['summary', 'assigned_lines', (string) data_get($data, 'summary.assigned_lines', 0)],
            ['summary', 'unassigned_lines', (string) data_get($data, 'summary.unassigned_lines', 0)],
            ['summary', 'required_missing_lines', (string) data_get($data, 'summary.required_missing_lines', 0)],
            ['summary', 'assigned_debit_total', number_format((float) data_get($data, 'summary.assigned_debit_total', 0), 2, '.', '')],
            ['summary', 'assigned_credit_total', number_format((float) data_get($data, 'summary.assigned_credit_total', 0), 2, '.', '')],
            ['summary', 'assigned_net_total', number_format((float) data_get($data, 'summary.assigned_net_total', 0), 2, '.', '')],
            ['summary', 'payroll_rows', (string) data_get($data, 'summary.payroll_rows', 0)],
            ['summary', 'payroll_cost_centers', (string) data_get($data, 'summary.payroll_cost_centers', 0)],
            ['summary', 'payroll_departments', (string) data_get($data, 'summary.payroll_departments', 0)],
            ['summary', 'payroll_branches', (string) data_get($data, 'summary.payroll_branches', 0)],
            ['summary', 'payroll_projects', (string) data_get($data, 'summary.payroll_projects', 0)],
        ];

        foreach ((array) data_get($data, 'cost_centers', []) as $row) {
            $rows[] = [
                'cost_center',
                (string) data_get($row, 'cost_center_code', ''),
                implode('|', [
                    (string) data_get($row, 'cost_center_name', ''),
                    (string) data_get($row, 'parent_cost_center_name', ''),
                    (string) data_get($row, 'journal_count', 0),
                    (string) data_get($row, 'line_count', 0),
                    number_format((float) data_get($row, 'debit_total', 0), 2, '.', ''),
                    number_format((float) data_get($row, 'credit_total', 0), 2, '.', ''),
                    number_format((float) data_get($row, 'net_total', 0), 2, '.', ''),
                ]),
            ];
        }

        foreach ((array) data_get($data, 'required_missing_lines', []) as $row) {
            $rows[] = [
                'required_missing',
                (string) data_get($row, 'journal_number', ''),
                implode('|', [
                    (string) data_get($row, 'journal_date', ''),
                    (string) data_get($row, 'account_code', ''),
                    (string) data_get($row, 'account_name', ''),
                    (string) data_get($row, 'reference_type', ''),
                    number_format((float) data_get($row, 'debit_amount', 0), 2, '.', ''),
                    number_format((float) data_get($row, 'credit_amount', 0), 2, '.', ''),
                ]),
            ];
        }

        foreach ((array) data_get($data, 'reference_types', []) as $row) {
            $rows[] = [
                'reference_type',
                (string) data_get($row, 'reference_type', ''),
                implode('|', [
                    (string) data_get($row, 'journal_count', 0),
                    (string) data_get($row, 'line_count', 0),
                    (string) data_get($row, 'assigned_lines', 0),
                    (string) data_get($row, 'required_missing_lines', 0),
                    (string) data_get($row, 'unassigned_lines', 0),
                    number_format((float) data_get($row, 'debit_total', 0), 2, '.', ''),
                    number_format((float) data_get($row, 'credit_total', 0), 2, '.', ''),
                    number_format((float) data_get($row, 'net_total', 0), 2, '.', ''),
                ]),
            ];
        }

        $csv = '';
        foreach ($rows as $row) {
            $csv .= '"' . str_replace('"', '""', $row[0] ?? '') . '","' .
                str_replace('"', '""', $row[1] ?? '') . '","' .
                str_replace('"', '""', $row[2] ?? '') . '"' . "\n";
        }

        $filename = sprintf(
            'mozambique-cost-center-analysis-%s-to-%s.csv',
            $filters['from_date'],
            $filters['to_date']
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function mozambiqueFiscalExportsHistory(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary') && !Auth::user()->can('manage-account-reports')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        if (!Schema::hasTable('fiscal_export_histories')) {
            return response()->json([
                'from_date' => null,
                'to_date' => null,
                'rows' => [],
                'summary_by_status' => [],
                'summary_by_type' => [],
            ]);
        }

        $currentYear = date('Y');
        $fromDate = $request->from_date ?: "$currentYear-01-01";
        $toDate = $request->to_date ?: "$currentYear-12-31";
        $exportType = $request->export_type ? trim((string) $request->export_type) : null;
        $status = $request->status ? trim((string) $request->status) : null;
        $limit = max(10, min((int) $request->integer('limit', 200), 500));

        $query = FiscalExportHistory::query()
            ->where('company_id', creatorId())
            ->where(function ($q) use ($fromDate, $toDate): void {
                $q->whereBetween('created_at', ["{$fromDate} 00:00:00", "{$toDate} 23:59:59"])
                    ->orWhere(function ($q2) use ($fromDate, $toDate): void {
                        $q2->whereDate('period_start', '<=', $toDate)
                            ->whereDate('period_end', '>=', $fromDate);
                    });
            });

        if ($exportType !== null && $exportType !== '') {
            $query->where('export_type', $exportType);
        }

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        $rows = $query
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return response()->json([
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'rows' => $rows->map(static function (FiscalExportHistory $history): array {
                return [
                    'id' => $history->id,
                    'company_id' => $history->company_id,
                    'export_type' => $history->export_type,
                    'period_start' => $history->period_start?->toDateString(),
                    'period_end' => $history->period_end?->toDateString(),
                    'generated_by' => $history->generated_by,
                    'generated_at' => $history->created_at?->toDateTimeString(),
                    'file_name' => $history->file_name,
                    'file_hash' => $history->file_hash,
                    'file_path' => $history->file_path,
                    'status' => $history->status,
                    'submission_channel' => $history->submission_channel,
                    'submission_reference' => $history->submission_reference,
                    'submitted_at' => $history->submitted_at?->toDateTimeString(),
                    'metadata' => $history->metadata ?? [],
                ];
            })->values(),
            'summary_by_status' => $rows->groupBy('status')->map(static fn ($group) => $group->count()),
            'summary_by_type' => $rows->groupBy('export_type')->map(static fn ($group) => $group->count()),
        ]);
    }

    public function confirmMozambiqueFiscalExportSubmission(Request $request, FiscalExportHistory $history)
    {
        if (!Auth::user()->can('manage-account-reports')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        if (!Schema::hasTable('fiscal_export_histories')) {
            return response()->json([
                'message' => __('Fiscal export history table not found. Run database migrations first.'),
            ], 422);
        }

        if ((int) $history->company_id !== (int) creatorId()) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $validated = $request->validate([
            'submission_channel' => ['required', 'in:manual_upload,xml_export,webservice,api'],
            'submission_reference' => ['required', 'string', 'max:120'],
            'status' => ['nullable', 'in:submitted,validated,rejected'],
            'submitted_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $metadata = is_array($history->metadata) ? $history->metadata : [];
        if (!empty($validated['notes'])) {
            $metadata['submission_notes'] = $validated['notes'];
        }
        $metadata['submission_updated_by'] = Auth::id();
        $metadata['submission_updated_at'] = now()->toIso8601String();

        $history->update([
            'status' => $validated['status'] ?? 'submitted',
            'submission_channel' => $validated['submission_channel'],
            'submission_reference' => $validated['submission_reference'],
            'submitted_at' => $validated['submitted_at'] ?? now(),
            'metadata' => $metadata,
        ]);

        return response()->json([
            'message' => __('Fiscal export submission confirmed successfully.'),
            'data' => [
                'id' => $history->id,
                'status' => $history->status,
                'submission_channel' => $history->submission_channel,
                'submission_reference' => $history->submission_reference,
                'submitted_at' => $history->submitted_at?->toDateTimeString(),
                'metadata' => $history->metadata ?? [],
            ],
        ]);
    }

    public function downloadMozambiqueFiscalExport(FiscalExportHistory $history)
    {
        if (!Auth::user()->can('view-tax-summary') && !Auth::user()->can('manage-account-reports')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        if ((int) $history->company_id !== (int) creatorId()) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        if (empty($history->file_path) || !Storage::disk('local')->exists($history->file_path)) {
            return response()->json(['message' => __('Fiscal export artifact not found.')], 404);
        }

        $downloadName = $history->file_name ?: basename($history->file_path);

        return response()->download(Storage::disk('local')->path($history->file_path), $downloadName);
    }

    public function exportMozambiqueSaft(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary') && !Auth::user()->can('manage-account-reports')) {
            return back()->with('error', __('Permission denied'));
        }

        $filters = $this->resolveDateFilters($request);
        $xsdRequired = (bool) config('sce.saft.require_xsd_validation', false);
        $xsdPath = (string) config('sce.saft.xsd_path', '');
        $xsdPathReady = $xsdPath !== '' && is_file($xsdPath) && is_readable($xsdPath);

        try {
            $xml = $this->saftExportService->generate(
                creatorId(),
                $filters['from_date'],
                $filters['to_date']
            );
            $this->saftExportService->validateGeneratedXml($xml);
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $filename = sprintf(
            'mozambique-saft-%s-to-%s.xml',
            $filters['from_date'],
            $filters['to_date']
        );

        $this->logFiscalExport(
            'saft_xml',
            $filters['from_date'],
            $filters['to_date'],
            $filename,
            $xml,
            [
                'content_type' => 'application/xml',
                'validation' => [
                    'well_formed' => true,
                    'xsd_required' => $xsdRequired,
                    'xsd_path_configured' => $xsdPath !== '',
                    'xsd_path_ready' => $xsdPathReady,
                    'xsd_validated' => $xsdRequired && $xsdPathReady,
                ],
                'fiscal_year' => substr($filters['from_date'], 0, 4),
                'xml_size_bytes' => strlen($xml),
                'generated_at' => now()->toIso8601String(),
            ]
        );

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function mozambiqueGoLiveReadiness()
    {
        if (!Auth::user()->can('manage-account-reports')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        return response()->json(
            $this->reportService->getMozambiqueGoLiveReadiness()
        );
    }

    public function updateMozambiqueGoLiveReadinessAttestation(Request $request)
    {
        if (!Auth::user()->can('manage-account-reports')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $validated = $request->validate([
            'legal_review_status' => ['nullable', 'in:pending,in_progress,approved,rejected'],
            'legal_reviewed_at' => ['nullable', 'date'],
            'legal_notes' => ['nullable', 'string', 'max:1000'],
            'legal_tables_validation_status' => ['nullable', 'in:not_started,in_progress,completed'],
            'legal_tables_validation_completed_at' => ['nullable', 'date'],
            'legal_tables_validation_notes' => ['nullable', 'string', 'max:1000'],
            'legal_tables_review_status' => ['nullable', 'in:pending,in_progress,approved,rejected'],
            'legal_tables_reviewed_at' => ['nullable', 'date'],
            'legal_tables_review_notes' => ['nullable', 'string', 'max:1000'],
            'fiscal_calendar_validation_status' => ['nullable', 'in:not_started,in_progress,completed'],
            'fiscal_calendar_validation_completed_at' => ['nullable', 'date'],
            'fiscal_calendar_validation_notes' => ['nullable', 'string', 'max:1000'],
            'fiscal_calendar_export_status' => ['nullable', 'in:not_started,in_progress,generated,validated'],
            'fiscal_calendar_export_generated_at' => ['nullable', 'date'],
            'fiscal_calendar_export_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'fiscal_calendar_export_file_name' => ['nullable', 'string', 'max:255'],
            'fiscal_calendar_export_notes' => ['nullable', 'string', 'max:1000'],
            'commercial_readiness_status' => ['nullable', 'in:pending,in_progress,approved,rejected'],
            'commercial_reviewed_at' => ['nullable', 'date'],
            'commercial_notes' => ['nullable', 'string', 'max:1000'],
            'pilot_status' => ['nullable', 'in:not_started,in_progress,completed'],
            'pilot_completed_at' => ['nullable', 'date'],
            'pilot_company_count' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'pilot_notes' => ['nullable', 'string', 'max:1000'],
            'payroll_sector_validation_status' => ['nullable', 'in:not_started,in_progress,completed'],
            'payroll_sector_validation_completed_at' => ['nullable', 'date'],
            'payroll_sector_validation_notes' => ['nullable', 'string', 'max:1000'],
            'accounting_local_validation_status' => ['nullable', 'in:not_started,in_progress,completed'],
            'accounting_local_validation_completed_at' => ['nullable', 'date'],
            'accounting_local_validation_notes' => ['nullable', 'string', 'max:1000'],
            'e2e_sales_flow_status' => ['nullable', 'in:not_started,in_progress,completed'],
            'e2e_purchase_flow_status' => ['nullable', 'in:not_started,in_progress,completed'],
            'e2e_pos_flow_status' => ['nullable', 'in:not_started,in_progress,completed'],
            'e2e_payroll_flow_status' => ['nullable', 'in:not_started,in_progress,completed'],
            'e2e_completed_at' => ['nullable', 'date'],
            'e2e_notes' => ['nullable', 'string', 'max:1000'],
            'backup_restore_status' => ['nullable', 'in:not_started,in_progress,completed'],
            'backup_restore_tested_at' => ['nullable', 'date'],
            'backup_restore_evidence_ref' => ['nullable', 'string', 'max:255'],
            'backup_restore_notes' => ['nullable', 'string', 'max:1000'],
            'go_live_approved' => ['nullable', 'in:on,off'],
            'go_live_approved_at' => ['nullable', 'date'],
            'go_live_approval_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $settingMap = [
            'legal_review_status' => 'mz_go_live_legal_review_status',
            'legal_reviewed_at' => 'mz_go_live_legal_reviewed_at',
            'legal_notes' => 'mz_go_live_legal_notes',
            'legal_tables_validation_status' => 'mz_legal_tables_validation_status',
            'legal_tables_validation_completed_at' => 'mz_legal_tables_validation_completed_at',
            'legal_tables_validation_notes' => 'mz_legal_tables_validation_notes',
            'legal_tables_review_status' => 'mz_legal_tables_review_status',
            'legal_tables_reviewed_at' => 'mz_legal_tables_reviewed_at',
            'legal_tables_review_notes' => 'mz_legal_tables_review_notes',
            'fiscal_calendar_validation_status' => 'mz_fiscal_calendar_validation_status',
            'fiscal_calendar_validation_completed_at' => 'mz_fiscal_calendar_validation_completed_at',
            'fiscal_calendar_validation_notes' => 'mz_fiscal_calendar_validation_notes',
            'fiscal_calendar_export_status' => 'mz_fiscal_calendar_export_status',
            'fiscal_calendar_export_generated_at' => 'mz_fiscal_calendar_export_generated_at',
            'fiscal_calendar_export_year' => 'mz_fiscal_calendar_export_year',
            'fiscal_calendar_export_file_name' => 'mz_fiscal_calendar_export_file_name',
            'fiscal_calendar_export_notes' => 'mz_fiscal_calendar_export_notes',
            'commercial_readiness_status' => 'mz_go_live_commercial_status',
            'commercial_reviewed_at' => 'mz_go_live_commercial_reviewed_at',
            'commercial_notes' => 'mz_go_live_commercial_notes',
            'pilot_status' => 'mz_go_live_pilot_status',
            'pilot_completed_at' => 'mz_go_live_pilot_completed_at',
            'pilot_company_count' => 'mz_go_live_pilot_company_count',
            'pilot_notes' => 'mz_go_live_pilot_notes',
            'payroll_sector_validation_status' => 'mz_go_live_payroll_sector_validation_status',
            'payroll_sector_validation_completed_at' => 'mz_go_live_payroll_sector_validation_completed_at',
            'payroll_sector_validation_notes' => 'mz_go_live_payroll_sector_validation_notes',
            'accounting_local_validation_status' => 'mz_go_live_accounting_local_validation_status',
            'accounting_local_validation_completed_at' => 'mz_go_live_accounting_local_validation_completed_at',
            'accounting_local_validation_notes' => 'mz_go_live_accounting_local_validation_notes',
            'e2e_sales_flow_status' => 'mz_go_live_e2e_sales_flow_status',
            'e2e_purchase_flow_status' => 'mz_go_live_e2e_purchase_flow_status',
            'e2e_pos_flow_status' => 'mz_go_live_e2e_pos_flow_status',
            'e2e_payroll_flow_status' => 'mz_go_live_e2e_payroll_flow_status',
            'e2e_completed_at' => 'mz_go_live_e2e_completed_at',
            'e2e_notes' => 'mz_go_live_e2e_notes',
            'backup_restore_status' => 'mz_go_live_backup_restore_status',
            'backup_restore_tested_at' => 'mz_go_live_backup_restore_tested_at',
            'backup_restore_evidence_ref' => 'mz_go_live_backup_restore_evidence_ref',
            'backup_restore_notes' => 'mz_go_live_backup_restore_notes',
            'go_live_approved' => 'mz_go_live_formal_approval',
            'go_live_approved_at' => 'mz_go_live_formal_approval_at',
            'go_live_approval_notes' => 'mz_go_live_formal_approval_notes',
        ];

        foreach ($settingMap as $payloadKey => $settingKey) {
            if (array_key_exists($payloadKey, $validated)) {
                setSetting($settingKey, $validated[$payloadKey] ?? '', creatorId());
            }
        }
        AccountCacheService::bumpForCompany((int) creatorId());

        return response()->json([
            'message' => __('Go-live attestation updated successfully.'),
            'data' => $this->reportService->getMozambiqueGoLiveReadiness(),
        ]);
    }

    public function listMozambiquePilotCompanies()
    {
        if (!Auth::user()->can('manage-account-reports')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        if (!Schema::hasTable('mz_pilot_companies')) {
            return response()->json(['data' => []]);
        }

        $rows = MozPilotCompany::query()
            ->where('created_by', creatorId());

        try {
            $rows = $rows->orderByDesc('id')->get();
        } catch (\Throwable $e) {
            return response()->json(['data' => []]);
        }

        return response()->json(['data' => $rows]);
    }

    public function storeMozambiquePilotCompany(Request $request)
    {
        if (!Auth::user()->can('manage-account-reports')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        if (!Schema::hasTable('mz_pilot_companies')) {
            return response()->json(['message' => __('Pilot companies table not found. Run migrations first.')], 422);
        }

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:180'],
            'company_nuit' => ['nullable', 'string', 'max:32'],
            'industry_sector' => ['nullable', 'string', 'max:120'],
            'contact_name' => ['nullable', 'string', 'max:180'],
            'contact_email' => ['nullable', 'email', 'max:180'],
            'contact_phone' => ['nullable', 'string', 'max:60'],
            'status' => ['required', 'in:planned,active,completed,on_hold,cancelled'],
            'pilot_start_date' => ['nullable', 'date'],
            'pilot_end_date' => ['nullable', 'date', 'after_or_equal:pilot_start_date'],
            'validation_result' => ['nullable', 'in:pending,passed,failed'],
            'validation_signed_at' => ['nullable', 'date'],
            'validation_evidence_ref' => ['nullable', 'string', 'max:255'],
            'validation_notes' => ['nullable', 'string', 'max:2000'],
            'validation_scope' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $record = MozPilotCompany::create([
                ...$validated,
                'creator_id' => Auth::id(),
                'created_by' => creatorId(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => __('Pilot companies table not available in this environment.')], 422);
        }

        return response()->json([
            'message' => __('Pilot company registered successfully.'),
            'data' => $record,
        ]);
    }

    public function updateMozambiquePilotCompany(Request $request, MozPilotCompany $pilotCompany)
    {
        if (!Auth::user()->can('manage-account-reports')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        if ((int) $pilotCompany->created_by !== (int) creatorId()) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:180'],
            'company_nuit' => ['nullable', 'string', 'max:32'],
            'industry_sector' => ['nullable', 'string', 'max:120'],
            'contact_name' => ['nullable', 'string', 'max:180'],
            'contact_email' => ['nullable', 'email', 'max:180'],
            'contact_phone' => ['nullable', 'string', 'max:60'],
            'status' => ['required', 'in:planned,active,completed,on_hold,cancelled'],
            'pilot_start_date' => ['nullable', 'date'],
            'pilot_end_date' => ['nullable', 'date', 'after_or_equal:pilot_start_date'],
            'validation_result' => ['nullable', 'in:pending,passed,failed'],
            'validation_signed_at' => ['nullable', 'date'],
            'validation_evidence_ref' => ['nullable', 'string', 'max:255'],
            'validation_notes' => ['nullable', 'string', 'max:2000'],
            'validation_scope' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $pilotCompany->update($validated);
        } catch (\Throwable $e) {
            return response()->json(['message' => __('Pilot companies table not available in this environment.')], 422);
        }

        return response()->json([
            'message' => __('Pilot company updated successfully.'),
            'data' => $pilotCompany->refresh(),
        ]);
    }

    public function destroyMozambiquePilotCompany(MozPilotCompany $pilotCompany)
    {
        if (!Auth::user()->can('manage-account-reports')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        if ((int) $pilotCompany->created_by !== (int) creatorId()) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        try {
            $pilotCompany->delete();
        } catch (\Throwable $e) {
            return response()->json(['message' => __('Pilot companies table not available in this environment.')], 422);
        }

        return response()->json(['message' => __('Pilot company removed successfully.')]);
    }

    public function listMozambiquePilotValidationCases()
    {
        if (!Auth::user()->can('manage-account-reports')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        if (!Schema::hasTable('mz_pilot_validation_cases')) {
            return response()->json(['data' => []]);
        }

        $rows = MozPilotValidationCase::query()
            ->where('created_by', creatorId());

        try {
            $rows = $rows->orderByDesc('id')->get();
        } catch (\Throwable $e) {
            return response()->json(['data' => []]);
        }

        return response()->json(['data' => $rows]);
    }

    public function storeMozambiquePilotValidationCase(Request $request)
    {
        if (!Auth::user()->can('manage-account-reports')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        if (!Schema::hasTable('mz_pilot_validation_cases')) {
            return response()->json(['message' => __('Pilot validation table not found. Run migrations first.')], 422);
        }

        $validated = $request->validate([
            'domain' => ['required', 'in:payroll,accounting'],
            'company_name' => ['required', 'string', 'max:180'],
            'company_nuit' => ['nullable', 'string', 'max:32'],
            'industry_sector' => ['nullable', 'string', 'max:120'],
            'scenario_code' => ['nullable', 'string', 'max:64'],
            'scenario_description' => ['nullable', 'string', 'max:2000'],
            'result' => ['required', 'in:pending,passed,failed'],
            'executed_at' => ['nullable', 'date'],
            'evidence_ref' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $record = MozPilotValidationCase::create([
                ...$validated,
                'creator_id' => Auth::id(),
                'created_by' => creatorId(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => __('Pilot validation table not available in this environment.')], 422);
        }

        return response()->json([
            'message' => __('Pilot validation case registered successfully.'),
            'data' => $record,
        ]);
    }

    public function updateMozambiquePilotValidationCase(Request $request, MozPilotValidationCase $validationCase)
    {
        if (!Auth::user()->can('manage-account-reports')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        if ((int) $validationCase->created_by !== (int) creatorId()) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $validated = $request->validate([
            'domain' => ['required', 'in:payroll,accounting'],
            'company_name' => ['required', 'string', 'max:180'],
            'company_nuit' => ['nullable', 'string', 'max:32'],
            'industry_sector' => ['nullable', 'string', 'max:120'],
            'scenario_code' => ['nullable', 'string', 'max:64'],
            'scenario_description' => ['nullable', 'string', 'max:2000'],
            'result' => ['required', 'in:pending,passed,failed'],
            'executed_at' => ['nullable', 'date'],
            'evidence_ref' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $validationCase->update($validated);
        } catch (\Throwable $e) {
            return response()->json(['message' => __('Pilot validation table not available in this environment.')], 422);
        }

        return response()->json([
            'message' => __('Pilot validation case updated successfully.'),
            'data' => $validationCase->refresh(),
        ]);
    }

    public function destroyMozambiquePilotValidationCase(MozPilotValidationCase $validationCase)
    {
        if (!Auth::user()->can('manage-account-reports')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        if ((int) $validationCase->created_by !== (int) creatorId()) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        try {
            $validationCase->delete();
        } catch (\Throwable $e) {
            return response()->json(['message' => __('Pilot validation table not available in this environment.')], 422);
        }

        return response()->json(['message' => __('Pilot validation case removed successfully.')]);
    }

    public function fiscalClosings()
    {
        if (!Auth::user()->can('manage-account-reports')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        if (!Schema::hasTable('mz_fiscal_closings')) {
            return response()->json([
                'latest_closed_until' => null,
                'closings' => [],
            ]);
        }

        return response()->json($this->reportService->getFiscalClosings());
    }

    public function exportFiscalClosings(Request $request)
    {
        if (!Auth::user()->can('manage-account-reports')) {
            return back()->with('error', __('Permission denied'));
        }

        if (!Schema::hasTable('mz_fiscal_closings')) {
            return back()->with('error', __('Fiscal closing table not found. Run database migrations first.'));
        }

        $data = $this->reportService->getFiscalClosings();
        $closings = (array) data_get($data, 'closings', []);
        $fromDate = (string) collect($closings)
            ->pluck('period_from')
            ->filter()
            ->sort()
            ->first();
        $toDate = (string) collect($closings)
            ->pluck('period_to')
            ->filter()
            ->sort()
            ->last();

        $rows = [
            ['section', 'metric', 'value'],
            ['summary', 'latest_closed_until', (string) data_get($data, 'latest_closed_until', '')],
            ['summary', 'closings_count', (string) count($closings)],
            ['', '', ''],
            ['closings', 'period', 'status|closed_by|reopened_by|closed_at|reopened_at|close_reason|reopen_reason|net_vat_payable|journal_entries'],
        ];

        foreach ($closings as $closing) {
            $snapshot = (array) data_get($closing, 'snapshot', []);
            $vatNet = data_get($snapshot, 'tax_summary.vat.net_vat_payable', data_get($snapshot, 'vat.net_vat_payable', 0));
            $journalEntries = data_get($snapshot, 'journal_summary.entries', 0);

            $rows[] = [
                'closings',
                trim((string) data_get($closing, 'period_from', '')) . ' - ' . trim((string) data_get($closing, 'period_to', '')),
                implode('|', [
                    (string) data_get($closing, 'status', ''),
                    (string) data_get($closing, 'closed_by', ''),
                    (string) data_get($closing, 'reopened_by', ''),
                    (string) data_get($closing, 'closed_at', ''),
                    (string) data_get($closing, 'reopened_at', ''),
                    (string) data_get($closing, 'close_reason', ''),
                    (string) data_get($closing, 'reopen_reason', ''),
                    number_format((float) $vatNet, 2, '.', ''),
                    (string) $journalEntries,
                ]),
            ];
        }

        $csv = '';
        foreach ($rows as $row) {
            $csv .= '"' . str_replace('"', '""', $row[0] ?? '') . '","' .
                str_replace('"', '""', $row[1] ?? '') . '","' .
                str_replace('"', '""', $row[2] ?? '') . '"' . "\n";
        }

        $filename = sprintf(
            'mozambique-fiscal-closings-%s.csv',
            now()->format('Y-m-d_His')
        );

        $this->logFiscalExport(
            'fiscal_closings_csv',
            $fromDate,
            $toDate,
            $filename,
            $csv,
            ['rows' => count($rows)]
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function closeFiscalPeriod(Request $request)
    {
        if (!Auth::user()->can('manage-account-reports')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        if (!Schema::hasTable('mz_fiscal_closings')) {
            return response()->json([
                'message' => __('Fiscal closing table not found. Run database migrations first.'),
            ], 422);
        }

        $validated = $request->validate([
            'period_from' => ['required', 'date'],
            'period_to' => ['required', 'date', 'after_or_equal:period_from'],
            'close_reason' => ['nullable', 'string'],
        ]);

        $hasOverlap = MozFiscalClosing::query()
            ->where('created_by', creatorId())
            ->where('status', 'closed')
            ->whereDate('period_from', '<=', $validated['period_to'])
            ->whereDate('period_to', '>=', $validated['period_from'])
            ->exists();

        if ($hasOverlap) {
            return response()->json([
                'message' => __('There is already a closed fiscal period overlapping this date range.'),
            ], 422);
        }

        $snapshot = $this->reportService->buildFiscalClosingSnapshot(
            $validated['period_from'],
            $validated['period_to']
        );

        MozFiscalClosing::create([
            'period_from' => $validated['period_from'],
            'period_to' => $validated['period_to'],
            'status' => 'closed',
            'close_reason' => $validated['close_reason'] ?? null,
            'snapshot' => $snapshot,
            'closed_by' => Auth::id(),
            'closed_at' => now(),
            'creator_id' => Auth::id(),
            'created_by' => creatorId(),
        ]);

        return response()->json([
            'message' => __('Fiscal period closed successfully.'),
            'data' => $this->reportService->getFiscalClosings(),
        ]);
    }

    public function reopenFiscalPeriod(Request $request, MozFiscalClosing $closing)
    {
        if (!Auth::user()->can('manage-account-reports')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        if (!Schema::hasTable('mz_fiscal_closings')) {
            return response()->json([
                'message' => __('Fiscal closing table not found. Run database migrations first.'),
            ], 422);
        }

        if ((int) $closing->created_by !== (int) creatorId()) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        if ($closing->status !== 'closed') {
            return response()->json(['message' => __('This period is not in closed state.')], 422);
        }

        $validated = $request->validate([
            'reopen_reason' => ['nullable', 'string'],
        ]);

        $closing->update([
            'status' => 'reopened',
            'reopen_reason' => $validated['reopen_reason'] ?? null,
            'reopened_by' => Auth::id(),
            'reopened_at' => now(),
        ]);

        return response()->json([
            'message' => __('Fiscal period reopened successfully.'),
            'data' => $this->reportService->getFiscalClosings(),
        ]);
    }

    public function cashClosings()
    {
        if (!Auth::user()->can('manage-account-reports')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        return response()->json($this->reportService->getCashClosings());
    }

    public function exportCashClosings(Request $request)
    {
        if (!Auth::user()->can('manage-account-reports')) {
            return back()->with('error', __('Permission denied'));
        }

        if (!Schema::hasTable('mz_cash_closings')) {
            return back()->with('error', __('Cash closing table not found. Run database migrations first.'));
        }

        $data = $this->reportService->getCashClosings();
        $cashAccounts = (array) data_get($data, 'cash_accounts', []);
        $closings = (array) data_get($data, 'closings', []);

        $closingDates = collect($closings)
            ->pluck('closing_date')
            ->filter()
            ->sort()
            ->values();

        $fromDate = (string) ($closingDates->first() ?: '');
        $toDate = (string) ($closingDates->last() ?: '');

        $rows = [
            ['section', 'metric', 'value'],
            ['summary', 'latest_closed_until', (string) data_get($data, 'latest_closed_until', '')],
            ['summary', 'cash_accounts_count', (string) count($cashAccounts)],
            ['summary', 'closings_count', (string) count($closings)],
            ['', '', ''],
            ['cash_accounts', 'bank_account_id', 'account_number|account_name|bank_name|account_type|current_balance_mzn'],
        ];

        foreach ($cashAccounts as $cashAccount) {
            $rows[] = [
                'cash_accounts',
                (string) data_get($cashAccount, 'bank_account_id', ''),
                implode('|', [
                    (string) data_get($cashAccount, 'account_number', ''),
                    (string) data_get($cashAccount, 'account_name', ''),
                    (string) data_get($cashAccount, 'bank_name', ''),
                    (string) data_get($cashAccount, 'account_type', ''),
                    number_format((float) data_get($cashAccount, 'current_balance_mzn', 0), 2, '.', ''),
                ]),
            ];
        }

        $rows[] = ['', '', ''];
        $rows[] = ['closings', 'closing_date', 'bank_account|status|opening|cash_in|cash_out|expected|counted|variance|close_reason|reopen_reason'];
        foreach ($closings as $closing) {
            $rows[] = [
                'closings',
                (string) data_get($closing, 'closing_date', ''),
                implode('|', [
                    trim(implode(' ', array_filter([
                        (string) data_get($closing, 'cash_account_name', ''),
                        (string) data_get($closing, 'cash_account_number', ''),
                    ]))) ?: '-',
                    (string) data_get($closing, 'status', ''),
                    number_format((float) data_get($closing, 'opening_balance_mzn', 0), 2, '.', ''),
                    number_format((float) data_get($closing, 'cash_in_mzn', 0), 2, '.', ''),
                    number_format((float) data_get($closing, 'cash_out_mzn', 0), 2, '.', ''),
                    number_format((float) data_get($closing, 'expected_balance_mzn', 0), 2, '.', ''),
                    number_format((float) data_get($closing, 'counted_balance_mzn', 0), 2, '.', ''),
                    number_format((float) data_get($closing, 'variance_mzn', 0), 2, '.', ''),
                    (string) data_get($closing, 'close_reason', ''),
                    (string) data_get($closing, 'reopen_reason', ''),
                ]),
            ];
        }

        $csv = '';
        foreach ($rows as $row) {
            $csv .= '"' . str_replace('"', '""', $row[0] ?? '') . '","' .
                str_replace('"', '""', $row[1] ?? '') . '","' .
                str_replace('"', '""', $row[2] ?? '') . '"' . "\n";
        }

        $filename = sprintf(
            'mozambique-cash-closings-%s.csv',
            now()->format('Y-m-d_His')
        );

        $this->logFiscalExport(
            'cash_closings_csv',
            $fromDate !== '' ? $fromDate : null,
            $toDate !== '' ? $toDate : null,
            $filename,
            $csv,
            ['rows' => count($rows)]
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function closeCashClosing(Request $request)
    {
        if (!Auth::user()->can('manage-account-reports')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        if (!Schema::hasTable('mz_cash_closings')) {
            return response()->json([
                'message' => __('Cash closing table not found. Run database migrations first.'),
            ], 422);
        }

        $validated = $request->validate([
            'bank_account_id' => ['required', 'integer'],
            'closing_date' => ['required', 'date', 'before_or_equal:today'],
            'counted_balance_mzn' => ['required', 'numeric', 'min:0'],
            'close_reason' => ['nullable', 'string'],
        ]);

        $bankAccount = BankAccount::query()
            ->where('id', (int) $validated['bank_account_id'])
            ->where('created_by', creatorId())
            ->first();

        if (!$bankAccount) {
            return response()->json(['message' => __('Selected cash account was not found.')], 422);
        }

        try {
            $snapshot = $this->reportService->buildCashClosingSnapshot(
                (int) $bankAccount->id,
                (string) $validated['closing_date'],
                (float) $validated['counted_balance_mzn']
            );
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $hasExistingClosedRow = MozCashClosing::query()
            ->where('created_by', creatorId())
            ->where('bank_account_id', $bankAccount->id)
            ->whereDate('closing_date', $validated['closing_date'])
            ->where('status', 'closed')
            ->exists();

        if ($hasExistingClosedRow) {
            return response()->json([
                'message' => __('There is already a closed cash closing for this account and date.'),
            ], 422);
        }

        MozCashClosing::create([
            'bank_account_id' => $bankAccount->id,
            'closing_date' => $validated['closing_date'],
            'status' => 'closed',
            'opening_balance_mzn' => $snapshot['opening_balance_mzn'] ?? 0,
            'cash_in_mzn' => $snapshot['cash_in_mzn'] ?? 0,
            'cash_out_mzn' => $snapshot['cash_out_mzn'] ?? 0,
            'expected_balance_mzn' => $snapshot['expected_balance_mzn'] ?? 0,
            'counted_balance_mzn' => $snapshot['counted_balance_mzn'] ?? 0,
            'variance_mzn' => $snapshot['variance_mzn'] ?? 0,
            'close_reason' => $validated['close_reason'] ?? null,
            'snapshot' => $snapshot,
            'closed_by' => Auth::id(),
            'closed_at' => now(),
            'creator_id' => Auth::id(),
            'created_by' => creatorId(),
        ]);

        return response()->json([
            'message' => __('Cash closing completed successfully.'),
            'data' => $this->reportService->getCashClosings(),
        ]);
    }

    public function reopenCashClosing(Request $request, MozCashClosing $closing)
    {
        if (!Auth::user()->can('manage-account-reports')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        if (!Schema::hasTable('mz_cash_closings')) {
            return response()->json([
                'message' => __('Cash closing table not found. Run database migrations first.'),
            ], 422);
        }

        if ((int) $closing->created_by !== (int) creatorId()) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        if ($closing->status !== 'closed') {
            return response()->json(['message' => __('This cash closing is not in closed state.')], 422);
        }

        $validated = $request->validate([
            'reopen_reason' => ['nullable', 'string'],
        ]);

        $closing->update([
            'status' => 'reopened',
            'reopen_reason' => $validated['reopen_reason'] ?? null,
            'reopened_by' => Auth::id(),
            'reopened_at' => now(),
        ]);

        return response()->json([
            'message' => __('Cash closing reopened successfully.'),
            'data' => $this->reportService->getCashClosings(),
        ]);
    }

    public function customerBalance(Request $request)
    {
        if (!$this->canAccessReport(['view-customer-balance'])) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $filters = [
            'as_of_date' => $request->as_of_date ?: date('Y-m-d'),
            'show_zero_balances' => $request->show_zero_balances === 'true',
        ];

        $data = $this->rememberReportPayload(
            'customer-balance',
            $filters,
            fn () => $this->reportService->getCustomerBalanceSummary($filters),
            $request
        );
        return response()->json($data);
    }

    public function vendorBalance(Request $request)
    {
        if (!$this->canAccessReport(['view-vendor-balance'])) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $filters = [
            'as_of_date' => $request->as_of_date ?: date('Y-m-d'),
            'show_zero_balances' => $request->show_zero_balances === 'true',
        ];

        $data = $this->rememberReportPayload(
            'vendor-balance',
            $filters,
            fn () => $this->reportService->getVendorBalanceSummary($filters),
            $request
        );
        return response()->json($data);
    }

    public function printInvoiceAging(Request $request)
    {
        if ($this->canAccessReport(['print-invoice-aging'])) {
            $filters = ['as_of_date' => $request->as_of_date ?: date('Y-m-d')];
            $data = $this->rememberReportPayload(
                'invoice-aging',
                $filters,
                fn () => $this->reportService->getInvoiceAging($filters),
                $request
            );
            return Inertia::render('Account/Reports/Print/InvoiceAging', ['data' => $data, 'filters' => $filters]);
        }
        else
        {
             return back()->with('error', __('Permission denied'));
        }
    }

    public function printBillAging(Request $request)
    {
        if ($this->canAccessReport(['print-bill-aging'])) {
            $filters = ['as_of_date' => $request->as_of_date ?: date('Y-m-d')];
            $data = $this->rememberReportPayload(
                'bill-aging',
                $filters,
                fn () => $this->reportService->getBillAging($filters),
                $request
            );
            return Inertia::render('Account/Reports/Print/BillAging', ['data' => $data, 'filters' => $filters]);
        }
        else
        {
             return back()->with('error', __('Permission denied'));
        }
    }

    public function printTaxSummary(Request $request)
    {
        if ($this->canAccessReport(['print-tax-summary'])) {
            $filters = $this->resolveDateFilters($request);
            $data = $this->rememberReportPayload(
                'tax-summary',
                $filters,
                fn () => $this->reportService->getTaxSummary($filters),
                $request
            );

            return Inertia::render('Account/Reports/Print/TaxSummary', ['data' => $data, 'filters' => $filters]);
        }
        else
        {
                return back()->with('error', __('Permission denied'));
        }
    }

    public function printCustomerBalance(Request $request)
    {
        if ($this->canAccessReport(['print-customer-balance'])) {
            $filters = [
                'as_of_date' => $request->as_of_date ?: date('Y-m-d'),
                'show_zero_balances' => $request->show_zero_balances === 'true',
                ];
            $data = $this->rememberReportPayload(
                'customer-balance',
                $filters,
                fn () => $this->reportService->getCustomerBalanceSummary($filters),
                $request
            );
            return Inertia::render('Account/Reports/Print/CustomerBalance', ['data' => $data, 'filters' => $filters]);
        }
        else{
                return back()->with('error', __('Permission denied'));
        }
    }

    public function printVendorBalance(Request $request)
    {
        if ($this->canAccessReport(['print-vendor-balance'])) {
            $filters = [
                'as_of_date' => $request->as_of_date ?: date('Y-m-d'),
                'show_zero_balances' => $request->show_zero_balances === 'true',
            ];
            $data = $this->rememberReportPayload(
                'vendor-balance',
                $filters,
                fn () => $this->reportService->getVendorBalanceSummary($filters),
                $request
            );
            return Inertia::render('Account/Reports/Print/VendorBalance', ['data' => $data, 'filters' => $filters]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function customerDetail($customerId, Request $request)
    {
        if ($this->canAccessReport(['view-customer-detail-report'])) {
            $filters = [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ];

            $data = $this->rememberReportPayload(
                "customer-detail:{$customerId}",
                $filters,
                fn () => $this->reportService->getCustomerDetail($customerId, $filters),
                $request
            );

            if (!$data) {
                return back()->with('error', __('Customer not found'));
            }

            return Inertia::render('Account/Reports/CustomerDetail', [
                'customerData' => $data,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function vendorDetail($vendorId, Request $request)
    {
        if ($this->canAccessReport(['view-vendor-detail-report'])) {
            $filters = [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ];

            $data = $this->rememberReportPayload(
                "vendor-detail:{$vendorId}",
                $filters,
                fn () => $this->reportService->getVendorDetail($vendorId, $filters),
                $request
            );

            if (!$data) {
                return back()->with('error', __('Vendor not found'));
            }

            return Inertia::render('Account/Reports/VendorDetail', [
                'vendorData' => $data,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function printCustomerDetail($customerId, Request $request)
    {
        if ($this->canAccessReport(['print-customer-detail-report'])) {
            $filters = [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ];

            $data = $this->rememberReportPayload(
                "customer-detail:{$customerId}",
                $filters,
                fn () => $this->reportService->getCustomerDetail($customerId, $filters),
                $request
            );

            if (!$data) {
                return back()->with('error', __('Customer not found'));
            }

            return Inertia::render('Account/Reports/Print/CustomerDetail', [
                'data' => $data,
                'filters' => $filters,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function printVendorDetail($vendorId, Request $request)
    {
        if ($this->canAccessReport(['print-vendor-detail-report'])) {
            $filters = [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ];

            $data = $this->rememberReportPayload(
                "vendor-detail:{$vendorId}",
                $filters,
                fn () => $this->reportService->getVendorDetail($vendorId, $filters),
                $request
            );

            if (!$data) {
                return back()->with('error', __('Vendor not found'));
            }

            return Inertia::render('Account/Reports/Print/VendorDetail', [
                'data' => $data,
                'filters' => $filters,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    private function rememberReportPayload(
        string $scope,
        array $filters,
        callable $resolver,
        ?Request $request = null
    ) {
        if ($this->shouldBypassReportCache($request)) {
            return $resolver();
        }

        $cacheKey = $this->buildReportCacheKey($scope, $filters);
        return Cache::remember(
            $cacheKey,
            now()->addSeconds($this->reportCacheTtlSeconds()),
            static fn () => $resolver()
        );
    }

    private function buildReportCacheKey(string $scope, array $filters): string
    {
        $companyId = (int) creatorId();
        $cacheVersion = AccountCacheService::currentVersion($companyId);

        return sprintf(
            'account:report:v1:cv%d:%d:%s:%s',
            $cacheVersion,
            $companyId,
            $scope,
            md5(json_encode($filters))
        );
    }

    private function reportCacheTtlSeconds(): int
    {
        return max(30, (int) config('performance.report_cache_ttl_seconds', 120));
    }

    private function shouldBypassReportCache(?Request $request): bool
    {
        return $request?->boolean('refresh', false) ?? false;
    }

    /**
     * @param array<int, string> $permissions
     */
    private function canAccessReport(array $permissions, bool $allowManageReports = true): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($allowManageReports && $user->can('manage-account-reports')) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    private function canAccessReportsIndex(): bool
    {
        return $this->canAccessReport([
            'view-invoice-aging',
            'view-bill-aging',
            'view-tax-summary',
            'view-customer-balance',
            'view-vendor-balance',
        ]);
    }

    private function resolveDateFilters(Request $request): array
    {
        $currentYear = date('Y');

        return [
            'from_date' => $request->from_date ?: "$currentYear-01-01",
            'to_date' => $request->to_date ?: "$currentYear-12-31",
        ];
    }

    private function resolveGifimThresholdCategoryForPayment(string $paymentMethod, float $amountMzn): ?string
    {
        $paymentMethod = strtolower(trim($paymentMethod));
        $cashThreshold = (float) config('sce.gifim.cash_threshold_mzn', 250000);
        $electronicThreshold = (float) config('sce.gifim.electronic_threshold_mzn', 750000);
        $electronicMethods = (array) config('sce.gifim.electronic_payment_methods', ['bank_transfer', 'cheque', 'card', 'mobile_money', 'other']);

        if ($paymentMethod === 'cash' && $amountMzn >= $cashThreshold) {
            return 'cash_threshold';
        }

        if (in_array($paymentMethod, $electronicMethods, true) && $amountMzn >= $electronicThreshold) {
            return 'electronic_threshold';
        }

        return null;
    }

    /**
     * @param \Workdo\Account\Models\VendorPayment|\Workdo\Account\Models\CustomerPayment $payment
     * @return array<int, string>
     */
    private function resolveExchangeDossierRequirements(string $direction, $payment): array
    {
        $direction = strtolower(trim($direction));
        $currencyCode = strtoupper((string) ($payment->currency_code ?? 'MZN'));
        $isInternational = $direction === 'outbound'
            ? ((bool) ($payment->is_international_payment ?? false) || $currencyCode !== 'MZN')
            : ((bool) ($payment->is_export_receipt ?? false) || $currencyCode !== 'MZN');

        if (!$isInternational) {
            return [];
        }

        $required = [
            'contract_reference',
            'invoice_reference',
            'bank_settlement_reference',
        ];

        if ($direction === 'inbound' && (bool) ($payment->is_export_receipt ?? false)) {
            $required[] = 'transport_document_reference';
            $required[] = 'customs_declaration_reference';
        }

        if ($direction === 'outbound') {
            $required[] = 'fx_authorization_reference';
            $withholdingTreatment = strtolower((string) ($payment->withholding_tax_treatment ?? ''));
            if (in_array($withholdingTreatment, ['withheld', 'adt_reduced'], true)) {
                $required[] = 'withholding_receipt_reference';
            }
        }

        return array_values(array_unique($required));
    }

    private function logFiscalExport(
        string $exportType,
        ?string $fromDate,
        ?string $toDate,
        ?string $fileName,
        string $content,
        array $metadata = []
    ): void {
        if (!Schema::hasTable('fiscal_export_histories')) {
            return;
        }

        $filePath = null;
        if (!empty($fileName)) {
            $safeFileName = basename($fileName);
            $filePath = sprintf(
                'fiscal-exports/%d/%s/%s-%s-%s',
                creatorId(),
                $exportType,
                now()->format('YmdHis'),
                substr(hash('sha256', $content), 0, 12),
                $safeFileName
            );

            Storage::disk('local')->put($filePath, $content);
        }

        FiscalExportHistory::query()->create([
            'company_id' => creatorId(),
            'export_type' => $exportType,
            'period_start' => $fromDate,
            'period_end' => $toDate,
            'generated_by' => Auth::id(),
            'file_name' => $fileName,
            'file_hash' => hash('sha256', $content),
            'file_path' => $filePath,
            'status' => 'generated',
            'metadata' => $metadata,
        ]);
    }

}
