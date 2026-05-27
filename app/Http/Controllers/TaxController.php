<?php

namespace App\Http\Controllers;

use App\Models\MzVatCode;
use App\Models\IrpcConfiguration;
use App\Models\TaxAdjustment;
use App\Models\WithholdingTaxRule;
use App\Models\WithholdingTaxTransaction;
use App\Services\FiscalDeclarationService;
use App\Services\FiscalValidationService;
use App\Services\VatCalculationService;
use App\Services\IrpcCalculationService;
use App\Services\WithholdingTaxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TaxController extends Controller
{
    public function __construct(
        private readonly FiscalDeclarationService $fiscalDeclarationService,
        private readonly FiscalValidationService $fiscalValidationService,
    ) {}

    // VAT Map
    public function vatMap(Request $request)
    {
        if ($this->cannotViewTaxSummary()) {
            abort(403, __('Permission denied'));
        }

        $year = $request->get('year', date('Y'));
        $month = $request->get('month', (int) date('m'));
        $startDate = sprintf('%s-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $vatCodes = MzVatCode::where('is_active', true)->get();

        $vatResult = null;
        if ($request->has('calculate')) {
            $service = app(VatCalculationService::class);
            $vatResult = $service->calculatePeriodVat(creatorId(), $startDate, $endDate);
        }

        return Inertia::render('Tax/VatMap/Index', [
            'vatCodes' => $vatCodes,
            'vatResult' => $vatResult,
            'year' => (int) $year,
            'month' => (int) $month,
        ]);
    }

    // IRPC Dashboard
    public function irpcDashboard(Request $request)
    {
        if ($this->cannotViewTaxSummary()) {
            abort(403, __('Permission denied'));
        }

        $year = $request->get('year', date('Y'));

        $config = IrpcConfiguration::firstOrNew(
            ['company_id' => creatorId(), 'fiscal_year' => $year],
            ['standard_rate' => 32.00, 'regime' => 'normal', 'payment_on_account_rate' => 80.00]
        );

        $adjustments = TaxAdjustment::where('company_id', creatorId())
            ->where('fiscal_year', $year)
            ->orderBy('type')
            ->get();

        $irpcResult = null;
        if ($request->has('calculate')) {
            $service = app(IrpcCalculationService::class);
            $irpcResult = $service->calculate(creatorId(), $year);
        }

        $categories = (new IrpcCalculationService())->getAdjustmentCategories();

        return Inertia::render('Tax/Irpc/Index', [
            'config' => $config,
            'adjustments' => $adjustments,
            'irpcResult' => $irpcResult,
            'categories' => $categories,
            'year' => $year,
        ]);
    }

    public function updateIrpcConfig(Request $request)
    {
        if ($this->cannotManageTaxReports()) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'fiscal_year' => 'required|string|size:4',
            'standard_rate' => 'required|numeric|min:0|max:100',
            'reduced_rate' => 'nullable|numeric|min:0|max:100',
            'regime' => 'required|string|in:normal,simplified,free_zone,agriculture',
            'payment_on_account_rate' => 'required|numeric|min:0|max:100',
        ]);

        IrpcConfiguration::updateOrCreate(
            ['company_id' => creatorId(), 'fiscal_year' => $validated['fiscal_year']],
            array_merge($validated, ['created_by' => Auth::id()])
        );

        return back()->with('success', __('Configuração IRPC actualizada.'));
    }

    public function storeAdjustment(Request $request)
    {
        if ($this->cannotManageTaxReports()) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'fiscal_year' => 'required|string|size:4',
            'type' => 'required|string|in:add_back,deduction',
            'category' => 'required|string|max:50',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'legal_basis' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $validated['company_id'] = creatorId();
        $validated['created_by'] = Auth::id();
        TaxAdjustment::create($validated);

        return back()->with('success', __('Correcção fiscal registada.'));
    }

    public function destroyAdjustment(TaxAdjustment $adjustment)
    {
        if ($this->cannotManageTaxReports()) {
            return back()->with('error', __('Permission denied'));
        }

        if ($adjustment->company_id !== creatorId()) {
            abort(403, __('Permission denied'));
        }

        $adjustment->delete();
        return back()->with('success', __('Correcção fiscal eliminada.'));
    }

    // Withholding Tax
    public function withholdingIndex(Request $request)
    {
        if ($this->cannotViewTaxSummary()) {
            abort(403, __('Permission denied'));
        }

        $year = $request->get('year', date('Y'));
        $month = $request->get('month', (int) date('m'));

        $rules = WithholdingTaxRule::where('is_active', true)->get();

        $transactions = WithholdingTaxTransaction::where('company_id', creatorId())
            ->where('fiscal_year', $year)
            ->when($request->month, fn($q) => $q->where('fiscal_month', $month))
            ->with('rule')
            ->orderByDesc('transaction_date')
            ->paginate(20);
        $transactions->through(function (WithholdingTaxTransaction $transaction) {
            return [
                ...$transaction->toArray(),
                'tax_withheld' => (float) $transaction->withholding_amount,
            ];
        });

        return Inertia::render('Tax/Withholding/Index', [
            'rules' => $rules,
            'transactions' => $transactions,
            'year' => $year,
            'month' => (int) $month,
        ]);
    }

    public function withholdingStore(Request $request)
    {
        if ($this->cannotManageTaxReports()) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'rule_code' => 'required|string|exists:withholding_tax_rules,code',
            'gross_amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'vendor_name' => 'nullable|string|max:255',
            'vendor_nuit' => 'nullable|string|size:9',
            'document_reference' => 'nullable|string|max:50',
        ]);

        $service = app(WithholdingTaxService::class);

        try {
            if ($this->isMozambiqueFiscalContext() && empty($validated['vendor_nuit'])) {
                return back()->withErrors([
                    'vendor_nuit' => __('NUIT do fornecedor é obrigatório para retenções na fonte no contexto fiscal de Moçambique.'),
                ]);
            }

            if (!empty($validated['vendor_nuit'])) {
                $this->fiscalValidationService->requireValidNuit($validated['vendor_nuit'], 'vendor_nuit');
            }

            $service->recordWithholding(
                creatorId(),
                $validated['rule_code'],
                $validated['gross_amount'],
                $validated['transaction_date'],
                null,
                $validated['vendor_nuit'] ?? null,
                $validated['vendor_name'] ?? null,
                $validated['document_reference'] ?? null
            );
        } catch (\RuntimeException | \Illuminate\Validation\ValidationException $e) {
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return back()->withErrors($e->errors());
            }
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Retenção na fonte registada.'));
    }

    public function withholdingDeclaration(Request $request)
    {
        if ($this->cannotViewTaxSummary()) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $year = (string) $request->get('year', date('Y'));
        $month = (int) $request->get('month', (int) date('m'));

        $data = $this->fiscalDeclarationService->getWithholdingDeclaration(creatorId(), $year, $month);

        return response()->json($data);
    }

    public function withholdingDeclarationPage(Request $request)
    {
        if ($this->cannotViewTaxSummary()) {
            abort(403, __('Permission denied'));
        }

        $year = (string) $request->get('year', date('Y'));
        $month = (int) $request->get('month', (int) date('m'));
        $data = $this->fiscalDeclarationService->getWithholdingDeclaration(creatorId(), $year, $month);

        return Inertia::render('Tax/WithholdingDeclaration/Index', [
            'year' => (int) $year,
            'month' => $month,
            'declaration' => $data,
        ]);
    }

    public function exportWithholdingDeclaration(Request $request)
    {
        if ($this->cannotViewTaxSummary()) {
            return back()->with('error', __('Permission denied'));
        }

        $year = (string) $request->get('year', date('Y'));
        $month = (int) $request->get('month', (int) date('m'));
        $data = $this->fiscalDeclarationService->getWithholdingDeclaration(creatorId(), $year, $month);

        $rows = [
            ['section', 'metric', 'value'],
            ['period', 'year', $year],
            ['period', 'month', str_pad((string) $month, 2, '0', STR_PAD_LEFT)],
            ['period', 'due_date', (string) $data['due_date']],
            ['period', 'payment_reference', (string) $data['payment_reference']],
            ['totals', 'gross', number_format((float) data_get($data, 'totals.gross', 0), 2, '.', '')],
            ['totals', 'withholding', number_format((float) data_get($data, 'totals.withholding', 0), 2, '.', '')],
            ['totals', 'net', number_format((float) data_get($data, 'totals.net', 0), 2, '.', '')],
            ['', '', ''],
            ['rule', 'rate', 'gross|withholding|net'],
        ];

        foreach (data_get($data, 'summary', []) as $line) {
            $rows[] = [
                (string) data_get($line, 'rule_code', ''),
                number_format((float) data_get($line, 'rate', 0), 2, '.', '') . '%',
                implode('|', [
                    number_format((float) data_get($line, 'total_gross', 0), 2, '.', ''),
                    number_format((float) data_get($line, 'total_withholding', 0), 2, '.', ''),
                    number_format((float) data_get($line, 'total_net', 0), 2, '.', ''),
                ]),
            ];
        }

        $csv = $this->fiscalDeclarationService->toCsv($rows);
        $filename = sprintf('withholding-declaration-%s-%02d.csv', $year, $month);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function irpcGuide(Request $request)
    {
        if ($this->cannotViewTaxSummary()) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $year = (string) $request->get('year', date('Y'));
        $service = app(IrpcCalculationService::class);

        return response()->json($service->calculate(creatorId(), $year));
    }

    public function model20Support(Request $request)
    {
        if ($this->cannotViewTaxSummary()) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $year = (string) $request->get('year', date('Y'));

        return response()->json(
            $this->fiscalDeclarationService->getModel20Support(creatorId(), $year)
        );
    }

    public function model20SupportPage(Request $request)
    {
        if ($this->cannotViewTaxSummary()) {
            abort(403, __('Permission denied'));
        }

        $year = (string) $request->get('year', date('Y'));
        $support = $this->fiscalDeclarationService->getModel20Support(creatorId(), $year);

        return Inertia::render('Tax/Modelo20/Index', [
            'year' => (int) $year,
            'support' => $support,
        ]);
    }

    public function exportModel20Support(Request $request)
    {
        if ($this->cannotViewTaxSummary()) {
            return back()->with('error', __('Permission denied'));
        }

        $year = (string) $request->get('year', date('Y'));
        $data = $this->fiscalDeclarationService->getModel20Support(creatorId(), $year);

        $rows = [
            ['model20_line', 'debit_total', 'credit_total', 'net_total', 'movements'],
        ];

        foreach ($data['lines'] as $line) {
            $rows[] = [
                (string) $line['model20_line'],
                number_format((float) $line['debit_total'], 2, '.', ''),
                number_format((float) $line['credit_total'], 2, '.', ''),
                number_format((float) $line['net_total'], 2, '.', ''),
                (string) $line['movements'],
            ];
        }

        $rows[] = ['', '', '', '', ''];
        $rows[] = ['UNMAPPED_ACCOUNT', 'debit_total', 'credit_total', '', 'movements'];

        foreach ($data['unmapped_accounts'] as $unmapped) {
            $rows[] = [
                (string) ($unmapped['account_code'] . ' - ' . $unmapped['account_name']),
                number_format((float) $unmapped['debit_total'], 2, '.', ''),
                number_format((float) $unmapped['credit_total'], 2, '.', ''),
                '',
                (string) $unmapped['movements'],
            ];
        }

        $csv = $this->fiscalDeclarationService->toCsv($rows);
        $filename = sprintf('modelo20-support-%s.csv', $year);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function annualDeclaration(Request $request)
    {
        if ($this->cannotViewTaxSummary()) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $year = (string) $request->get('year', date('Y'));

        return response()->json(
            $this->fiscalDeclarationService->getAnnualFiscalDeclaration(creatorId(), $year)
        );
    }

    public function annualDeclarationPage(Request $request)
    {
        if ($this->cannotViewTaxSummary()) {
            abort(403, __('Permission denied'));
        }

        $year = (string) $request->get('year', date('Y'));
        $declaration = $this->fiscalDeclarationService->getAnnualFiscalDeclaration(creatorId(), $year);

        return Inertia::render('Tax/AnnualDeclaration/Index', [
            'year' => (int) $year,
            'declaration' => $declaration,
        ]);
    }

    private function isMozambiqueFiscalContext(): bool
    {
        $taxType = strtoupper((string) company_setting('tax_type', creatorId()));
        if ($taxType === 'NUIT') {
            return true;
        }

        $country = strtolower((string) company_setting('company_country', creatorId()));

        return str_contains($country, 'mozambique') || str_contains($country, 'moçambique');
    }

    private function cannotViewTaxSummary(): bool
    {
        return !Auth::user()->can('view-tax-summary')
            && !Auth::user()->can('manage-account-reports');
    }

    private function cannotManageTaxReports(): bool
    {
        return !Auth::user()->can('manage-account-reports');
    }
}
