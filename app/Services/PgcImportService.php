<?php

namespace App\Services;

use App\Models\PgcAccountCatalog;
use App\Models\PgcAccountMapping;
use App\Models\CompanyFiscalProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Workdo\Account\Helpers\AccountUtility;
use Workdo\Account\Models\AccountCategory;
use Workdo\Account\Models\AccountType;
use Workdo\Account\Models\ChartOfAccount;

/**
 * Service to import PGC-MZ chart of accounts for a company
 * and migrate existing generic accounts.
 */
class PgcImportService
{
    /**
     * Default mapping from legacy generic codes to PGC-MZ codes.
     * Used for automatic migration of existing companies.
     */
    private const LEGACY_TO_PGC = [
        // Assets
        '1000' => '11',    // Cash and cash equivalents → Caixa
        '1050' => '111',   // Petty cash → Caixa
        '1100' => '211',   // Accounts Receivable → Clientes c/c
        '1200' => '31',    // Inventory → Compras (mercadorias)
        '1300' => '12',    // Prepaid expenses → Depósitos à ordem
        '1400' => '24',    // Other current assets → Estado e outros entes públicos
        '1500' => '2432',  // VAT Receivable → IVA dedutível
        '1600' => '43',    // Fixed assets → Activos fixos tangíveis
        '1700' => '44',    // Accumulated depreciation → Activos intangíveis
        // Liabilities
        '2000' => '221',   // Accounts Payable → Fornecedores c/c
        '2100' => '23',    // Accrued liabilities → Pessoal
        '2200' => '2433',  // Sales tax payable → IVA liquidado
        '2210' => '2433',  // VAT Output → IVA liquidado
        '2300' => '25',    // Unearned revenue → Financiamentos obtidos
        '2350' => '218',   // Customer deposits → Adiantamentos de clientes
        '2400' => '24',    // Current portion LT debt → Estado
        '2500' => '25',    // Long-term debt → Financiamentos obtidos
        // Equity
        '3000' => '51',    // Owner's equity → Capital
        '3100' => '52',    // Common stock → Acções/quotas próprias
        '3200' => '56',    // Retained earnings → Resultados transitados
        '3300' => '55',    // Dividends → Reservas
        // Revenue
        '4100' => '71',    // Sales revenue → Vendas
        '4200' => '72',    // Service revenue → Prestações de serviços
        '4300' => '78',    // Other revenue → Outros rendimentos
        // Expenses
        '5100' => '61',    // Cost of goods sold → CMVMC
        '5200' => '62',    // Operating expenses → Fornecimentos e serviços externos
        '5300' => '63',    // Salaries → Gastos com pessoal
        '5400' => '64',    // Depreciation → Depreciações e amortizações
        '5500' => '68',    // Other expenses → Outros gastos e perdas
        '5600' => '69',    // Interest expense → Gastos e perdas de financiamento
    ];

    /**
     * Import PGC catalog accounts into a company's chart of accounts.
     */
    public function importForCompany(int $companyId, string $framework = 'pgc_nirf'): array
    {
        $catalogAccounts = PgcAccountCatalog::where('framework', $framework)
            ->orderBy('account_code')
            ->get();

        if ($catalogAccounts->isEmpty()) {
            return ['imported' => 0, 'skipped' => 0, 'error' => 'Catálogo PGC não encontrado. Execute o seeder primeiro.'];
        }

        $imported = 0;
        $skipped = 0;
        $accountTypeCache = [];

        DB::transaction(function () use ($catalogAccounts, $companyId, $framework, &$imported, &$skipped, &$accountTypeCache) {
            foreach ($catalogAccounts as $catalog) {
                // Skip if account already exists for this company
                $exists = ChartOfAccount::where('account_code', $catalog->account_code)
                    ->where('created_by', $companyId)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                // Resolve account type
                $accountTypeId = $this->resolveAccountType($catalog, $companyId, $accountTypeCache);

                // Resolve parent
                $parentId = null;
                if ($catalog->parent_code) {
                    $parent = ChartOfAccount::where('account_code', $catalog->parent_code)
                        ->where('created_by', $companyId)
                        ->first();
                    $parentId = $parent?->id;
                }

                ChartOfAccount::create([
                    'account_code' => $catalog->account_code,
                    'account_name' => $catalog->account_name,
                    'account_type_id' => $accountTypeId,
                    'parent_account_id' => $parentId,
                    'level' => $catalog->level,
                    'normal_balance' => $catalog->normal_balance,
                    'opening_balance' => 0,
                    'current_balance' => 0,
                    'is_active' => true,
                    'is_system_account' => true,
                    'is_movement_account' => $catalog->is_movement_account,
                    'pgc_class' => $catalog->class_number,
                    'tax_type' => $catalog->tax_type,
                    'financial_statement_line' => $catalog->financial_statement_line,
                    'modelo20_line' => $catalog->modelo20_line,
                    'saft_taxonomy_code' => $catalog->saft_taxonomy_code,
                    'accounting_framework' => $framework,
                    'description' => $catalog->description,
                    'creator_id' => $companyId,
                    'created_by' => $companyId,
                ]);

                $imported++;
            }
        });

        return ['imported' => $imported, 'skipped' => $skipped, 'error' => null];
    }

    /**
     * Create automatic migration mappings from legacy to PGC-MZ codes.
     */
    public function createMigrationMappings(int $companyId): array
    {
        $legacyAccounts = ChartOfAccount::where('created_by', $companyId)
            ->whereNull('pgc_class')
            ->get();

        $mapped = 0;
        $unmapped = 0;

        foreach ($legacyAccounts as $account) {
            $pgcCode = self::LEGACY_TO_PGC[$account->account_code] ?? null;

            PgcAccountMapping::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'legacy_account_code' => $account->account_code,
                ],
                [
                    'pgc_account_code' => $pgcCode ?? '',
                    'status' => $pgcCode ? 'mapped' : 'pending',
                    'notes' => $pgcCode ? null : 'Mapeamento automático não encontrado — requer revisão manual.',
                    'created_by' => $companyId,
                ]
            );

            $pgcCode ? $mapped++ : $unmapped++;
        }

        return ['mapped' => $mapped, 'unmapped' => $unmapped];
    }

    /**
     * Execute the migration: update journal entry items to point to PGC accounts.
     * This should be run after manual review of mappings.
     */
    public function executeMigration(int $companyId): array
    {
        $mappings = PgcAccountMapping::where('company_id', $companyId)
            ->where('status', 'verified')
            ->whereNotNull('pgc_account_code')
            ->where('pgc_account_code', '!=', '')
            ->get();

        $migrated = 0;
        $errors = [];

        DB::transaction(function () use ($mappings, $companyId, &$migrated, &$errors) {
            foreach ($mappings as $mapping) {
                $legacyAccount = ChartOfAccount::where('account_code', $mapping->legacy_account_code)
                    ->where('created_by', $companyId)
                    ->first();

                $pgcAccount = ChartOfAccount::where('account_code', $mapping->pgc_account_code)
                    ->where('created_by', $companyId)
                    ->first();

                if (!$legacyAccount || !$pgcAccount) {
                    $errors[] = "Conta {$mapping->legacy_account_code} → {$mapping->pgc_account_code}: conta de origem ou destino não encontrada.";
                    continue;
                }

                // Transfer balance
                $pgcAccount->current_balance += $legacyAccount->current_balance;
                $pgcAccount->opening_balance += $legacyAccount->opening_balance;
                $pgcAccount->save();

                // Update journal entry items to point to PGC account
                DB::table('journal_entry_items')
                    ->where('account_id', $legacyAccount->id)
                    ->update(['account_id' => $pgcAccount->id]);

                // Deactivate legacy account
                $legacyAccount->is_active = false;
                $legacyAccount->description = ($legacyAccount->description ?? '') . ' [Migrado para ' . $mapping->pgc_account_code . ']';
                $legacyAccount->save();

                $migrated++;
            }
        });

        return ['migrated' => $migrated, 'errors' => $errors];
    }

    /**
     * Validate the PGC structure for a company.
     *
     * @return array<int, string>
     */
    public function validateStructure(int $companyId, ?string $framework = null): array
    {
        return $this->buildValidationReport($companyId, $framework)['errors'];
    }

    /**
     * Build a validation report for the official PGC catalog imported in the company.
     *
     * @return array{
     *     framework: string,
     *     profile_framework: string|null,
     *     catalog_count: int,
     *     company_pgc_count: int,
     *     legacy_active_count: int,
     *     missing_classes: array<int>,
     *     missing_codes: array<int, string>,
     *     extra_codes: array<int, string>,
     *     class_coverage: array<int, array{class:int,label:string,official_count:int,company_count:int}>,
     *     warnings: array<int, string>,
     *     errors: array<int, string>,
     *     valid: bool,
     * }
     */
    public function buildValidationReport(int $companyId, ?string $framework = null): array
    {
        $resolvedFramework = $framework ?: 'pgc_nirf';
        $profile = CompanyFiscalProfile::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        $catalog = PgcAccountCatalog::query()
            ->where('framework', $resolvedFramework)
            ->orderBy('account_code')
            ->get();

        $companyAccounts = ChartOfAccount::query()
            ->where('created_by', $companyId)
            ->where('is_active', true)
            ->get();

        if ($catalog->isEmpty()) {
            return [
                'framework' => $resolvedFramework,
                'profile_framework' => $profile?->accounting_framework,
                'catalog_count' => 0,
                'company_pgc_count' => 0,
                'legacy_active_count' => 0,
                'missing_classes' => [],
                'missing_codes' => [],
                'extra_codes' => [],
                'class_coverage' => [],
                'warnings' => [],
                'errors' => [
                    __('Catálogo PGC não encontrado. Execute o seeder primeiro.'),
                ],
                'valid' => false,
            ];
        }

        $officialAccounts = $companyAccounts->filter(function (ChartOfAccount $account) use ($resolvedFramework): bool {
            return $account->pgc_class !== null
                && (string) ($account->accounting_framework ?? '') === $resolvedFramework;
        })->values();

        $legacyActiveAccounts = $companyAccounts->filter(function (ChartOfAccount $account) use ($resolvedFramework): bool {
            return $account->is_active
                && (
                    $account->pgc_class === null
                    || (string) ($account->accounting_framework ?? '') !== $resolvedFramework
                );
        })->values();

        $warnings = [];
        $errors = [];

        if ($profile !== null && (string) $profile->accounting_framework !== $resolvedFramework) {
            $warnings[] = __('O perfil fiscal activo sugere :suggested, mas a validação está a ser executada sobre :framework.', [
                'suggested' => $profile->accounting_framework,
                'framework' => $resolvedFramework,
            ]);
        }

        $classesFound = $officialAccounts
            ->pluck('pgc_class')
            ->filter(fn ($class): bool => $class !== null)
            ->map(fn ($class): int => (int) $class)
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $requiredClasses = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $missingClasses = array_values(array_diff($requiredClasses, $classesFound));

        foreach ($missingClasses as $class) {
            $errors[] = __('Classe :class não tem contas PGC activas.', ['class' => $class]);
        }

        $catalogCodes = $catalog->pluck('account_code')->values()->all();
        $officialCodes = $officialAccounts->pluck('account_code')->values()->all();
        $missingCodes = array_values(array_diff($catalogCodes, $officialCodes));
        $extraCodes = array_values(array_diff($officialCodes, $catalogCodes));

        if (!empty($missingCodes)) {
            $errors[] = __('Faltam :count contas oficiais PGC no catálogo importado. Exemplos: :codes', [
                'count' => count($missingCodes),
                'codes' => $this->summariseCodes($missingCodes),
            ]);
        }

        if (!empty($extraCodes)) {
            $warnings[] = __('Existem :count contas PGC activas fora do catálogo oficial. Exemplos: :codes', [
                'count' => count($extraCodes),
                'codes' => $this->summariseCodes($extraCodes),
            ]);
        }

        $essentialCodes = ['0', '9', '11', '12', '211', '221', '2432', '2433', '51', '56', '71', '61', '81'];
        foreach ($essentialCodes as $code) {
            $found = $officialAccounts->first(fn ($account) => str_starts_with((string) $account->account_code, $code));
            if (!$found) {
                $errors[] = __('Conta essencial ":code" não encontrada no PGC oficial.', ['code' => $code]);
            }
        }

        $classCoverage = collect($requiredClasses)
            ->map(function (int $class) use ($catalog, $officialAccounts): array {
                return [
                    'class' => $class,
                    'label' => $this->resolveClassLabel($class),
                    'official_count' => $catalog->where('class_number', $class)->count(),
                    'company_count' => $officialAccounts->where('pgc_class', $class)->count(),
                ];
            })
            ->values()
            ->all();

        if ($legacyActiveAccounts->isNotEmpty()) {
            $warnings[] = __('Existem :count contas legadas activas sem reconciliação PGC.', [
                'count' => $legacyActiveAccounts->count(),
            ]);
        }

        return [
            'framework' => $resolvedFramework,
            'profile_framework' => $profile?->accounting_framework,
            'catalog_count' => $catalog->count(),
            'company_pgc_count' => $officialAccounts->count(),
            'legacy_active_count' => $legacyActiveAccounts->count(),
            'missing_classes' => $missingClasses,
            'missing_codes' => $missingCodes,
            'extra_codes' => $extraCodes,
            'class_coverage' => $classCoverage,
            'warnings' => $warnings,
            'errors' => $errors,
            'valid' => empty($errors),
        ];
    }

    /**
     * Resolve or create the appropriate AccountType for a PGC catalog entry.
     */
    private function resolveAccountType(PgcAccountCatalog $catalog, int $companyId, array &$cache): int
    {
        $typeMeta = match ($catalog->class_number) {
            0 => ['name' => 'Contas de Ordem', 'category_code' => 'LIB'],
            1 => ['name' => 'Meios Financeiros Líquidos', 'category_code' => 'AST'],
            2 => ['name' => 'Inventários e Activos Biológicos', 'category_code' => 'AST'],
            3 => ['name' => 'Investimentos de Capital', 'category_code' => 'AST'],
            4 => ['name' => 'Contas a Receber, Contas a Pagar e Empréstimos', 'category_code' => 'LIB'],
            5 => ['name' => 'Capital Próprio', 'category_code' => 'EQT'],
            6 => ['name' => 'Custos e Perdas', 'category_code' => 'EXP'],
            7 => ['name' => 'Proveitos e Ganhos', 'category_code' => 'REV'],
            8 => ['name' => 'Resultados', 'category_code' => 'EQT'],
            9 => ['name' => 'Contabilidade Analítica e de Gestão', 'category_code' => 'EXP'],
            default => ['name' => 'Outros', 'category_code' => 'EXP'],
        };

        $typeName = $typeMeta['name'];
        $categoryId = $this->resolveAccountCategoryId($companyId, $typeMeta['category_code'], $cache);
        $cacheKey = 'type:' . $companyId . ':' . $typeName;

        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $type = AccountType::firstOrCreate(
            ['name' => $typeName, 'created_by' => $companyId],
            [
                'category_id' => $categoryId,
                'code' => 'C' . $catalog->class_number,
                'normal_balance' => $catalog->normal_balance,
                'description' => 'PGC-MZ Classe ' . $catalog->class_number,
                'is_active' => true,
                'is_system_type' => true,
                'creator_id' => $companyId,
            ]
        );

        // Keep existing system types aligned with the intended category.
        if ((int) $type->category_id !== (int) $categoryId) {
            $type->category_id = $categoryId;
            $type->save();
        }

        $cache[$cacheKey] = $type->id;

        return $type->id;
    }

    /**
     * Resolve/create the account category required by an account type.
     */
    private function resolveAccountCategoryId(int $companyId, string $categoryCode, array &$cache): int
    {
        $cacheKey = 'category:' . $companyId . ':' . $categoryCode;

        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $category = AccountCategory::where('created_by', $companyId)
            ->where('code', $categoryCode)
            ->first();

        if (!$category) {
            $definition = collect(AccountUtility::accountCategoryDefinitions('pt'))
                ->firstWhere('code', $categoryCode);

            $fallbackType = match ($categoryCode) {
                'AST' => 'assets',
                'LIB' => 'liabilities',
                'EQT' => 'equity',
                'REV' => 'revenue',
                default => 'expenses',
            };

            $category = AccountCategory::create([
                'name' => $definition['name'] ?? $categoryCode,
                'code' => $categoryCode,
                'type' => $definition['type'] ?? $fallbackType,
                'description' => $definition['description'] ?? 'Categoria criada automaticamente pelo setup SCE.',
                'is_active' => true,
                'creator_id' => $companyId,
                'created_by' => $companyId,
            ]);
        }

        $cache[$cacheKey] = $category->id;

        return $category->id;
    }

    private function resolveClassLabel(int $class): string
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

    /**
     * @param array<int, string> $codes
     */
    private function summariseCodes(array $codes, int $limit = 5): string
    {
        $sample = array_slice($codes, 0, $limit);
        $summary = implode(', ', $sample);

        if (count($codes) > $limit) {
            $summary .= '…';
        }

        return $summary;
    }
}
