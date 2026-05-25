<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScePermissionChecks;
use App\Models\PgcAccountCatalog;
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
    public function index()
    {
        if (!$this->canViewSceSuite()) {
            abort(403, __('Permission denied'));
        }

        $companyId = creatorId();
        $framework = 'pgc_nirf';

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

        // Check how many have been imported for this company
        $importedCount = \Workdo\Account\Models\ChartOfAccount::where('created_by', $companyId)
            ->whereNotNull('pgc_class')
            ->count();

        // Validate structure
        $issues = $this->pgcImportService->validateStructure($companyId);

        return Inertia::render('Fiscal/PgcImport/Index', [
            'catalog' => $catalog,
            'totalCatalog' => PgcAccountCatalog::where('framework', $framework)->count(),
            'importedCount' => $importedCount,
            'issues' => $issues,
            'framework' => $framework,
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
        $issues = $this->pgcImportService->validateStructure($companyId);

        return back()->with(
            empty($issues) ? 'success' : 'warning',
            empty($issues)
                ? __('Estrutura PGC válida — todas as classes obrigatórias e contas essenciais presentes.')
                : __(':count problemas encontrados na estrutura PGC.', ['count' => count($issues)])
        );
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
            default => 'Outros',
        };
    }
}
