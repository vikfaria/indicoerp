<?php

namespace App\Services\AssistantActivation;

use App\Models\CompanyFiscalProfile;
use App\Models\FiscalDocumentSeries;
use App\Models\Plan;
use App\Models\StockCostLayer;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\FiscalValidationService;
use App\Services\MozambiquePayrollTaxService;
use App\Services\AssistantActivation\AssistantActivationCacheService;
use App\Services\AssistantActivation\TenantFeatureOverrideService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\MozTaxAccountMapping;
use Workdo\Hrm\Models\Payroll;
use Workdo\Pos\Models\Pos;

class PlanFeatureResolver
{
    public function __construct(
        private readonly FeatureCatalogService $featureCatalogService,
        private readonly ModuleFeatureBridgeService $moduleFeatureBridgeService,
        private readonly ModuleCatalogService $moduleCatalogService,
        private readonly FiscalValidationService $fiscalValidationService,
        private readonly MozambiquePayrollTaxService $mozambiquePayrollTaxService,
        private readonly TenantFeatureOverrideService $tenantFeatureOverrideService,
        private readonly AssistantActivationCacheService $cacheService
    ) {
    }

    public function catalogVersion(): string
    {
        return $this->featureCatalogService->catalogVersion();
    }

    public function features(): array
    {
        return $this->featureCatalogService->features();
    }

    public function indexedFeatures(): array
    {
        return $this->featureCatalogService->indexedFeatures();
    }

    public function find(string $featureKey): ?array
    {
        return $this->featureCatalogService->find($featureKey);
    }

    public function buildCatalogReport(): array
    {
        return $this->featureCatalogService->buildReport();
    }

    public function buildReport(?User $user = null): array
    {
        $resolvedFeatures = array_map(
            fn (array $feature) => $this->resolve($feature['key'], $user),
            $this->features()
        );

        $stateCounts = [];
        foreach ($resolvedFeatures as $feature) {
            $state = $feature['state'];
            $stateCounts[$state] = ($stateCounts[$state] ?? 0) + 1;
        }

        return [
            'meta' => [
                'catalog_version' => $this->catalogVersion(),
                'generated_at' => now()->toIso8601String(),
            ],
            'summary' => [
                'features_total' => count($resolvedFeatures),
                'active_features_total' => $stateCounts['active'] ?? 0,
                'locked_features_total' => $stateCounts['locked'] ?? 0,
                'hidden_features_total' => $stateCounts['hidden'] ?? 0,
                'addon_features_total' => $stateCounts['addon'] ?? 0,
            ],
            'features' => $resolvedFeatures,
        ];
    }

    public function resolve(string $featureKey, ?User $user = null): array
    {
        $feature = $this->find($featureKey);

        if ($feature === null) {
            return $this->buildMissingFeatureResolution($featureKey, $user);
        }

        $subjectUser = $user ?? Auth::user();

        if (! $subjectUser) {
            return $this->buildNoUserResolution($feature);
        }

        return $this->cacheService->rememberFeature(
            $featureKey,
            $subjectUser,
            function () use ($feature, $subjectUser): array {
                if ($subjectUser->isSuperAdminUser()) {
                    return $this->buildSuperAdminResolution($feature, $subjectUser);
                }

                $tenantUser = $this->resolveTenantUser($subjectUser);
                if (! $tenantUser) {
                    return $this->buildMissingTenantResolution($feature, $subjectUser);
                }

                $subscription = $this->resolveSubscriptionState($tenantUser);
                $override = $this->tenantFeatureOverrideService->resolveFeatureOverride($tenantUser, $feature['key']);

                if ($override !== null) {
                    return $this->buildOverrideResolution(
                        $feature,
                        $subjectUser,
                        $tenantUser,
                        $subscription,
                        $override
                    );
                }

                if (in_array($subscription['state'], ['inactive', 'expired'], true)) {
                    return $this->buildBlockedResolution(
                        $feature,
                        $subjectUser,
                        $tenantUser,
                        $subscription,
                        [],
                        ['subscription_' . $subscription['state']],
                        'locked'
                    );
                }

                $permissions = $subjectUser->getAllPermissions()
                    ->pluck('name')
                    ->map(fn ($permission) => trim((string) $permission))
                    ->filter(fn (string $permission) => $permission !== '')
                    ->unique()
                    ->values()
                    ->all();

                $activeModules = array_values(array_filter(array_map(
                    fn ($module) => trim((string) $module),
                    (array) ActivatedModule($tenantUser->id)
                )));

                $moduleResolution = $this->resolveModuleState($feature['modules'], $activeModules);
                if (!empty($moduleResolution['unavailable_modules'])) {
                    return $this->buildResolution(
                        $feature,
                        $subjectUser,
                        $tenantUser,
                        $subscription,
                        $moduleResolution,
                        ['module_unavailable'],
                        'hidden'
                    );
                }

                if (!empty($moduleResolution['addon_modules'])) {
                    return $this->buildResolution(
                        $feature,
                        $subjectUser,
                        $tenantUser,
                        $subscription,
                        $moduleResolution,
                        ['addon_required'],
                        'addon'
                    );
                }

                $permissionResolution = $this->resolvePermissionState(
                    $feature['permissions_all'],
                    $feature['permissions_any'],
                    $permissions
                );

                $configResolution = $this->resolveConfigState($feature['config_keys'], $tenantUser);

                $blockingReasons = array_values(array_merge(
                    $permissionResolution['missing_permissions'] ? ['permission_missing'] : [],
                    $configResolution['missing_config_keys'] ? ['config_missing'] : []
                ));

                $state = empty($blockingReasons) ? 'active' : 'locked';

                return $this->buildResolution(
                    $feature,
                    $subjectUser,
                    $tenantUser,
                    $subscription,
                    $moduleResolution,
                    $blockingReasons,
                    $state,
                    $permissionResolution,
                    $configResolution
                );
            }
        );
    }

    private function buildMissingFeatureResolution(string $featureKey, ?User $user): array
    {
        return [
            'key' => $featureKey,
            'label' => $this->humanizeKey($featureKey),
            'domain' => null,
            'state' => 'hidden',
            'reasons' => ['feature_unknown'],
            'subject_user_id' => $user?->id,
            'tenant_user_id' => null,
            'subscription_state' => 'inactive',
            'subscription' => null,
            'modules' => [],
            'missing_modules' => [],
            'addon_modules' => [],
            'unavailable_modules' => [],
            'permissions_all' => [],
            'permissions_any' => [],
            'missing_permissions' => [],
            'config_keys' => [],
            'missing_config_keys' => [],
        ];
    }

    private function buildNoUserResolution(array $feature): array
    {
        return [
            'key' => $feature['key'],
            'label' => $feature['label'],
            'domain' => $feature['domain'],
            'state' => 'hidden',
            'reasons' => ['no_user_context'],
            'subject_user_id' => null,
            'tenant_user_id' => null,
            'subscription_state' => 'inactive',
            'subscription' => null,
            'modules' => $this->moduleChecks($feature['modules'], []),
            'missing_modules' => $feature['modules'],
            'addon_modules' => [],
            'unavailable_modules' => $feature['modules'],
            'permissions_all' => $feature['permissions_all'],
            'permissions_any' => $feature['permissions_any'],
            'missing_permissions' => array_values(array_unique(array_merge($feature['permissions_all'], $feature['permissions_any']))),
            'config_keys' => $feature['config_keys'],
            'missing_config_keys' => $feature['config_keys'],
        ];
    }

    private function buildSuperAdminResolution(array $feature, User $user): array
    {
        return [
            'key' => $feature['key'],
            'label' => $feature['label'],
            'domain' => $feature['domain'],
            'state' => 'active',
            'reasons' => ['superadmin_bypass'],
            'subject_user_id' => $user->id,
            'tenant_user_id' => $user->id,
            'subscription_state' => 'superadmin',
            'subscription' => [
                'plan_id' => null,
                'plan_name' => 'Super Admin',
                'plan_family' => 'enterprise',
                'plan_expire_date' => null,
                'trial_expire_date' => null,
            ],
            'modules' => $this->moduleChecks($feature['modules'], ActivatedModule($user->id)),
            'missing_modules' => [],
            'addon_modules' => [],
            'unavailable_modules' => [],
            'permissions_all' => $feature['permissions_all'],
            'permissions_any' => $feature['permissions_any'],
            'missing_permissions' => [],
            'config_keys' => $feature['config_keys'],
            'missing_config_keys' => [],
        ];
    }

    private function buildMissingTenantResolution(array $feature, User $user): array
    {
        return [
            'key' => $feature['key'],
            'label' => $feature['label'],
            'domain' => $feature['domain'],
            'state' => 'hidden',
            'reasons' => ['tenant_context_missing'],
            'subject_user_id' => $user->id,
            'tenant_user_id' => null,
            'subscription_state' => 'inactive',
            'subscription' => null,
            'modules' => $this->moduleChecks($feature['modules'], []),
            'missing_modules' => $feature['modules'],
            'addon_modules' => [],
            'unavailable_modules' => $feature['modules'],
            'permissions_all' => $feature['permissions_all'],
            'permissions_any' => $feature['permissions_any'],
            'missing_permissions' => array_values(array_unique(array_merge($feature['permissions_all'], $feature['permissions_any']))),
            'config_keys' => $feature['config_keys'],
            'missing_config_keys' => $feature['config_keys'],
        ];
    }

    /**
     * @param array<int, string> $permissions
     * @return array<string, mixed>
     */
    private function resolveSubscriptionState(User $tenantUser): array
    {
        if ($tenantUser->isSuperAdminUser()) {
            return [
                'state' => 'superadmin',
                'plan_id' => null,
                'plan_name' => 'Super Admin',
                'plan_family' => 'enterprise',
                'plan_expire_date' => null,
                'trial_expire_date' => null,
            ];
        }

        if ((int) $tenantUser->active_plan <= 0) {
            return [
                'state' => 'inactive',
                'plan_id' => null,
                'plan_name' => null,
                'plan_family' => 'custom',
                'plan_expire_date' => null,
                'trial_expire_date' => $tenantUser->trial_expire_date,
            ];
        }

        if ($tenantUser->plan_expire_date && now()->gt($tenantUser->plan_expire_date)) {
            $plan = Plan::find($tenantUser->active_plan);

            return [
                'state' => 'expired',
                'plan_id' => $tenantUser->active_plan,
                'plan_name' => $plan?->name,
                'plan_family' => $this->normalizePlanFamily($plan?->name),
                'plan_expire_date' => $tenantUser->plan_expire_date,
                'trial_expire_date' => $tenantUser->trial_expire_date,
            ];
        }

        $plan = Plan::find($tenantUser->active_plan);

        return [
            'state' => 'active',
            'plan_id' => $tenantUser->active_plan,
            'plan_name' => $plan?->name,
            'plan_family' => $this->normalizePlanFamily($plan?->name),
            'plan_expire_date' => $tenantUser->plan_expire_date,
            'trial_expire_date' => $tenantUser->trial_expire_date,
        ];
    }

    /**
     * @param array<int, string> $requiredModules
     * @param array<int, string> $activeModules
     * @return array<string, array<int, string>|array<int, array<string, string>>>
     */
    private function resolveModuleState(array $requiredModules, array $activeModules): array
    {
        $moduleHelper = new \App\Classes\Module();
        $checks = [];
        $missingModules = [];
        $addonModules = [];
        $unavailableModules = [];

        foreach ($requiredModules as $module) {
            $module = trim((string) $module);
            if ($module === '') {
                continue;
            }

            $exists = $moduleHelper->has($module);
            $enabled = $exists && $moduleHelper->isEnabled($module);
            $active = in_array($module, $activeModules, true);
            $featureKeys = $this->moduleFeatureBridgeService->featureKeysForModuleReference($module);
            $catalogModuleKeys = $this->moduleFeatureBridgeService->moduleKeysForReference($module);

            if ($active) {
                $state = 'active';
            } elseif ($enabled) {
                $state = 'addon';
                $addonModules[] = $module;
                $missingModules[] = $module;
            } else {
                $state = 'hidden';
                $unavailableModules[] = $module;
                $missingModules[] = $module;
            }

            $checks[] = [
                'module' => $module,
                'exists' => $exists,
                'enabled' => $enabled,
                'active' => $active,
                'state' => $state,
                'catalog_module_keys' => $catalogModuleKeys,
                'feature_keys' => $featureKeys,
                'feature_count' => count($featureKeys),
            ];
        }

        return [
            'checks' => $checks,
            'missing_modules' => array_values(array_unique($missingModules)),
            'addon_modules' => array_values(array_unique($addonModules)),
            'unavailable_modules' => array_values(array_unique($unavailableModules)),
        ];
    }

    /**
     * @param array<int, string> $requiredPermissionsAll
     * @param array<int, string> $requiredPermissionsAny
     * @param array<int, string> $permissions
     * @return array<string, array<int, string>|bool>
     */
    private function resolvePermissionState(array $requiredPermissionsAll, array $requiredPermissionsAny, array $permissions): array
    {
        $requiredPermissionsAll = array_values(array_filter(array_map('trim', $requiredPermissionsAll)));
        $requiredPermissionsAny = array_values(array_filter(array_map('trim', $requiredPermissionsAny)));
        $permissions = array_values(array_filter(array_map('trim', $permissions)));

        $missingAll = array_values(array_diff($requiredPermissionsAll, $permissions));
        $missingAny = [];
        $hasAny = true;

        if (!empty($requiredPermissionsAny)) {
            $hasAny = count(array_intersect($requiredPermissionsAny, $permissions)) > 0;

            if (! $hasAny) {
                $missingAny = $requiredPermissionsAny;
            }
        }

        return [
            'satisfied' => empty($missingAll) && $hasAny,
            'missing_permissions' => array_values(array_unique(array_merge($missingAll, $missingAny))),
            'granted_permissions' => array_values(array_intersect(array_merge($requiredPermissionsAll, $requiredPermissionsAny), $permissions)),
        ];
    }

    /**
     * @param array<int, string> $requiredConfigKeys
     * @return array<string, array<int, string>|array<int, array<string, mixed>>|bool>
     */
    private function resolveConfigState(array $requiredConfigKeys, ?User $tenantUser): array
    {
        $requiredConfigKeys = array_values(array_filter(array_map('trim', $requiredConfigKeys)));
        $checks = [];
        $missingConfigKeys = [];

        foreach ($requiredConfigKeys as $configKey) {
            $check = $this->evaluateConfigKey($configKey, $tenantUser);
            $checks[] = $check;

            if (! $check['satisfied']) {
                $missingConfigKeys[] = $configKey;
            }
        }

        return [
            'satisfied' => empty($missingConfigKeys),
            'missing_config_keys' => array_values(array_unique($missingConfigKeys)),
            'checks' => $checks,
        ];
    }

    private function evaluateConfigKey(string $configKey, ?User $tenantUser): array
    {
        $companyId = $tenantUser?->id;
        $label = $this->labelForConfigKey($configKey);

        if ($companyId === null) {
            return [
                'key' => $configKey,
                'label' => $label,
                'satisfied' => false,
                'reason' => 'tenant_missing',
                'details' => null,
            ];
        }

        return match ($configKey) {
            'fiscal_profile' => $this->checkFiscalProfile($companyId, $label),
            'accounting_period' => $this->checkAccountingPeriod($companyId, $label),
            'document_series' => $this->checkDocumentSeries($companyId, $label),
            'tax_profile' => $this->checkTaxProfile($companyId, $label),
            'chart_of_accounts' => $this->checkChartOfAccounts($companyId, $label),
            'payroll_calendar' => $this->checkPayrollCalendar($companyId, $label),
            'payroll_contributions' => $this->checkPayrollContributions($companyId, $label),
            'warehouses' => $this->checkWarehouses($companyId, $label),
            'initial_stock' => $this->checkInitialStock($companyId, $label),
            'fifo_layers' => $this->checkFifoLayers($companyId, $label),
            'pos_registers' => $this->checkPosRegisters($companyId, $label),
            default => [
                'key' => $configKey,
                'label' => $label,
                'satisfied' => false,
                'reason' => 'unknown_config_key',
                'details' => null,
            ],
        };
    }

    private function checkFiscalProfile(int $companyId, string $label): array
    {
        if (!Schema::hasTable('company_fiscal_profiles')) {
            return [
                'key' => 'fiscal_profile',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'table_missing',
                'details' => null,
            ];
        }

        $profile = CompanyFiscalProfile::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (! $profile) {
            return [
                'key' => 'fiscal_profile',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_profile',
                'details' => null,
            ];
        }

        $missingFields = [];
        foreach (['nuit', 'legal_name', 'fiscal_regime', 'accounting_framework'] as $field) {
            if (blank($profile->{$field} ?? null)) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            return [
                'key' => 'fiscal_profile',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_fields',
                'details' => $missingFields,
            ];
        }

        return [
            'key' => 'fiscal_profile',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'profile_id' => $profile->id,
                'nuit' => $profile->nuit,
                'fiscal_regime' => $profile->fiscal_regime,
                'accounting_framework' => $profile->accounting_framework,
            ],
        ];
    }

    private function checkAccountingPeriod(int $companyId, string $label): array
    {
        try {
            $period = $this->fiscalValidationService->validatePeriodOpen(now()->toDateString(), $companyId);
        } catch (\Throwable $exception) {
            return [
                'key' => 'accounting_period',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'period_not_open',
                'details' => $exception->getMessage(),
            ];
        }

        return [
            'key' => 'accounting_period',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'period_id' => $period->id ?? null,
                'period_name' => $period->period_name ?? null,
                'status' => $period->status ?? 'open',
            ],
        ];
    }

    private function checkDocumentSeries(int $companyId, string $label): array
    {
        if (!Schema::hasTable('fiscal_document_series')) {
            return [
                'key' => 'document_series',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'table_missing',
                'details' => null,
            ];
        }

        $today = now()->toDateString();
        $series = FiscalDocumentSeries::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereHas('fiscalDocumentType', function ($query): void {
                $query->where('is_active', true)
                    ->where('category', 'sales');
            })
            ->where(function ($query) use ($today): void {
                $query->whereNull('valid_from')
                    ->orWhereDate('valid_from', '<=', $today);
            })
            ->where(function ($query) use ($today): void {
                $query->whereNull('valid_to')
                    ->orWhereDate('valid_to', '>=', $today);
            })
            ->orderByDesc('id')
            ->first();

        if (! $series) {
            return [
                'key' => 'document_series',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_series',
                'details' => null,
            ];
        }

        return [
            'key' => 'document_series',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'series_id' => $series->id,
                'series_code' => $series->series_code,
                'document_type' => $series->fiscalDocumentType?->code,
                'valid_from' => $series->valid_from?->toDateString(),
                'valid_to' => $series->valid_to?->toDateString(),
            ],
        ];
    }

    private function checkTaxProfile(int $companyId, string $label): array
    {
        if (!Schema::hasTable('mz_tax_account_mappings')) {
            return [
                'key' => 'tax_profile',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'table_missing',
                'details' => null,
            ];
        }

        $today = now()->toDateString();
        $mapping = MozTaxAccountMapping::query()
            ->where('created_by', $companyId)
            ->where('is_active', true)
            ->whereNotNull('vat_output_account_id')
            ->whereNotNull('vat_input_account_id')
            ->where(function ($query) use ($today): void {
                $query->whereNull('effective_from')
                    ->orWhereDate('effective_from', '<=', $today);
            })
            ->where(function ($query) use ($today): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $today);
            })
            ->orderByDesc('id')
            ->first();

        if (! $mapping) {
            return [
                'key' => 'tax_profile',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_mapping',
                'details' => null,
            ];
        }

        return [
            'key' => 'tax_profile',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'mapping_id' => $mapping->id,
                'vat_output_account_id' => $mapping->vat_output_account_id,
                'vat_input_account_id' => $mapping->vat_input_account_id,
            ],
        ];
    }

    private function checkChartOfAccounts(int $companyId, string $label): array
    {
        if (!Schema::hasTable('chart_of_accounts')) {
            return [
                'key' => 'chart_of_accounts',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'table_missing',
                'details' => null,
            ];
        }

        $hasAccounts = ChartOfAccount::query()
            ->where('created_by', $companyId)
            ->exists();

        if (! $hasAccounts) {
            return [
                'key' => 'chart_of_accounts',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_accounts',
                'details' => null,
            ];
        }

        return [
            'key' => 'chart_of_accounts',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => null,
        ];
    }

    private function checkPayrollCalendar(int $companyId, string $label): array
    {
        if (!Schema::hasTable('payrolls')) {
            return [
                'key' => 'payroll_calendar',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'table_missing',
                'details' => null,
            ];
        }

        $payroll = Payroll::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhere('creator_id', $companyId);
            })
            ->where(function ($query): void {
                $query->whereNotNull('pay_date')
                    ->orWhereNotNull('pay_period_start')
                    ->orWhereNotNull('pay_period_end');
            })
            ->orderByDesc('id')
            ->first();

        if (! $payroll) {
            return [
                'key' => 'payroll_calendar',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_payrolls',
                'details' => null,
            ];
        }

        return [
            'key' => 'payroll_calendar',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'payroll_id' => $payroll->id,
                'title' => $payroll->title,
                'pay_date' => $payroll->pay_date?->toDateString(),
                'pay_period_start' => $payroll->pay_period_start?->toDateString(),
                'pay_period_end' => $payroll->pay_period_end?->toDateString(),
            ],
        ];
    }

    private function checkPayrollContributions(int $companyId, string $label): array
    {
        $today = now()->toDateString();
        $irps = $this->mozambiquePayrollTaxService->calculateIrps(50000, $companyId, $today);
        $inss = $this->mozambiquePayrollTaxService->calculateInss(50000, $companyId, $today);

        $missingItems = [];
        $reason = null;

        if (! (bool) ($irps['configured'] ?? false)) {
            $missingItems[] = 'irps_table';
            $reason = match ((string) ($irps['rule'] ?? '')) {
                'table_missing' => 'missing_irps_table',
                'no_matching_bracket' => 'missing_irps_brackets',
                default => 'missing_contributions',
            };
        }

        if (! (bool) ($inss['configured'] ?? false)) {
            $missingItems[] = 'inss_rate';
            $reason ??= 'missing_inss_rate';
        }

        if ($missingItems !== []) {
            return [
                'key' => 'payroll_contributions',
                'label' => $label,
                'satisfied' => false,
                'reason' => $reason ?? 'missing_contributions',
                'details' => [
                    'missing_items' => array_values(array_unique($missingItems)),
                    'irps_rule' => $irps['rule'] ?? null,
                    'irps_table_id' => $irps['table_id'] ?? null,
                    'irps_bracket_id' => $irps['bracket_id'] ?? null,
                    'inss_rate_id' => $inss['rate_id'] ?? null,
                ],
            ];
        }

        return [
            'key' => 'payroll_contributions',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'irps_table_id' => $irps['table_id'] ?? null,
                'irps_bracket_id' => $irps['bracket_id'] ?? null,
                'inss_rate_id' => $inss['rate_id'] ?? null,
                'employee_rate' => $inss['employee_rate'] ?? null,
                'employer_rate' => $inss['employer_rate'] ?? null,
            ],
        ];
    }

    private function checkWarehouses(int $companyId, string $label): array
    {
        if (!Schema::hasTable('warehouses')) {
            return [
                'key' => 'warehouses',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'table_missing',
                'details' => null,
            ];
        }

        $warehouse = Warehouse::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhere('creator_id', $companyId);
            })
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();

        if (! $warehouse) {
            return [
                'key' => 'warehouses',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_warehouses',
                'details' => null,
            ];
        }

        return [
            'key' => 'warehouses',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'warehouse_id' => $warehouse->id,
                'warehouse_name' => $warehouse->name,
                'is_active' => (bool) $warehouse->is_active,
            ],
        ];
    }

    private function checkInitialStock(int $companyId, string $label): array
    {
        if (!Schema::hasTable('stock_movements')) {
            return [
                'key' => 'initial_stock',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'table_missing',
                'details' => null,
            ];
        }

        $movement = StockMovement::query()
            ->where('company_id', $companyId)
            ->where('quantity', '>', 0)
            ->orderByDesc('id')
            ->first();

        if (! $movement) {
            return [
                'key' => 'initial_stock',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_initial_stock',
                'details' => null,
            ];
        }

        return [
            'key' => 'initial_stock',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'movement_id' => $movement->id,
                'product_id' => $movement->product_id,
                'quantity' => (float) $movement->quantity,
                'movement_type' => $movement->movement_type,
                'warehouse_code' => $movement->warehouse_code,
            ],
        ];
    }

    private function checkFifoLayers(int $companyId, string $label): array
    {
        if (!Schema::hasTable('stock_cost_layers')) {
            return [
                'key' => 'fifo_layers',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'table_missing',
                'details' => null,
            ];
        }

        $layer = StockCostLayer::query()
            ->where('company_id', $companyId)
            ->where('remaining_quantity', '>', 0)
            ->where('is_exhausted', false)
            ->orderByDesc('id')
            ->first();

        if (! $layer) {
            return [
                'key' => 'fifo_layers',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_fifo_layers',
                'details' => null,
            ];
        }

        return [
            'key' => 'fifo_layers',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'layer_id' => $layer->id,
                'movement_id' => $layer->stock_movement_id,
                'product_id' => $layer->product_id,
                'remaining_quantity' => (float) $layer->remaining_quantity,
                'unit_cost' => (float) $layer->unit_cost,
            ],
        ];
    }

    private function checkPosRegisters(int $companyId, string $label): array
    {
        if (!Schema::hasTable('pos')) {
            return [
                'key' => 'pos_registers',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'table_missing',
                'details' => null,
            ];
        }

        $pos = Pos::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhere('creator_id', $companyId);
            })
            ->whereNotNull('warehouse_id')
            ->where(function ($query): void {
                $query->whereNull('is_cancelled')->orWhere('is_cancelled', false);
            })
            ->orderByDesc('id')
            ->first();

        if (! $pos) {
            return [
                'key' => 'pos_registers',
                'label' => $label,
                'satisfied' => false,
                'reason' => 'missing_pos_registers',
                'details' => null,
            ];
        }

        return [
            'key' => 'pos_registers',
            'label' => $label,
            'satisfied' => true,
            'reason' => 'ok',
            'details' => [
                'pos_id' => $pos->id,
                'sale_number' => $pos->sale_number,
                'warehouse_id' => $pos->warehouse_id,
                'status' => $pos->status,
                'is_cancelled' => (bool) $pos->is_cancelled,
            ],
        ];
    }

    /**
     * @param array<int, string> $requiredModules
     * @param array<int, string> $activeModules
     * @return array<int, array<string, mixed>>
     */
    private function moduleChecks(array $requiredModules, array $activeModules): array
    {
        return $this->resolveModuleState($requiredModules, $activeModules)['checks'];
    }

    /**
     * @param array<int, string> $permissions
     * @return array<string, mixed>
     */
    private function resolveTenantUser(User $user): ?User
    {
        if (in_array($user->type, ['company', 'superadmin', 'super admin'], true)) {
            return $user;
        }

        return $user->createdBy;
    }

    /**
     * @param array<string, mixed> $feature
     * @param array<string, mixed> $subscription
     * @param array<string, mixed> $moduleResolution
     * @param array<string, mixed> $permissionResolution
     * @param array<string, mixed> $configResolution
     */
    private function buildResolution(
        array $feature,
        User $subjectUser,
        User $tenantUser,
        array $subscription,
        array $moduleResolution,
        array $reasons,
        string $state,
        array $permissionResolution = [],
        array $configResolution = []
    ): array {
        $finalReasons = $reasons;

        if (!empty($permissionResolution['missing_permissions'] ?? [])) {
            $finalReasons = array_values(array_unique(array_merge(
                $finalReasons,
                array_map(fn (string $permission) => 'permission_missing:' . $permission, $permissionResolution['missing_permissions'])
            )));
        }

        if (!empty($configResolution['missing_config_keys'] ?? [])) {
            $finalReasons = array_values(array_unique(array_merge(
                $finalReasons,
                array_map(fn (string $configKey) => 'config_missing:' . $configKey, $configResolution['missing_config_keys'])
            )));
        }

        return [
            'key' => $feature['key'],
            'label' => $feature['label'],
            'domain' => $feature['domain'],
            'state' => $state,
            'reasons' => $finalReasons,
            'subject_user_id' => $subjectUser->id,
            'tenant_user_id' => $tenantUser->id,
            'subscription_state' => $subscription['state'],
            'subscription' => $subscription,
            'plan_family' => $subscription['plan_family'] ?? 'custom',
            'plan_name' => $subscription['plan_name'] ?? null,
            'modules' => $moduleResolution['checks'] ?? [],
            'missing_modules' => $moduleResolution['missing_modules'] ?? [],
            'addon_modules' => $moduleResolution['addon_modules'] ?? [],
            'unavailable_modules' => $moduleResolution['unavailable_modules'] ?? [],
            'permissions_all' => $feature['permissions_all'],
            'permissions_any' => $feature['permissions_any'],
            'missing_permissions' => $permissionResolution['missing_permissions'] ?? [],
            'granted_permissions' => $permissionResolution['granted_permissions'] ?? [],
            'config_keys' => $feature['config_keys'],
            'config_checks' => $configResolution['checks'] ?? [],
            'missing_config_keys' => $configResolution['missing_config_keys'] ?? [],
            'route_prefixes' => $feature['route_prefixes'],
            'menu_groups' => $feature['menu_groups'],
            'notes' => $feature['notes'],
        ];
    }

    private function buildBlockedResolution(
        array $feature,
        User $subjectUser,
        User $tenantUser,
        array $subscription,
        array $moduleResolution,
        array $reasons,
        string $state
    ): array {
        return [
            'key' => $feature['key'],
            'label' => $feature['label'],
            'domain' => $feature['domain'],
            'state' => $state,
            'reasons' => $reasons,
            'subject_user_id' => $subjectUser->id,
            'tenant_user_id' => $tenantUser->id,
            'subscription_state' => $subscription['state'],
            'subscription' => $subscription,
            'plan_family' => $subscription['plan_family'] ?? 'custom',
            'plan_name' => $subscription['plan_name'] ?? null,
            'modules' => $moduleResolution['checks'] ?? [],
            'missing_modules' => $moduleResolution['missing_modules'] ?? [],
            'addon_modules' => $moduleResolution['addon_modules'] ?? [],
            'unavailable_modules' => $moduleResolution['unavailable_modules'] ?? [],
            'permissions_all' => $feature['permissions_all'],
            'permissions_any' => $feature['permissions_any'],
            'missing_permissions' => [],
            'granted_permissions' => [],
            'config_keys' => $feature['config_keys'],
            'config_checks' => [],
            'missing_config_keys' => [],
            'route_prefixes' => $feature['route_prefixes'],
            'menu_groups' => $feature['menu_groups'],
            'notes' => $feature['notes'],
        ];
    }

    private function buildOverrideResolution(
        array $feature,
        User $subjectUser,
        User $tenantUser,
        array $subscription,
        array $override
    ): array {
        $activeModules = array_values(array_filter(array_map(
            fn ($module) => trim((string) $module),
            (array) ActivatedModule($tenantUser->id)
        )));

        $moduleResolution = $this->resolveModuleState($feature['modules'], $activeModules);

        return [
            'key' => $feature['key'],
            'label' => $feature['label'],
            'domain' => $feature['domain'],
            'state' => 'active',
            'reasons' => ['tenant_override'],
            'subject_user_id' => $subjectUser->id,
            'tenant_user_id' => $tenantUser->id,
            'subscription_state' => $subscription['state'],
            'subscription' => $subscription,
            'plan_family' => $subscription['plan_family'] ?? 'custom',
            'plan_name' => $subscription['plan_name'] ?? null,
            'modules' => $moduleResolution['checks'] ?? [],
            'missing_modules' => [],
            'addon_modules' => [],
            'unavailable_modules' => [],
            'permissions_all' => $feature['permissions_all'],
            'permissions_any' => $feature['permissions_any'],
            'missing_permissions' => [],
            'granted_permissions' => array_values(array_unique(array_merge(
                $feature['permissions_all'],
                $feature['permissions_any']
            ))),
            'config_keys' => $feature['config_keys'],
            'config_checks' => [],
            'missing_config_keys' => [],
            'route_prefixes' => $feature['route_prefixes'],
            'menu_groups' => $feature['menu_groups'],
            'notes' => $feature['notes'],
            'override' => $override,
        ];
    }

    private function normalizeFeature(array $feature): array
    {
        return [
            'key' => (string) Arr::get($feature, 'key', ''),
            'label' => (string) Arr::get($feature, 'label', ''),
            'domain' => (string) Arr::get($feature, 'domain', ''),
            'modules' => array_values(array_filter(array_map(
                fn ($module) => trim((string) $module),
                (array) Arr::get($feature, 'modules', [])
            ))),
            'permissions_all' => array_values(array_filter(array_map(
                fn ($permission) => trim((string) $permission),
                (array) Arr::get($feature, 'permissions_all', [])
            ))),
            'permissions_any' => array_values(array_filter(array_map(
                fn ($permission) => trim((string) $permission),
                (array) Arr::get($feature, 'permissions_any', [])
            ))),
            'config_keys' => array_values(array_filter(array_map(
                fn ($configKey) => trim((string) $configKey),
                (array) Arr::get($feature, 'config_keys', [])
            ))),
            'route_prefixes' => array_values(array_filter(array_map(
                fn ($prefix) => trim((string) $prefix),
                (array) Arr::get($feature, 'route_prefixes', [])
            ))),
            'menu_groups' => array_values(array_filter(array_map(
                fn ($group) => trim((string) $group),
                (array) Arr::get($feature, 'menu_groups', [])
            ))),
            'notes' => (string) Arr::get($feature, 'notes', ''),
        ];
    }

    private function normalizePlanFamily(?string $planName): string
    {
        $normalized = $this->normalizeLabel($planName);

        foreach ((array) config('assistant_activation.plan_families', []) as $familyKey => $family) {
            $aliases = array_merge(
                [$familyKey],
                (array) ($family['aliases'] ?? []),
                [(string) ($family['label'] ?? '')]
            );

            foreach ($aliases as $alias) {
                $aliasNormalized = $this->normalizeLabel($alias);

                if ($aliasNormalized !== '' && $normalized !== '' && str_contains($normalized, $aliasNormalized)) {
                    return (string) $familyKey;
                }
            }
        }

        return 'custom';
    }

    private function normalizeLabel(?string $label): string
    {
        return Str::of((string) $label)
            ->lower()
            ->replace(['_', '-', '/'], ' ')
            ->squish()
            ->toString();
    }

    private function labelForConfigKey(string $configKey): string
    {
        return match ($configKey) {
            'fiscal_profile' => 'Perfil fiscal',
            'accounting_period' => 'Período contabilístico',
            'document_series' => 'Séries documentais',
            'tax_profile' => 'Perfil fiscal de impostos',
            'chart_of_accounts' => 'Plano de contas',
            'payroll_calendar' => 'Calendário salarial',
            'payroll_contributions' => 'Contribuições da folha',
            'warehouses' => 'Armazéns',
            'initial_stock' => 'Stock inicial',
            'fifo_layers' => 'Layers FIFO',
            'pos_registers' => 'Registos POS',
            default => Str::of($configKey)->replace(['_', '-'], ' ')->title()->toString(),
        };
    }

    private function labelForDomain(string $domainKey): string
    {
        $priorityDomains = (array) config('assistant_activation.priority_domains', []);
        $label = data_get($priorityDomains, $domainKey . '.label');

        if (is_string($label) && trim($label) !== '') {
            return $label;
        }

        return $this->humanizeKey($domainKey);
    }

    private function humanizeKey(string $key): string
    {
        return Str::of($key)->replace(['.', '_', '-'], ' ')->title()->toString();
    }
}
