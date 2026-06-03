<?php

namespace App\Http\Controllers;

use App\Models\MzVatCode;
use App\Models\FiscalExportHistory;
use App\Models\IrpcConfiguration;
use App\Models\TaxAdjustment;
use App\Models\WithholdingTaxTreatyRate;
use App\Models\WithholdingTaxRule;
use App\Models\WithholdingTaxTransaction;
use App\Services\FiscalDeclarationService;
use App\Services\FiscalValidationService;
use App\Services\VatCalculationService;
use App\Services\IrpcCalculationService;
use App\Services\WithholdingTaxService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
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

    public function exportVatMap(Request $request)
    {
        if ($this->cannotViewTaxSummary()) {
            return back()->with('error', __('Permission denied'));
        }

        $year = (string) $request->get('year', date('Y'));
        $month = max(1, min(12, (int) $request->get('month', (int) date('m'))));
        $startDate = sprintf('%s-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $data = app(VatCalculationService::class)->calculatePeriodVat(creatorId(), $startDate, $endDate);
        $rows = [
            ['section', 'metric', 'value'],
            ['period', 'year', $year],
            ['period', 'month', str_pad((string) $month, 2, '0', STR_PAD_LEFT)],
            ['period', 'start_date', $startDate],
            ['period', 'end_date', $endDate],
            ['', '', ''],
            ['totals', 'output_vat', number_format((float) data_get($data, 'output_vat', 0), 2, '.', '')],
            ['totals', 'supported_vat', number_format((float) data_get($data, 'supported_vat', 0), 2, '.', '')],
            ['totals', 'deductible_vat', number_format((float) data_get($data, 'deductible_vat', 0), 2, '.', '')],
            ['totals', 'non_deductible_vat', number_format((float) data_get($data, 'non_deductible_vat', 0), 2, '.', '')],
            ['totals', 'regularizations', number_format((float) data_get($data, 'regularizations', 0), 2, '.', '')],
            ['totals', 'vat_payable', number_format((float) data_get($data, 'vat_payable', 0), 2, '.', '')],
            ['totals', 'vat_recoverable', number_format((float) data_get($data, 'vat_recoverable', 0), 2, '.', '')],
            ['totals', 'net_position', number_format((float) data_get($data, 'net_position', 0), 2, '.', '')],
            ['', '', ''],
            ['status', 'label', data_get($data, 'net_position', 0) >= 0 ? __('IVA a pagar') : __('IVA a recuperar')],
        ];

        $csv = $this->fiscalDeclarationService->toCsv($rows);
        $filename = sprintf('vat-map-%s-%02d.csv', $year, $month);

        $this->recordFiscalExportHistory(
            exportType: 'vat_map_csv',
            fileName: $filename,
            fileContent: $csv,
            periodStart: $startDate,
            periodEnd: $endDate,
            metadata: [
                'content_type' => 'text/csv',
                'source' => 'sce.tax.vat-map.export',
                'fiscal_year' => $year,
                'fiscal_month' => $month,
                'csv_size_bytes' => strlen($csv),
                'generated_at' => now()->toIso8601String(),
                'net_position' => (float) data_get($data, 'net_position', 0),
            ]
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
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
        $filters = $this->parseWithholdingFilters($request);

        $rules = WithholdingTaxRule::where('is_active', true)->get()->map(fn (WithholdingTaxRule $rule) => [
            'id' => $rule->id,
            'code' => $rule->code,
            'name' => $rule->name,
            'description' => $rule->name,
            'income_type' => $rule->income_type,
            'rate' => (float) $rule->rate,
        ])->values();

        $transactionsQuery = WithholdingTaxTransaction::query()
            ->where('company_id', creatorId())
            ->where('fiscal_year', $year)
            ->when($request->month, fn($q) => $q->where('fiscal_month', $month))
            ->with('rule');

        if ($filters['vendor_nuit'] !== null) {
            $transactionsQuery->where('vendor_nuit', 'like', '%' . $filters['vendor_nuit'] . '%');
        }

        if ($filters['status'] !== null) {
            $transactionsQuery->where('status', $filters['status']);
        }

        if ($filters['income_type'] !== null) {
            $transactionsQuery->where(function ($incomeQuery) use ($filters): void {
                $incomeQuery->where('income_type_snapshot', $filters['income_type'])
                    ->orWhereHas('rule', function ($ruleQuery) use ($filters): void {
                        $ruleQuery->where('income_type', $filters['income_type']);
                    });
            });
        }

        $transactions = $transactionsQuery
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(20);
        $transactions->through(function (WithholdingTaxTransaction $transaction) {
            return [
                ...$transaction->toArray(),
                'tax_withheld' => (float) $transaction->withholding_amount,
                'income_type' => $transaction->income_type_snapshot ?: optional($transaction->rule)->income_type,
            ];
        });

        return Inertia::render('Tax/Withholding/Index', [
            'rules' => $rules,
            'transactions' => $transactions,
            'year' => $year,
            'month' => (int) $month,
            'filters' => $filters,
            'incomeTypes' => $rules->pluck('income_type')->filter()->unique()->values(),
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
        $filters = $this->parseWithholdingFilters($request);

        $data = $this->fiscalDeclarationService->getWithholdingDeclaration(creatorId(), $year, $month, $filters);

        return response()->json($data);
    }

    public function withholdingDeclarationPage(Request $request)
    {
        if ($this->cannotViewTaxSummary()) {
            abort(403, __('Permission denied'));
        }

        $year = (string) $request->get('year', date('Y'));
        $month = (int) $request->get('month', (int) date('m'));
        $filters = $this->parseWithholdingFilters($request);
        $incomeTypes = WithholdingTaxRule::query()
            ->where('is_active', true)
            ->pluck('income_type')
            ->filter()
            ->unique()
            ->values();
        $data = $this->fiscalDeclarationService->getWithholdingDeclaration(creatorId(), $year, $month, $filters);

        return Inertia::render('Tax/WithholdingDeclaration/Index', [
            'year' => (int) $year,
            'month' => $month,
            'declaration' => $data,
            'filters' => $filters,
            'incomeTypes' => $incomeTypes,
            'canManageTaxReports' => !$this->cannotManageTaxReports(),
        ]);
    }

    public function withholdingTreatyRates(Request $request)
    {
        if ($this->cannotViewTaxSummary()) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $validated = $request->validate([
            'country' => 'nullable|string|max:120',
            'income_type' => 'nullable|string|max:60',
            'is_active' => 'nullable|boolean',
            'as_of_date' => 'nullable|date',
        ]);

        $asOfDate = isset($validated['as_of_date'])
            ? Carbon::parse((string) $validated['as_of_date'])->startOfDay()
            : null;

        $query = WithholdingTaxTreatyRate::query()
            ->where('created_by', creatorId())
            ->when($asOfDate !== null, fn (Builder $builder) => $builder->activeAt($asOfDate))
            ->when(isset($validated['country']) && trim((string) $validated['country']) !== '', function (Builder $builder) use ($validated): void {
                $builder->forCountry((string) $validated['country']);
            })
            ->when(isset($validated['income_type']) && trim((string) $validated['income_type']) !== '', function (Builder $builder) use ($validated): void {
                $builder->where('income_type', strtolower(trim((string) $validated['income_type'])));
            });

        if (array_key_exists('is_active', $validated)) {
            $query->where('is_active', (bool) $validated['is_active']);
        }

        $rows = $query
            ->orderBy('country_name')
            ->orderBy('income_type')
            ->orderByDesc('valid_from')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'as_of_date' => $asOfDate?->toDateString(),
            'rows' => $rows->map(static function (WithholdingTaxTreatyRate $rate): array {
                return [
                    'id' => $rate->id,
                    'code' => $rate->code,
                    'country_code' => $rate->country_code,
                    'country_name' => $rate->country_name,
                    'income_type' => $rate->income_type,
                    'standard_rate' => $rate->standard_rate !== null ? (float) $rate->standard_rate : null,
                    'treaty_rate' => (float) $rate->treaty_rate,
                    'requires_residency_certificate' => (bool) $rate->requires_residency_certificate,
                    'legal_basis' => $rate->legal_basis,
                    'valid_from' => $rate->valid_from?->toDateString(),
                    'valid_to' => $rate->valid_to?->toDateString(),
                    'is_active' => (bool) $rate->is_active,
                    'created_at' => $rate->created_at?->toDateTimeString(),
                    'updated_at' => $rate->updated_at?->toDateTimeString(),
                ];
            })->values(),
        ]);
    }

    public function storeWithholdingTreatyRate(Request $request)
    {
        if ($this->cannotManageTaxReports()) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $payload = $this->validateWithholdingTreatyRatePayload($request);
        $rate = WithholdingTaxTreatyRate::query()->create($payload + ['created_by' => creatorId()]);

        return response()->json([
            'message' => __('Withholding treaty rate created successfully.'),
            'data' => [
                'id' => $rate->id,
                'code' => $rate->code,
            ],
        ], 201);
    }

    public function updateWithholdingTreatyRate(Request $request, WithholdingTaxTreatyRate $treatyRate)
    {
        if ($this->cannotManageTaxReports()) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        if ((int) $treatyRate->created_by !== (int) creatorId()) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $payload = $this->validateWithholdingTreatyRatePayload($request, true, $treatyRate);
        $treatyRate->fill($payload);
        $treatyRate->save();

        return response()->json([
            'message' => __('Withholding treaty rate updated successfully.'),
            'data' => [
                'id' => $treatyRate->id,
                'code' => $treatyRate->code,
            ],
        ]);
    }

    public function deactivateWithholdingTreatyRate(WithholdingTaxTreatyRate $treatyRate)
    {
        if ($this->cannotManageTaxReports()) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        if ((int) $treatyRate->created_by !== (int) creatorId()) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $treatyRate->is_active = false;
        if ($treatyRate->valid_to === null) {
            $treatyRate->valid_to = now()->toDateString();
        }
        $treatyRate->save();

        return response()->json([
            'message' => __('Withholding treaty rate deactivated successfully.'),
            'data' => [
                'id' => $treatyRate->id,
                'is_active' => (bool) $treatyRate->is_active,
            ],
        ]);
    }

    public function compareWithholdingTreatyRate(Request $request)
    {
        if ($this->cannotViewTaxSummary()) {
            return response()->json(['message' => __('Permission denied')], 403);
        }

        $validated = $request->validate([
            'country' => 'required|string|max:120',
            'income_type' => 'required|string|max:60',
            'standard_rate' => 'nullable|numeric|min:0|max:100',
            'as_of_date' => 'nullable|date',
        ]);

        $asOfDate = isset($validated['as_of_date'])
            ? Carbon::parse((string) $validated['as_of_date'])->startOfDay()
            : now()->startOfDay();
        $incomeType = strtolower(trim((string) $validated['income_type']));

        $treatyRate = $this->resolveApplicableTreatyRate(
            (string) $validated['country'],
            $incomeType,
            $asOfDate
        );

        $standardRate = array_key_exists('standard_rate', $validated)
            ? (float) $validated['standard_rate']
            : ($treatyRate?->standard_rate !== null ? (float) $treatyRate->standard_rate : null);

        $recommendedRate = $standardRate ?? 0.0;
        $source = 'standard';
        $warnings = [];

        if ($treatyRate !== null) {
            $treatyRateValue = (float) $treatyRate->treaty_rate;
            if ($standardRate !== null && $standardRate > 0) {
                $recommendedRate = min($standardRate, $treatyRateValue);
            } else {
                $recommendedRate = $treatyRateValue;
            }
            $source = 'treaty';

            if ($standardRate !== null && $treatyRateValue > $standardRate) {
                $warnings[] = __('Treaty rate is above the standard local rate. Review legal configuration.');
            }
        }

        return response()->json([
            'country' => $validated['country'],
            'income_type' => $incomeType,
            'as_of_date' => $asOfDate->toDateString(),
            'found_treaty_rate' => $treatyRate !== null,
            'standard_rate' => $standardRate,
            'treaty_rate' => $treatyRate !== null ? (float) $treatyRate->treaty_rate : null,
            'recommended_rate' => round($recommendedRate, 4),
            'source' => $source,
            'requires_residency_certificate' => $treatyRate !== null
                ? (bool) $treatyRate->requires_residency_certificate
                : true,
            'treaty' => $treatyRate ? [
                'id' => $treatyRate->id,
                'code' => $treatyRate->code,
                'country_code' => $treatyRate->country_code,
                'country_name' => $treatyRate->country_name,
                'income_type' => $treatyRate->income_type,
                'legal_basis' => $treatyRate->legal_basis,
                'valid_from' => $treatyRate->valid_from?->toDateString(),
                'valid_to' => $treatyRate->valid_to?->toDateString(),
            ] : null,
            'warnings' => $warnings,
        ]);
    }

    public function updateWithholdingSettlementStatus(Request $request)
    {
        if ($this->cannotManageTaxReports()) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'year' => 'required|string|size:4',
            'month' => 'required|integer|min:1|max:12',
            'action' => 'required|string|in:mark_pending,mark_declared,mark_paid',
            'transaction_ids' => 'nullable|array',
            'transaction_ids.*' => 'integer|min:1',
            'declaration_reference' => 'nullable|string|max:120',
            'state_payment_reference' => 'nullable|string|max:120',
            'status' => 'nullable|string|in:pending,declared,paid',
            'income_type' => 'nullable|string|max:50',
            'vendor_nuit' => 'nullable|string|max:30',
            'vendor_id' => 'nullable|integer|min:1',
        ]);

        if ($validated['action'] === 'mark_declared' && trim((string) ($validated['declaration_reference'] ?? '')) === '') {
            return back()->withErrors([
                'declaration_reference' => __('A referência da declaração é obrigatória para marcar retenções como declaradas.'),
            ]);
        }

        if ($validated['action'] === 'mark_paid' && trim((string) ($validated['state_payment_reference'] ?? '')) === '') {
            return back()->withErrors([
                'state_payment_reference' => __('A referência de pagamento ao Estado é obrigatória para marcar retenções como pagas.'),
            ]);
        }

        $filters = $this->parseWithholdingFilters($request);
        $query = WithholdingTaxTransaction::query()
            ->where('company_id', creatorId())
            ->where('fiscal_year', (string) $validated['year'])
            ->where('fiscal_month', (int) $validated['month'])
            ->with('rule');

        $query = $this->applyWithholdingFilters($query, $filters);

        $transactionIds = collect($validated['transaction_ids'] ?? [])
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn ($id) => $id > 0)
            ->values();

        if ($transactionIds->isNotEmpty()) {
            $query->whereIn('id', $transactionIds->all());
        }

        $transactions = $query->get();
        if ($transactions->isEmpty()) {
            return back()->with('error', __('Nenhuma retenção encontrada para o período/filtro selecionado.'));
        }

        $now = now();
        $updated = 0;

        foreach ($transactions as $transaction) {
            $payload = match ($validated['action']) {
                'mark_pending' => [
                    'status' => 'pending',
                    'declaration_reference' => null,
                    'declared_at' => null,
                    'declared_by' => null,
                    'state_payment_reference' => null,
                    'paid_at' => null,
                    'paid_by' => null,
                ],
                'mark_declared' => [
                    'status' => 'declared',
                    'declaration_reference' => trim((string) ($validated['declaration_reference'] ?? '')),
                    'declared_at' => $transaction->declared_at ?: $now,
                    'declared_by' => $transaction->declared_by ?: Auth::id(),
                    'state_payment_reference' => null,
                    'paid_at' => null,
                    'paid_by' => null,
                ],
                default => [
                    'status' => 'paid',
                    'declaration_reference' => trim((string) ($validated['declaration_reference'] ?? $transaction->declaration_reference)),
                    'declared_at' => $transaction->declared_at ?: $now,
                    'declared_by' => $transaction->declared_by ?: Auth::id(),
                    'state_payment_reference' => trim((string) ($validated['state_payment_reference'] ?? '')),
                    'paid_at' => $now,
                    'paid_by' => Auth::id(),
                ],
            };

            $transaction->fill($payload);
            if ($transaction->isDirty()) {
                $transaction->save();
                $updated++;
            }
        }

        if ($updated === 0) {
            return back()->with('success', __('Nenhuma alteração necessária. Os movimentos já estavam nesse estado.'));
        }

        $message = match ($validated['action']) {
            'mark_pending' => __(':count retenções atualizadas para pendente.', ['count' => $updated]),
            'mark_declared' => __(':count retenções marcadas como declaradas.', ['count' => $updated]),
            default => __(':count retenções marcadas como pagas ao Estado.', ['count' => $updated]),
        };

        return back()->with('success', $message);
    }

    public function exportWithholdingDeclaration(Request $request)
    {
        if ($this->cannotViewTaxSummary()) {
            return back()->with('error', __('Permission denied'));
        }

        $year = (string) $request->get('year', date('Y'));
        $month = (int) $request->get('month', (int) date('m'));
        $filters = $this->parseWithholdingFilters($request);
        $data = $this->fiscalDeclarationService->getWithholdingDeclaration(creatorId(), $year, $month, $filters);

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

        $rows[] = ['', '', ''];
        $rows[] = ['detailed_map', 'beneficiary', 'nuit|country|income|treatment|rate|gross|withholding|net|status|declaration|state_payment|document'];
        foreach (data_get($data, 'detailed_map', []) as $line) {
            $rows[] = [
                (string) data_get($line, 'transaction_date', ''),
                (string) data_get($line, 'beneficiary', ''),
                implode('|', [
                    (string) data_get($line, 'beneficiary_tax_number', ''),
                    (string) data_get($line, 'beneficiary_country', ''),
                    (string) data_get($line, 'income_type', ''),
                    (string) data_get($line, 'withholding_treatment', ''),
                    number_format((float) data_get($line, 'rate', 0), 2, '.', ''),
                    number_format((float) data_get($line, 'gross_amount', 0), 2, '.', ''),
                    number_format((float) data_get($line, 'withholding_amount', 0), 2, '.', ''),
                    number_format((float) data_get($line, 'net_amount', 0), 2, '.', ''),
                    (string) data_get($line, 'status', ''),
                    (string) data_get($line, 'declaration_reference', ''),
                    (string) data_get($line, 'state_payment_reference', ''),
                    (string) data_get($line, 'document_reference', ''),
                ]),
            ];
        }

        $rows[] = ['', '', ''];
        $rows[] = ['history_by_vendor', 'beneficiary', 'nuit|country|income|transactions|gross|withholding|net'];
        foreach (data_get($data, 'history_by_vendor', []) as $line) {
            $rows[] = [
                '',
                (string) data_get($line, 'beneficiary', ''),
                implode('|', [
                    (string) data_get($line, 'beneficiary_tax_number', ''),
                    (string) data_get($line, 'beneficiary_country', ''),
                    (string) data_get($line, 'income_type', ''),
                    (string) data_get($line, 'transactions', ''),
                    number_format((float) data_get($line, 'gross_amount', 0), 2, '.', ''),
                    number_format((float) data_get($line, 'withholding_amount', 0), 2, '.', ''),
                    number_format((float) data_get($line, 'net_amount', 0), 2, '.', ''),
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

    private function parseWithholdingFilters(Request $request): array
    {
        $status = strtolower(trim((string) $request->get('status', '')));
        if (!in_array($status, ['pending', 'declared', 'paid'], true)) {
            $status = '';
        }

        return [
            'vendor_id' => $request->filled('vendor_id') ? (int) $request->get('vendor_id') : null,
            'vendor_nuit' => $request->filled('vendor_nuit') ? trim((string) $request->get('vendor_nuit')) : null,
            'income_type' => $request->filled('income_type') ? trim((string) $request->get('income_type')) : null,
            'status' => $status !== '' ? $status : null,
        ];
    }

    private function applyWithholdingFilters(Builder $query, array $filters): Builder
    {
        $vendorId = $filters['vendor_id'] ?? null;
        if ($vendorId !== null && $vendorId !== '') {
            $query->where('vendor_id', (int) $vendorId);
        }

        $vendorNuit = trim((string) ($filters['vendor_nuit'] ?? ''));
        if ($vendorNuit !== '') {
            $query->where('vendor_nuit', 'like', '%' . $vendorNuit . '%');
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if (in_array($status, ['pending', 'declared', 'paid'], true)) {
            $query->where('status', $status);
        }

        $incomeType = trim((string) ($filters['income_type'] ?? ''));
        if ($incomeType !== '') {
            $query->where(function (Builder $incomeTypeQuery) use ($incomeType): void {
                $incomeTypeQuery->where('income_type_snapshot', $incomeType)
                    ->orWhereHas('rule', function (Builder $ruleQuery) use ($incomeType): void {
                        $ruleQuery->where('income_type', $incomeType);
                    });
            });
        }

        return $query;
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

    public function exportIrpcGuide(Request $request)
    {
        if ($this->cannotViewTaxSummary()) {
            return back()->with('error', __('Permission denied'));
        }

        $year = (string) $request->get('year', date('Y'));
        $data = app(IrpcCalculationService::class)->calculate(creatorId(), $year);

        $rows = [
            ['section', 'metric', 'value'],
            ['period', 'fiscal_year', $year],
            ['calculation', 'accounting_result', number_format((float) data_get($data, 'accounting_result', 0), 2, '.', '')],
            ['calculation', 'add_backs', number_format((float) data_get($data, 'add_backs', 0), 2, '.', '')],
            ['calculation', 'deductions', number_format((float) data_get($data, 'deductions', 0), 2, '.', '')],
            ['calculation', 'taxable_income', number_format((float) data_get($data, 'taxable_income', 0), 2, '.', '')],
            ['calculation', 'irpc_rate', number_format((float) data_get($data, 'irpc_rate', 0), 2, '.', '') . '%'],
            ['calculation', 'gross_tax', number_format((float) data_get($data, 'gross_tax', data_get($data, 'irpc_due', 0)), 2, '.', '')],
            ['calculation', 'payments_on_account_total', number_format((float) data_get($data, 'payments_on_account.total', data_get($data, 'ppc_total', 0)), 2, '.', '')],
            ['calculation', 'withholdings_suffered', number_format((float) data_get($data, 'withholdings_suffered', 0), 2, '.', '')],
            ['calculation', 'net_tax_payable', number_format((float) data_get($data, 'net_tax_payable', data_get($data, 'net_payable', 0)), 2, '.', '')],
            ['calculation', 'net_tax_recoverable', number_format((float) data_get($data, 'net_tax_recoverable', 0), 2, '.', '')],
            ['', '', ''],
            ['payments_on_account', 'installment', 'may|july|september'],
            [
                '',
                'values',
                implode('|', [
                    number_format((float) data_get($data, 'payments_on_account.may', 0), 2, '.', ''),
                    number_format((float) data_get($data, 'payments_on_account.july', 0), 2, '.', ''),
                    number_format((float) data_get($data, 'payments_on_account.september', 0), 2, '.', ''),
                ]),
            ],
        ];

        $rows[] = ['', '', ''];
        $rows[] = ['adjustments', 'type', 'category|description|amount|legal_basis'];

        foreach (data_get($data, 'adjustments_detail', []) as $adjustment) {
            $rows[] = [
                (string) data_get($adjustment, 'type', ''),
                (string) data_get($adjustment, 'category', ''),
                implode('|', [
                    (string) data_get($adjustment, 'description', ''),
                    number_format((float) data_get($adjustment, 'amount', 0), 2, '.', ''),
                    (string) data_get($adjustment, 'legal_basis', ''),
                ]),
            ];
        }

        $rows[] = ['', '', ''];
        $rows[] = ['warnings', 'message', 'value'];
        foreach ((array) data_get($data, 'warnings', []) as $warning) {
            $rows[] = ['warning', '', (string) $warning];
        }

        $csv = $this->fiscalDeclarationService->toCsv($rows);
        $filename = sprintf('irpc-guide-%s.csv', $year);

        $this->recordFiscalExportHistory(
            exportType: 'irpc_csv',
            fileName: $filename,
            fileContent: $csv,
            periodStart: sprintf('%s-01-01', $year),
            periodEnd: sprintf('%s-12-31', $year),
            metadata: [
                'content_type' => 'text/csv',
                'source' => 'sce.tax.irpc.export',
                'fiscal_year' => $year,
                'csv_size_bytes' => strlen($csv),
                'generated_at' => now()->toIso8601String(),
                'net_tax_payable' => (float) data_get($data, 'net_tax_payable', data_get($data, 'net_payable', 0)),
            ]
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
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

    public function exportAnnualDeclaration(Request $request)
    {
        if ($this->cannotViewTaxSummary()) {
            return back()->with('error', __('Permission denied'));
        }

        $year = (string) $request->get('year', date('Y'));
        $data = $this->fiscalDeclarationService->getAnnualFiscalDeclaration(creatorId(), $year);

        $rows = [
            ['section', 'metric', 'value'],
            ['period', 'fiscal_year', $year],
            ['period', 'generated_at', (string) data_get($data, 'generated_at', now()->toDateTimeString())],
            ['', '', ''],
            ['vat', 'output_vat', number_format((float) data_get($data, 'vat.output_vat', 0), 2, '.', '')],
            ['vat', 'supported_vat', number_format((float) data_get($data, 'vat.supported_vat', 0), 2, '.', '')],
            ['vat', 'deductible_vat', number_format((float) data_get($data, 'vat.deductible_vat', 0), 2, '.', '')],
            ['vat', 'non_deductible_vat', number_format((float) data_get($data, 'vat.non_deductible_vat', 0), 2, '.', '')],
            ['vat', 'regularizations', number_format((float) data_get($data, 'vat.regularizations', 0), 2, '.', '')],
            ['vat', 'vat_payable', number_format((float) data_get($data, 'vat.vat_payable', 0), 2, '.', '')],
            ['vat', 'vat_recoverable', number_format((float) data_get($data, 'vat.vat_recoverable', 0), 2, '.', '')],
            ['vat', 'net_position', number_format((float) data_get($data, 'vat.net_position', 0), 2, '.', '')],
            ['', '', ''],
            ['irpc', 'accounting_result', number_format((float) data_get($data, 'irpc.accounting_result', 0), 2, '.', '')],
            ['irpc', 'taxable_income', number_format((float) data_get($data, 'irpc.taxable_income', 0), 2, '.', '')],
            ['irpc', 'rate', number_format((float) data_get($data, 'irpc.irpc_rate', 0), 2, '.', '') . '%'],
            ['irpc', 'irpc_due', number_format((float) data_get($data, 'irpc.irpc_due', 0), 2, '.', '')],
            ['irpc', 'ppc_total', number_format((float) data_get($data, 'irpc.ppc_total', 0), 2, '.', '')],
            ['irpc', 'withholdings_suffered', number_format((float) data_get($data, 'irpc.withholdings_suffered', 0), 2, '.', '')],
            ['irpc', 'net_payable', number_format((float) data_get($data, 'irpc.net_payable', 0), 2, '.', '')],
            ['', '', ''],
            ['withholding', 'transaction_count', (string) data_get($data, 'withholding.transaction_count', 0)],
            ['withholding', 'gross_amount', number_format((float) data_get($data, 'withholding.gross_amount', 0), 2, '.', '')],
            ['withholding', 'withholding_amount', number_format((float) data_get($data, 'withholding.withholding_amount', 0), 2, '.', '')],
            ['withholding', 'net_amount', number_format((float) data_get($data, 'withholding.net_amount', 0), 2, '.', '')],
            ['', '', ''],
            ['model20', 'mapped_movements', (string) data_get($data, 'model20.totals.mapped_movements', 0)],
            ['model20', 'unmapped_movements', (string) data_get($data, 'model20.totals.unmapped_movements', 0)],
            ['model20', 'warnings', implode('|', (array) data_get($data, 'model20.warnings', []))],
        ];

        $csv = $this->fiscalDeclarationService->toCsv($rows);
        $filename = sprintf('annual-declaration-%s.csv', $year);

        $this->recordFiscalExportHistory(
            exportType: 'annual_declaration_csv',
            fileName: $filename,
            fileContent: $csv,
            periodStart: sprintf('%s-01-01', $year),
            periodEnd: sprintf('%s-12-31', $year),
            metadata: [
                'content_type' => 'text/csv',
                'source' => 'sce.tax.annual-declaration.export',
                'fiscal_year' => $year,
                'csv_size_bytes' => strlen($csv),
                'generated_at' => now()->toIso8601String(),
                'warnings' => (array) data_get($data, 'model20.warnings', []),
            ]
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function recordFiscalExportHistory(
        string $exportType,
        string $fileName,
        string $fileContent,
        string $periodStart,
        string $periodEnd,
        array $metadata = []
    ): void {
        $safeFileName = basename($fileName);
        $filePath = sprintf(
            'fiscal-exports/%d/%s/%s-%s-%s',
            creatorId(),
            $exportType,
            now()->format('YmdHis'),
            substr(hash('sha256', $fileContent), 0, 12),
            $safeFileName
        );

        Storage::disk('local')->put($filePath, $fileContent);

        FiscalExportHistory::query()->create([
            'company_id' => creatorId(),
            'export_type' => $exportType,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'generated_by' => Auth::id(),
            'file_name' => $fileName,
            'file_hash' => hash('sha256', $fileContent),
            'file_path' => $filePath,
            'status' => 'generated',
            'metadata' => array_merge([
                'generated_at' => now()->toIso8601String(),
                'file_size_bytes' => strlen($fileContent),
            ], $metadata),
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

    /**
     * @return array<string, mixed>
     */
    private function validateWithholdingTreatyRatePayload(
        Request $request,
        bool $isUpdate = false,
        ?WithholdingTaxTreatyRate $existingRate = null
    ): array
    {
        $validated = $request->validate([
            'code' => [$isUpdate ? 'sometimes' : 'nullable', 'string', 'max:50'],
            'country_code' => 'nullable|string|max:3',
            'country_name' => 'nullable|string|max:120',
            'income_type' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:60'],
            'standard_rate' => 'nullable|numeric|min:0|max:100',
            'treaty_rate' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0', 'max:100'],
            'requires_residency_certificate' => 'nullable|boolean',
            'legal_basis' => 'nullable|string|max:255',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'is_active' => 'nullable|boolean',
        ]);

        $countryCode = strtoupper(trim((string) ($validated['country_code'] ?? ($existingRate?->country_code ?? ''))));
        $countryName = trim((string) ($validated['country_name'] ?? ($existingRate?->country_name ?? '')));

        if ($countryCode === '' && $countryName === '') {
            throw ValidationException::withMessages([
                'country_code' => __('Country code or country name is required.'),
            ]);
        }

        $effectiveStandardRate = array_key_exists('standard_rate', $validated)
            ? ($validated['standard_rate'] !== null ? (float) $validated['standard_rate'] : null)
            : ($existingRate?->standard_rate !== null ? (float) $existingRate->standard_rate : null);
        $effectiveTreatyRate = array_key_exists('treaty_rate', $validated)
            ? (float) $validated['treaty_rate']
            : ($existingRate?->treaty_rate !== null ? (float) $existingRate->treaty_rate : null);

        if ($effectiveStandardRate !== null && $effectiveTreatyRate !== null && $effectiveTreatyRate > $effectiveStandardRate) {
                throw ValidationException::withMessages([
                    'treaty_rate' => __('Treaty rate cannot be greater than the standard local rate.'),
                ]);
        }

        if (array_key_exists('country_code', $validated)) {
            $validated['country_code'] = $countryCode !== '' ? $countryCode : null;
        }
        if (array_key_exists('country_name', $validated)) {
            $validated['country_name'] = $countryName !== '' ? $countryName : null;
        }
        if (array_key_exists('income_type', $validated)) {
            $validated['income_type'] = strtolower(trim((string) $validated['income_type']));
        }
        if (array_key_exists('code', $validated)) {
            $code = strtoupper(trim((string) $validated['code']));
            $validated['code'] = $code !== '' ? $code : null;
        }

        if (!$isUpdate && empty($validated['code'])) {
            $countryToken = $validated['country_code'] ?? 'GLOBAL';
            $incomeToken = strtoupper((string) ($validated['income_type'] ?? 'ALL'));
            $validated['code'] = sprintf('%s-%s', $countryToken, $incomeToken);
        }

        if (array_key_exists('requires_residency_certificate', $validated)) {
            $validated['requires_residency_certificate'] = (bool) $validated['requires_residency_certificate'];
        }
        if (array_key_exists('is_active', $validated)) {
            $validated['is_active'] = (bool) $validated['is_active'];
        }

        return $validated;
    }

    private function resolveApplicableTreatyRate(string $country, string $incomeType, Carbon $asOfDate): ?WithholdingTaxTreatyRate
    {
        $incomeType = strtolower(trim($incomeType));
        if ($incomeType === '') {
            $incomeType = 'all';
        }

        return WithholdingTaxTreatyRate::query()
            ->where('created_by', creatorId())
            ->where('is_active', true)
            ->forCountry($country)
            ->whereIn('income_type', [$incomeType, 'all'])
            ->activeAt($asOfDate)
            ->orderByRaw('CASE WHEN income_type = ? THEN 0 WHEN income_type = ? THEN 1 ELSE 2 END', [$incomeType, 'all'])
            ->orderByDesc('valid_from')
            ->orderByDesc('id')
            ->first();
    }
}
