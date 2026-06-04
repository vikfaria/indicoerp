<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScePermissionChecks;
use App\Models\AuditTrail;
use App\Models\FixedAsset;
use App\Models\DepreciationEntry;
use App\Services\DepreciationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class FixedAssetController extends Controller
{
    use ScePermissionChecks;

    public function index(Request $request)
    {
        if (!$this->canViewSceSuite()) {
            abort(403, __('Permission denied'));
        }

        $query = FixedAsset::where('company_id', creatorId());

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->category) {
            $query->where('category', $request->category);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('asset_code', 'like', "%{$request->search}%");
            });
        }

        $assets = $query->orderBy('asset_code')->paginate(20);

        $service = app(DepreciationService::class);
        $register = $service->getAssetRegister(creatorId());

        return Inertia::render('Assets/FixedAssets/Index', [
            'assets' => $assets,
            'summary' => $register['summary'],
            'byCategory' => $register['by_category'],
        ]);
    }

    public function create()
    {
        if (!$this->canManageSceAccounting()) {
            abort(403, __('Permission denied'));
        }

        return Inertia::render('Assets/FixedAssets/Create');
    }

    public function store(Request $request)
    {
        if (!$this->canManageSceAccounting()) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'asset_code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('fixed_assets', 'asset_code')
                    ->where(fn ($query) => $query->where('company_id', creatorId())),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|in:tangible,intangible,investment_property,biological',
            'sub_category' => 'nullable|string|max:50',
            'acquisition_date' => 'required|date',
            'acquisition_cost' => 'required|numeric|min:0.01',
            'residual_value' => 'nullable|numeric|min:0',
            'useful_life_months' => 'required|integer|min:1',
            'depreciation_method' => 'required|string|in:straight_line',
            'location' => 'nullable|string|max:100',
            'responsible_person' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:50',
            'supplier' => 'nullable|string|max:100',
            'invoice_reference' => 'nullable|string|max:50',
        ]);

        $validated['company_id'] = creatorId();
        $validated['created_by'] = Auth::id();
        $validated['residual_value'] = $validated['residual_value'] ?? 0;
        $validated['net_book_value'] = $validated['acquisition_cost'];
        $validated['depreciation_rate'] = round(12 / $validated['useful_life_months'] * 100, 2);

        // Default PGC accounts based on category.
        // The current chart of accounts only exposes movement accounts for
        // equipment/buildings, so the operational defaults are intentionally
        // conservative until a company configures a more granular chart.
        $pgcAccounts = match ($validated['category']) {
            'investment_property' => ['pgc_asset_account' => '1700', 'pgc_depreciation_account' => '1710', 'pgc_expense_account' => '5430'],
            default => ['pgc_asset_account' => '1600', 'pgc_depreciation_account' => '1610', 'pgc_expense_account' => '5430'],
        };

        FixedAsset::create(array_merge($validated, $pgcAccounts));

        return redirect()->route('sce.fixed-assets.index')->with('success', __('Activo fixo registado com sucesso.'));
    }

    public function show(FixedAsset $fixedAsset)
    {
        if (!$this->canViewSceSuite()) {
            abort(403, __('Permission denied'));
        }

        if ($fixedAsset->company_id !== creatorId()) {
            abort(403, __('Permission denied'));
        }

        $depreciations = DepreciationEntry::where('fixed_asset_id', $fixedAsset->id)
            ->orderByDesc('depreciation_date')
            ->get();

        $service = app(DepreciationService::class);
        $schedule = $service->getSchedule($fixedAsset);

        return Inertia::render('Assets/FixedAssets/Show', [
            'asset' => $fixedAsset,
            'depreciations' => $depreciations,
            'schedule' => $schedule,
        ]);
    }

    public function dispose(Request $request, FixedAsset $fixedAsset)
    {
        if (!$this->canManageSceAccounting()) {
            return back()->with('error', __('Permission denied'));
        }

        if ($fixedAsset->company_id !== creatorId()) {
            abort(403, __('Permission denied'));
        }

        $validated = $request->validate([
            'disposal_date' => 'required|date',
            'disposal_proceeds' => 'nullable|numeric|min:0',
        ]);

        $before = $fixedAsset->only([
            'status',
            'net_book_value',
            'accumulated_depreciation',
            'disposal_date',
            'disposal_proceeds',
        ]);

        try {
            $service = app(DepreciationService::class);
            $result = $service->disposeAsset(
                $fixedAsset,
                $validated['disposal_date'],
                (float) ($validated['disposal_proceeds'] ?? 0)
            );

            $fixedAsset->refresh();

            AuditTrail::create([
                'company_id' => creatorId(),
                'user_id' => Auth::id(),
                'event' => 'disposed',
                'auditable_type' => FixedAsset::class,
                'auditable_id' => $fixedAsset->id,
                'route' => $request->route()?->getName(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'old_values' => $before,
                'new_values' => $fixedAsset->only([
                    'status',
                    'net_book_value',
                    'accumulated_depreciation',
                    'disposal_date',
                    'disposal_proceeds',
                ]),
                'changes' => [
                    'status' => 'disposed',
                    'disposal_date' => $fixedAsset->disposal_date?->toDateString(),
                    'disposal_proceeds' => (float) $fixedAsset->disposal_proceeds,
                    'gain_or_loss' => $result['gain_or_loss'],
                ],
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __(
            'Baixa do activo registada com sucesso. Resultado: :result.',
            ['result' => number_format((float) $result['gain_or_loss'], 2) . ' MZN']
        ));
    }

    public function runDepreciation(Request $request)
    {
        if (!$this->canManageSceAccounting()) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'year' => 'required|string|size:4',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $service = app(DepreciationService::class);
        $result = $service->runMonthlyDepreciation(creatorId(), $validated['year'], $validated['month']);

        return back()->with('success', __(
            'Depreciação executada: :processed activos processados, :total MZN.',
            ['processed' => $result['processed'], 'total' => number_format($result['total_amount'], 2)]
        ));
    }
}
