<?php

namespace App\Http\Controllers;

use App\Models\FixedAsset;
use App\Models\DepreciationEntry;
use App\Services\DepreciationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FixedAssetController extends Controller
{
    public function index(Request $request)
    {
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
        return Inertia::render('Assets/FixedAssets/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_code' => 'required|string|max:30',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|in:tangible,intangible,investment_property,biological',
            'sub_category' => 'nullable|string|max:50',
            'acquisition_date' => 'required|date',
            'acquisition_cost' => 'required|numeric|min:0.01',
            'residual_value' => 'nullable|numeric|min:0',
            'useful_life_months' => 'required|integer|min:1',
            'depreciation_method' => 'required|string|in:straight_line,declining_balance,units_of_production',
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

        // Default PGC accounts based on category
        $pgcAccounts = match ($validated['category']) {
            'tangible' => ['pgc_asset_account' => '43', 'pgc_depreciation_account' => '48', 'pgc_expense_account' => '64'],
            'intangible' => ['pgc_asset_account' => '44', 'pgc_depreciation_account' => '48', 'pgc_expense_account' => '64'],
            'investment_property' => ['pgc_asset_account' => '42', 'pgc_depreciation_account' => '48', 'pgc_expense_account' => '64'],
            default => ['pgc_asset_account' => '43', 'pgc_depreciation_account' => '48', 'pgc_expense_account' => '64'],
        };

        FixedAsset::create(array_merge($validated, $pgcAccounts));

        return redirect()->route('sce.fixed-assets.index')->with('success', __('Activo fixo registado com sucesso.'));
    }

    public function show(FixedAsset $fixedAsset)
    {
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

    public function runDepreciation(Request $request)
    {
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
