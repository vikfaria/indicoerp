<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScePermissionChecks;
use App\Models\FiscalDocumentSeries;
use App\Models\FiscalDocumentType;
use App\Services\FiscalHashService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class FiscalDocumentSeriesController extends Controller
{
    use ScePermissionChecks;

    public function __construct(
        private readonly FiscalHashService $hashService,
    ) {}

    /**
     * List all document series for the company.
     */
    public function index()
    {
        if (!$this->canViewSceSuite()) {
            abort(403, __('Permission denied'));
        }

        $companyId = creatorId();

        $series = FiscalDocumentSeries::with('fiscalDocumentType')
            ->where('company_id', $companyId)
            ->orderBy('fiscal_year', 'desc')
            ->orderBy('series_code')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'series_code' => $s->series_code,
                'fiscal_year' => $s->fiscal_year,
                'doc_type_code' => $s->fiscalDocumentType?->code,
                'doc_type_name' => $s->fiscalDocumentType?->name,
                'last_sequence' => $s->last_sequence,
                'last_hash' => $s->last_hash ? substr($s->last_hash, 0, 8) . '...' : null,
                'is_active' => $s->is_active,
                'valid_from' => $s->valid_from?->format('Y-m-d'),
                'valid_to' => $s->valid_to?->format('Y-m-d'),
                'assigned_user_id' => $s->assigned_user_id,
                'terminal_code' => $s->terminal_code,
                'fiscal_regime_code' => $s->fiscal_regime_code,
            ]);

        $documentTypes = FiscalDocumentType::where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $companyUsers = \App\Models\User::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('id', $companyId)
                    ->orWhere('created_by', $companyId);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'type'])
            ->map(static fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'type' => $user->type,
                'label' => trim(sprintf('%s (%s)', (string) $user->name, (string) $user->type)),
            ])
            ->values();

        return Inertia::render('Fiscal/DocumentSeries/Index', [
            'series' => $series,
            'documentTypes' => $documentTypes,
            'companyUsers' => $companyUsers,
            'currentYear' => (int) date('Y'),
        ]);
    }

    /**
     * Store a new document series.
     */
    public function store(Request $request)
    {
        if (!$this->canManageSceFiscal()) {
            return back()->with('error', __('Permission denied'));
        }

        $companyId = creatorId();

        $validated = $request->validate([
            'fiscal_document_type_id' => 'required|exists:fiscal_document_types,id',
            'series_code' => 'required|string|max:5|regex:/^[A-Z0-9]+$/',
            'fiscal_year' => 'required|integer|min:2020|max:2099',
            'assigned_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where(function ($q) use ($companyId): void {
                        $q->where('id', $companyId)
                            ->orWhere('created_by', $companyId);
                    });
                }),
            ],
            'terminal_code' => 'nullable|string|max:50|regex:/^[A-Z0-9_-]+$/i',
            'fiscal_regime_code' => 'nullable|string|max:50|regex:/^[A-Z0-9_-]+$/i',
        ]);

        // Check uniqueness
        $exists = FiscalDocumentSeries::where('company_id', $companyId)
            ->where('fiscal_document_type_id', $validated['fiscal_document_type_id'])
            ->where('series_code', $validated['series_code'])
            ->where('fiscal_year', $validated['fiscal_year'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['series_code' => __('Já existe uma série com este código para este tipo e exercício.')]);
        }

        FiscalDocumentSeries::create([
            'company_id' => $companyId,
            'fiscal_document_type_id' => $validated['fiscal_document_type_id'],
            'series_code' => $validated['series_code'],
            'assigned_user_id' => $validated['assigned_user_id'] ?? null,
            'terminal_code' => !empty($validated['terminal_code'])
                ? strtoupper((string) $validated['terminal_code'])
                : null,
            'fiscal_regime_code' => !empty($validated['fiscal_regime_code'])
                ? strtoupper((string) $validated['fiscal_regime_code'])
                : null,
            'fiscal_year' => $validated['fiscal_year'],
            'last_sequence' => 0,
            'is_active' => true,
            'valid_from' => "{$validated['fiscal_year']}-01-01",
            'valid_to' => "{$validated['fiscal_year']}-12-31",
            'created_by' => $companyId,
        ]);

        return back()->with('success', __('Série documental criada com sucesso.'));
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleActive(FiscalDocumentSeries $series)
    {
        if (!$this->canManageSceFiscal()) {
            return back()->with('error', __('Permission denied'));
        }

        if ($series->company_id !== creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        $series->update(['is_active' => !$series->is_active]);

        return back()->with('success', $series->is_active
            ? __('Série activada.')
            : __('Série desactivada.'));
    }

    /**
     * Verify the hash chain integrity for a series.
     */
    public function verifyChain(FiscalDocumentSeries $series)
    {
        if (!$this->canManageSceFiscal()) {
            return back()->with('error', __('Permission denied'));
        }

        if ($series->company_id !== creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        $result = $this->hashService->verifyChain($series->id);

        if ($result['valid']) {
            return back()->with('success', __('Cadeia de hash verificada — :count documentos, todos válidos.', [
                'count' => $result['checked'],
            ]));
        }

        return back()->with('warning', __('Cadeia de hash com :errors erros em :count documentos verificados.', [
            'errors' => count($result['errors']),
            'count' => $result['checked'],
        ]));
    }
}
