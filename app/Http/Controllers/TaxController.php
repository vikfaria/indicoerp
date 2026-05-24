<?php

namespace App\Http\Controllers;

use App\Models\MzVatCode;
use App\Models\IrpcConfiguration;
use App\Models\TaxAdjustment;
use App\Models\WithholdingTaxRule;
use App\Models\WithholdingTaxTransaction;
use App\Services\VatCalculationService;
use App\Services\IrpcCalculationService;
use App\Services\WithholdingTaxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TaxController extends Controller
{
    // VAT Map
    public function vatMap(Request $request)
    {
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
        $adjustment->delete();
        return back()->with('success', __('Correcção fiscal eliminada.'));
    }

    // Withholding Tax
    public function withholdingIndex(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', (int) date('m'));

        $rules = WithholdingTaxRule::where('is_active', true)->get();

        $transactions = WithholdingTaxTransaction::where('company_id', creatorId())
            ->where('fiscal_year', $year)
            ->when($request->month, fn($q) => $q->where('fiscal_month', $month))
            ->with('rule')
            ->orderByDesc('transaction_date')
            ->paginate(20);

        return Inertia::render('Tax/Withholding/Index', [
            'rules' => $rules,
            'transactions' => $transactions,
            'year' => $year,
            'month' => (int) $month,
        ]);
    }

    public function withholdingStore(Request $request)
    {
        $validated = $request->validate([
            'rule_code' => 'required|string|exists:withholding_tax_rules,code',
            'gross_amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'vendor_name' => 'nullable|string|max:255',
            'vendor_nuit' => 'nullable|string|max:9',
            'document_reference' => 'nullable|string|max:50',
        ]);

        $service = app(WithholdingTaxService::class);

        try {
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
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Retenção na fonte registada.'));
    }
}
