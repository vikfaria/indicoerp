<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScePermissionChecks;
use App\Models\CompanyFiscalProfile;
use App\Models\PgcAccountCatalog;
use App\Models\PgcAccountMapping;
use App\Services\PgcImportService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PgcImportController extends Controller
{
    use ScePermissionChecks;

    public function __construct(
        private readonly PgcImportService $pgcImportService,
    ) {}

    /**
     * Show PGC catalog and import status.
     */
    public function index(Request $request)
    {
        if (!$this->canViewSceSuite()) {
            abort(403, __('Permission denied'));
        }

        $companyId = creatorId();
        $profile = CompanyFiscalProfile::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        $framework = $request->input('framework', 'pgc_nirf');

        // Get catalog accounts grouped by class
        $catalog = PgcAccountCatalog::where('framework', $framework)
            ->orderBy('account_code')
            ->get()
            ->groupBy('class_number')
            ->map(fn ($accounts, $class) => [
                'class' => $class,
                'name' => $this->getClassName($class),
                'count' => $accounts->count(),
                'accounts' => $accounts->map(fn ($a) => [
                    'code' => $a->account_code,
                    'name' => $a->account_name,
                    'parent' => $a->parent_code,
                    'level' => $a->level,
                    'movement' => $a->is_movement_account,
                    'balance' => $a->normal_balance,
                    'fs_line' => $a->financial_statement_line,
                ]),
            ])
            ->values();

        $validationReport = $this->pgcImportService->buildValidationReport($companyId, $framework);

        $mappingSummary = PgcAccountMapping::query()
            ->where('company_id', $companyId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return Inertia::render('Fiscal/PgcImport/Index', [
            'catalog' => $catalog,
            'totalCatalog' => $validationReport['catalog_count'],
            'importedCount' => $validationReport['company_pgc_count'],
            'validationReport' => $validationReport,
            'mappingSummary' => array_merge([
                'pending' => 0,
                'mapped' => 0,
                'verified' => 0,
            ], $mappingSummary),
            'framework' => $framework,
            'profileFramework' => $profile?->accounting_framework,
        ]);
    }

    /**
     * Execute PGC import for the current company.
     */
    public function import(Request $request)
    {
        if (!$this->canManageSceFiscal()) {
            return back()->with('error', __('Permission denied'));
        }

        $request->validate([
            'framework' => 'sometimes|string|in:pgc_nirf,pgc_pe',
        ]);

        $companyId = creatorId();
        $framework = $request->input('framework', 'pgc_nirf');

        $result = $this->pgcImportService->importForCompany($companyId, $framework);

        if ($result['error']) {
            return back()->withErrors(['import' => $result['error']]);
        }

        return back()->with('success', __(':imported contas importadas, :skipped já existiam.', [
            'imported' => $result['imported'],
            'skipped' => $result['skipped'],
        ]));
    }

    /**
     * Validate the PGC structure for the current company.
     */
    public function validate(Request $request)
    {
        if (!$this->canManageSceFiscal()) {
            return back()->with('error', __('Permission denied'));
        }

        $companyId = creatorId();
        $framework = $request->input('framework', 'pgc_nirf');
        $report = $this->pgcImportService->buildValidationReport($companyId, $framework);

        return back()->with(
            $report['valid'] ? 'success' : 'warning',
            $report['valid']
                ? __('Estrutura PGC válida — catálogo oficial carregado e validado.')
                : __(':count problemas encontrados na estrutura PGC.', ['count' => count($report['errors'])])
        );
    }

    public function reconcile(Request $request)
    {
        if (!$this->canManageSceFiscal()) {
            return back()->with('error', __('Permission denied'));
        }

        $companyId = creatorId();
        $result = $this->pgcImportService->createMigrationMappings($companyId);

        return back()->with('success', __('Mapeamentos gerados: :mapped mapeados, :unmapped pendentes.', [
            'mapped' => $result['mapped'],
            'unmapped' => $result['unmapped'],
        ]));
    }

    private function getClassName(int $class): string
    {
        return match ($class) {
            0 => 'Contas de Ordem',
            1 => 'Meios Financeiros Líquidos',
            2 => 'Contas a Receber e a Pagar',
            3 => 'Inventários e Activos Biológicos',
            4 => 'Investimentos',
            5 => 'Capital, Reservas e Resultados Transitados',
            6 => 'Gastos e Perdas',
            7 => 'Rendimentos e Ganhos',
            8 => 'Resultados',
            9 => 'Contabilidade Analítica e de Gestão',
            default => 'Outros',
        };
    }
}
