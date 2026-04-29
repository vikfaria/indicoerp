<?php

namespace Workdo\Account\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Workdo\Account\Models\MozFiscalClosing;
use Workdo\Account\Models\MozPilotCompany;
use Workdo\Account\Models\MozPilotValidationCase;
use Workdo\Account\Services\ReportService;

class ReportsController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        if(Auth::user()->can('manage-account-reports')){
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
        $filters = [
            'as_of_date' => $request->as_of_date ?: date('Y-m-d'),
        ];

        $data = $this->reportService->getInvoiceAging($filters);
        return response()->json($data);
    }

    public function billAging(Request $request)
    {
        $filters = [
            'as_of_date' => $request->as_of_date ?: date('Y-m-d'),
        ];

        $data = $this->reportService->getBillAging($filters);
        return response()->json($data);
    }

    public function taxSummary(Request $request)
    {
        $currentYear = date('Y');
        $filters = [
            'from_date' => $request->from_date ?: "$currentYear-01-01",
            'to_date' => $request->to_date ?: "$currentYear-12-31",
        ];

        $data = $this->reportService->getTaxSummary($filters);
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

        $data = $this->reportService->getMozambiqueFiscalMap($filters);
        return response()->json($data);
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

        $data = $this->reportService->getMozambiqueFiscalMap($filters);

        $rows = [
            ['section', 'metric', 'value'],
            ['period', 'from_date', $data['from_date']],
            ['period', 'to_date', $data['to_date']],
            ['sales', 'documents', (string) $data['sales']['documents']],
            ['sales', 'taxable_base', number_format((float) $data['sales']['taxable_base'], 2, '.', '')],
            ['sales', 'tax_amount', number_format((float) $data['sales']['tax_amount'], 2, '.', '')],
            ['sales', 'total_amount', number_format((float) $data['sales']['total_amount'], 2, '.', '')],
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
        ]);
    }

    public function mozambiqueVatDeclaration(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $currentYear = date('Y');
        $filters = [
            'from_date' => $request->from_date ?: "$currentYear-01-01",
            'to_date' => $request->to_date ?: "$currentYear-12-31",
        ];

        $data = $this->reportService->getMozambiqueVatDeclaration($filters);
        return response()->json($data);
    }

    public function exportMozambiqueVatDeclaration(Request $request)
    {
        if (!Auth::user()->can('view-tax-summary')) {
            return back()->with('error', __('Permission denied'));
        }

        $currentYear = date('Y');
        $filters = [
            'from_date' => $request->from_date ?: "$currentYear-01-01",
            'to_date' => $request->to_date ?: "$currentYear-12-31",
        ];

        $data = $this->reportService->getMozambiqueVatDeclaration($filters);

        $rows = [
            ['section', 'metric', 'value'],
            ['period', 'from_date', $data['from_date']],
            ['period', 'to_date', $data['to_date']],
            ['totals', 'sales_vat', number_format((float) $data['totals']['sales_vat'], 2, '.', '')],
            ['totals', 'purchase_vat', number_format((float) $data['totals']['purchase_vat'], 2, '.', '')],
            ['totals', 'credit_notes_vat', number_format((float) $data['totals']['credit_notes_vat'], 2, '.', '')],
            ['totals', 'debit_notes_vat', number_format((float) $data['totals']['debit_notes_vat'], 2, '.', '')],
            ['totals', 'output_vat', number_format((float) $data['totals']['output_vat'], 2, '.', '')],
            ['totals', 'input_vat', number_format((float) $data['totals']['input_vat'], 2, '.', '')],
            ['totals', 'net_vat_payable', number_format((float) $data['totals']['net_vat_payable'], 2, '.', '')],
            ['', '', ''],
            ['monthly', 'period', 'sales_vat|purchase_vat|credit_notes_vat|debit_notes_vat|output_vat|input_vat|net_vat_payable'],
        ];

        foreach ($data['monthly'] as $month) {
            $rows[] = [
                'monthly',
                (string) $month['period'],
                implode('|', [
                    number_format((float) $month['sales_vat'], 2, '.', ''),
                    number_format((float) $month['purchase_vat'], 2, '.', ''),
                    number_format((float) $month['credit_notes_vat'], 2, '.', ''),
                    number_format((float) $month['debit_notes_vat'], 2, '.', ''),
                    number_format((float) $month['output_vat'], 2, '.', ''),
                    number_format((float) $month['input_vat'], 2, '.', ''),
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

        $data = $this->reportService->getMozambiqueFiscalSubmissionRegister($filters);
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

        $data = $this->reportService->getMozambiqueFiscalSubmissionRegister($filters);

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

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
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
            'go_live_approved' => ['nullable', 'in:on,off'],
            'go_live_approved_at' => ['nullable', 'date'],
            'go_live_approval_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $settingMap = [
            'legal_review_status' => 'mz_go_live_legal_review_status',
            'legal_reviewed_at' => 'mz_go_live_legal_reviewed_at',
            'legal_notes' => 'mz_go_live_legal_notes',
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
            'go_live_approved' => 'mz_go_live_formal_approval',
            'go_live_approved_at' => 'mz_go_live_formal_approval_at',
            'go_live_approval_notes' => 'mz_go_live_formal_approval_notes',
        ];

        foreach ($settingMap as $payloadKey => $settingKey) {
            if (array_key_exists($payloadKey, $validated)) {
                setSetting($settingKey, $validated[$payloadKey] ?? '', creatorId());
            }
        }

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

    public function customerBalance(Request $request)
    {
        $filters = [
            'as_of_date' => $request->as_of_date ?: date('Y-m-d'),
            'show_zero_balances' => $request->show_zero_balances === 'true',
        ];

        $data = $this->reportService->getCustomerBalanceSummary($filters);
        return response()->json($data);
    }

    public function vendorBalance(Request $request)
    {
        $filters = [
            'as_of_date' => $request->as_of_date ?: date('Y-m-d'),
            'show_zero_balances' => $request->show_zero_balances === 'true',
        ];

        $data = $this->reportService->getVendorBalanceSummary($filters);
        return response()->json($data);
    }

    public function printInvoiceAging(Request $request)
    {
        if(Auth::user()->can('print-invoice-aging')){
            $filters = ['as_of_date' => $request->as_of_date ?: date('Y-m-d')];
            $data = $this->reportService->getInvoiceAging($filters);
            return Inertia::render('Account/Reports/Print/InvoiceAging', ['data' => $data, 'filters' => $filters]);
        }
        else
        {
             return back()->with('error', __('Permission denied'));
        }
    }

    public function printBillAging(Request $request)
    {
        if(Auth::user()->can('print-bill-aging')){
            $filters = ['as_of_date' => $request->as_of_date ?: date('Y-m-d')];
            $data = $this->reportService->getBillAging($filters);
            return Inertia::render('Account/Reports/Print/BillAging', ['data' => $data, 'filters' => $filters]);
        }
        else
        {
             return back()->with('error', __('Permission denied'));
        }
    }

    public function printTaxSummary(Request $request)
    {
        if(Auth::user()->can('print-tax-summary')){
             $currentYear = date('Y');
            $filters = [
                'from_date' => $request->from_date ?: "$currentYear-01-01",
                'to_date' => $request->to_date ?: "$currentYear-12-31",
            ];
            $data = $this->reportService->getTaxSummary($filters);
            return Inertia::render('Account/Reports/Print/TaxSummary', ['data' => $data, 'filters' => $filters]);
        }
        else
        {
                return back()->with('error', __('Permission denied'));
        }
    }

    public function printCustomerBalance(Request $request)
    {
        if(Auth::user()->can('print-customer-balance')){
            $filters = [
                'as_of_date' => $request->as_of_date ?: date('Y-m-d'),
                'show_zero_balances' => $request->show_zero_balances === 'true',
                ];
            $data = $this->reportService->getCustomerBalanceSummary($filters);
            return Inertia::render('Account/Reports/Print/CustomerBalance', ['data' => $data, 'filters' => $filters]);
        }
        else{
                return back()->with('error', __('Permission denied'));
        }
    }

    public function printVendorBalance(Request $request)
    {
        if(Auth::user()->can('print-vendor-balance')){
            $filters = [
                'as_of_date' => $request->as_of_date ?: date('Y-m-d'),
                'show_zero_balances' => $request->show_zero_balances === 'true',
            ];
            $data = $this->reportService->getVendorBalanceSummary($filters);
            return Inertia::render('Account/Reports/Print/VendorBalance', ['data' => $data, 'filters' => $filters]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function customerDetail($customerId, Request $request)
    {
        if(Auth::user()->can('view-customer-detail-report')){
            $filters = [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ];

            $data = $this->reportService->getCustomerDetail($customerId, $filters);

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
        if(Auth::user()->can('view-vendor-detail-report')){
            $filters = [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ];

            $data = $this->reportService->getVendorDetail($vendorId, $filters);

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
        if(Auth::user()->can('print-customer-detail-report')){
            $filters = [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ];

            $data = $this->reportService->getCustomerDetail($customerId, $filters);

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
        if(Auth::user()->can('print-vendor-detail-report')){
            $filters = [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ];

            $data = $this->reportService->getVendorDetail($vendorId, $filters);

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
}
