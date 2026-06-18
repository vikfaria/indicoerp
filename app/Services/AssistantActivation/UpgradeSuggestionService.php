<?php

namespace App\Services\AssistantActivation;

use App\Models\Plan;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class UpgradeSuggestionService
{
    private const ACTION_NO_ACTION = 'no_action';
    private const ACTION_ACTIVATE_ADDON = 'activate_addon';
    private const ACTION_COMPLETE_CONFIGURATION = 'complete_configuration';
    private const ACTION_CONTACT_SUPPORT = 'contact_support';
    private const ACTION_GRANT_PERMISSION = 'grant_permission';
    private const ACTION_INSTALL_MODULE = 'install_module';
    private const ACTION_REDUCE_USAGE = 'reduce_usage';
    private const ACTION_RENEW_SUBSCRIPTION = 'renew_subscription';
    private const ACTION_SELECT_COMPANY = 'select_company';
    private const ACTION_UPGRADE_PLAN = 'upgrade_plan';

    public function __construct(
        private readonly PlanFeatureResolver $featureResolver,
        private readonly PlanLimitResolver $limitResolver,
        private readonly ModuleCatalogService $moduleCatalogService,
        private readonly ModuleFeatureBridgeService $moduleFeatureBridgeService,
        private readonly PlanLimitMatrixService $limitMatrixService
    ) {
    }

    public function suggestFeature(string $featureKey, ?User $user = null, ?Collection $plans = null): array
    {
        return $this->suggestFromFeatureResolution(
            $this->featureResolver->resolve($featureKey, $user),
            $plans
        );
    }

    public function suggestLimit(
        string $limitKey,
        ?User $user = null,
        ?CarbonInterface $referenceDate = null,
        ?Collection $plans = null
    ): array {
        return $this->suggestFromLimitResolution(
            $this->limitResolver->resolve($limitKey, $user, $referenceDate),
            $plans
        );
    }

    public function suggestFromFeatureResolution(array $resolution, ?Collection $plans = null): array
    {
        $issue = $this->classifyFeatureIssue($resolution);
        $currentPlanId = (int) data_get($resolution, 'subscription.plan_id', 0) ?: null;
        $subscriptionState = (string) ($resolution['subscription_state'] ?? 'inactive');

        $recommendation = match ($issue['code']) {
            'addon_required' => $this->buildAddonRecommendation(
                (array) ($resolution['addon_modules'] ?? []),
                $issue['label'],
                $issue['details']
            ),
            'module_unavailable' => $this->buildModuleAvailabilityRecommendation(
                (array) ($resolution['unavailable_modules'] ?? []),
                $issue['label'],
                $issue['details']
            ),
            'subscription_inactive', 'subscription_expired' => $this->buildPlanRecommendationForFeature(
                $resolution,
                $plans,
                $currentPlanId,
                $subscriptionState === 'expired'
            ),
            'permission_missing' => $this->buildPermissionRecommendation(
                (array) ($resolution['missing_permissions'] ?? []),
                $issue['label'],
                $issue['details']
            ),
            'config_missing' => $this->buildConfigurationRecommendation(
                (array) ($resolution['missing_config_keys'] ?? []),
                $issue['label'],
                $issue['details']
            ),
            'no_user_context', 'tenant_context_missing' => $this->buildContextRecommendation(
                $issue['code'],
                $issue['label'],
                $issue['details']
            ),
            'feature_unknown' => $this->buildSupportRecommendation($issue['label'], $issue['details']),
            default => $this->buildNoActionRecommendation(),
        };

        return $this->buildSuggestionPayload(
            type: 'feature',
            key: (string) ($resolution['key'] ?? ''),
            label: (string) ($resolution['label'] ?? ''),
            domain: (string) ($resolution['domain'] ?? ''),
            state: (string) ($resolution['state'] ?? 'hidden'),
            issue: $issue,
            recommendation: $recommendation,
            resolution: $resolution
        );
    }

    public function suggestFromLimitResolution(array $resolution, ?Collection $plans = null): array
    {
        $issue = $this->classifyLimitIssue($resolution);
        $currentPlanId = (int) data_get($resolution, 'subscription.plan_id', 0) ?: null;
        $recommendation = match ($issue['code']) {
            'limit_exceeded', 'limit_near', 'subscription_inactive', 'subscription_expired' => $this->buildPlanRecommendationForLimit(
                $resolution,
                $plans,
                $currentPlanId,
                $issue['code'] === 'subscription_expired'
            ),
            'no_user_context', 'tenant_context_missing' => $this->buildContextRecommendation(
                $issue['code'],
                $issue['label'],
                $issue['details']
            ),
            default => $this->buildNoActionRecommendation(),
        };

        return $this->buildSuggestionPayload(
            type: 'limit',
            key: (string) ($resolution['key'] ?? ''),
            label: (string) ($resolution['label'] ?? ''),
            domain: null,
            state: (string) ($resolution['state'] ?? 'hidden'),
            issue: $issue,
            recommendation: $recommendation,
            resolution: $resolution
        );
    }

    private function buildSuggestionPayload(
        string $type,
        string $key,
        string $label,
        ?string $domain,
        string $state,
        array $issue,
        array $recommendation,
        array $resolution
    ): array {
        return [
            'type' => $type,
            'key' => $key,
            'label' => $label,
            'domain' => $domain,
            'state' => $state,
            'block' => $issue,
            'recommendation' => $recommendation,
            'subscription_state' => $resolution['subscription_state'] ?? null,
            'subject_user_id' => $resolution['subject_user_id'] ?? null,
            'tenant_user_id' => $resolution['tenant_user_id'] ?? null,
            'missing_permissions' => $resolution['missing_permissions'] ?? [],
            'missing_config_keys' => $resolution['missing_config_keys'] ?? [],
            'addon_modules' => $resolution['addon_modules'] ?? [],
            'unavailable_modules' => $resolution['unavailable_modules'] ?? [],
            'source' => $resolution,
        ];
    }

    private function classifyFeatureIssue(array $resolution): array
    {
        $reasons = array_values(array_map('strval', (array) ($resolution['reasons'] ?? [])));
        $missingPermissions = array_values(array_filter(array_map('strval', (array) ($resolution['missing_permissions'] ?? []))));
        $missingConfigKeys = array_values(array_filter(array_map('strval', (array) ($resolution['missing_config_keys'] ?? []))));
        $addonModules = array_values(array_filter(array_map('strval', (array) ($resolution['addon_modules'] ?? []))));
        $unavailableModules = array_values(array_filter(array_map('strval', (array) ($resolution['unavailable_modules'] ?? []))));

        if (in_array('feature_unknown', $reasons, true)) {
            return $this->issue('feature_unknown', 'Funcionalidade desconhecida', ['feature_key' => $resolution['key'] ?? null]);
        }

        if (in_array('no_user_context', $reasons, true)) {
            return $this->issue('no_user_context', 'Sem contexto de utilizador', []);
        }

        if (in_array('tenant_context_missing', $reasons, true)) {
            return $this->issue('tenant_context_missing', 'Sem contexto da empresa', []);
        }

        if (in_array('subscription_expired', $reasons, true)) {
            return $this->issue('subscription_expired', 'Subscrição expirada', $this->subscriptionDetails($resolution));
        }

        if (in_array('subscription_inactive', $reasons, true)) {
            return $this->issue('subscription_inactive', 'Subscrição inactiva', $this->subscriptionDetails($resolution));
        }

        if ($unavailableModules !== [] || in_array('module_unavailable', $reasons, true)) {
            return $this->issue('module_unavailable', 'Módulo não activo no plano', [
                'modules' => $unavailableModules,
            ]);
        }

        if ($addonModules !== [] || in_array('addon_required', $reasons, true)) {
            return $this->issue('addon_required', 'Add-on não activo', [
                'modules' => $addonModules,
            ]);
        }

        if ($missingPermissions !== []) {
            return $this->issue('permission_missing', 'Permissão em falta', [
                'permissions' => $missingPermissions,
            ]);
        }

        if ($missingConfigKeys !== []) {
            return $this->issue('config_missing', 'Configuração em falta', [
                'config_keys' => $missingConfigKeys,
            ]);
        }

        return $this->issue('active', 'Funcionalidade disponível', []);
    }

    private function classifyLimitIssue(array $resolution): array
    {
        $reasons = array_values(array_map('strval', (array) ($resolution['reasons'] ?? [])));
        $subscriptionState = (string) ($resolution['subscription_state'] ?? 'inactive');

        if (in_array('no_user_context', $reasons, true)) {
            return $this->issue('no_user_context', 'Sem contexto de utilizador', []);
        }

        if (in_array('tenant_context_missing', $reasons, true)) {
            return $this->issue('tenant_context_missing', 'Sem contexto da empresa', []);
        }

        if ($subscriptionState === 'expired') {
            return $this->issue('subscription_expired', 'Subscrição expirada', $this->limitDetails($resolution));
        }

        if ($subscriptionState === 'inactive') {
            return $this->issue('subscription_inactive', 'Subscrição inactiva', $this->limitDetails($resolution));
        }

        if (($resolution['state'] ?? null) === 'exceeded') {
            return $this->issue('limit_exceeded', 'Limite excedido', $this->limitDetails($resolution));
        }

        if (($resolution['state'] ?? null) === 'near_limit') {
            return $this->issue('limit_near', 'Próximo do limite', $this->limitDetails($resolution));
        }

        return $this->issue('within_limit', 'Dentro do limite', $this->limitDetails($resolution));
    }

    private function buildAddonRecommendation(array $modules, string $reasonLabel, array $details): array
    {
        $addons = $this->buildAddonPayload($modules);

        return [
            'action' => self::ACTION_ACTIVATE_ADDON,
            'label' => 'Activar add-on',
            'reason_label' => $reasonLabel,
            'reason_details' => $details,
            'recommended_plan' => null,
            'recommended_addons' => $addons,
            'recommended_permissions' => [],
            'recommended_config_keys' => [],
            'alternatives' => [],
            'message' => $addons === []
                ? 'A funcionalidade depende de um módulo que não está disponível para activação imediata nesta empresa.'
                : 'O papel do utilizador pode estar correcto, mas a empresa ainda não activou o add-on necessário. Reveja os add-ons activos da empresa.',
        ];
    }

    private function buildModuleAvailabilityRecommendation(array $modules, string $reasonLabel, array $details): array
    {
        $addons = $this->buildAddonPayload($modules);

        return [
            'action' => self::ACTION_INSTALL_MODULE,
            'label' => 'Activar módulo',
            'reason_label' => $reasonLabel,
            'reason_details' => $details,
            'recommended_plan' => null,
            'recommended_addons' => $addons,
            'recommended_permissions' => [],
            'recommended_config_keys' => [],
            'alternatives' => [],
            'message' => $addons === []
                ? 'O papel do utilizador pode estar correcto, mas a empresa não tem este módulo disponível no plano ou no catálogo activo.'
                : 'O papel do utilizador pode estar correcto, mas a empresa não tem este módulo activo no plano/add-ons. Reveja os módulos activos da empresa.',
        ];
    }

    private function buildPermissionRecommendation(array $permissions, string $reasonLabel, array $details): array
    {
        return [
            'action' => self::ACTION_GRANT_PERMISSION,
            'label' => 'Atribuir permissões',
            'reason_label' => $reasonLabel,
            'reason_details' => $details,
            'recommended_plan' => null,
            'recommended_addons' => [],
            'recommended_permissions' => $permissions,
            'recommended_config_keys' => [],
            'alternatives' => [],
            'message' => $permissions === []
                ? 'A permissão necessária deve ser atribuída ao utilizador.'
                : 'Atribua as permissões em falta para desbloquear a funcionalidade.',
        ];
    }

    private function buildConfigurationRecommendation(array $configKeys, string $reasonLabel, array $details): array
    {
        return [
            'action' => self::ACTION_COMPLETE_CONFIGURATION,
            'label' => 'Completar configuração',
            'reason_label' => $reasonLabel,
            'reason_details' => $details,
            'recommended_plan' => null,
            'recommended_addons' => [],
            'recommended_permissions' => [],
            'recommended_config_keys' => $configKeys,
            'alternatives' => [],
            'message' => $configKeys === []
                ? 'Complete a configuração operacional em falta.'
                : 'Configure os elementos em falta para activar a funcionalidade.',
        ];
    }

    private function buildContextRecommendation(string $reasonCode, string $reasonLabel, array $details): array
    {
        return [
            'action' => self::ACTION_SELECT_COMPANY,
            'label' => 'Seleccionar empresa',
            'reason_label' => $reasonLabel,
            'reason_details' => $details,
            'recommended_plan' => null,
            'recommended_addons' => [],
            'recommended_permissions' => [],
            'recommended_config_keys' => [],
            'alternatives' => [],
            'message' => $reasonCode === 'no_user_context'
                ? 'Autentique-se ou seleccione uma empresa antes de continuar.'
                : 'A empresa activa não foi identificada para esta operação.',
        ];
    }

    private function buildSupportRecommendation(string $reasonLabel, array $details): array
    {
        return [
            'action' => self::ACTION_CONTACT_SUPPORT,
            'label' => 'Contactar suporte',
            'reason_label' => $reasonLabel,
            'reason_details' => $details,
            'recommended_plan' => null,
            'recommended_addons' => [],
            'recommended_permissions' => [],
            'recommended_config_keys' => [],
            'alternatives' => [],
            'message' => 'A funcionalidade solicitada não existe no catálogo activo. Confirme com a equipa técnica.',
        ];
    }

    private function buildNoActionRecommendation(): array
    {
        return [
            'action' => self::ACTION_NO_ACTION,
            'label' => 'Sem acção',
            'reason_label' => 'Sem bloqueio',
            'reason_details' => [],
            'recommended_plan' => null,
            'recommended_addons' => [],
            'recommended_permissions' => [],
            'recommended_config_keys' => [],
            'alternatives' => [],
            'message' => 'Não é necessária qualquer acção adicional.',
        ];
    }

    private function buildPlanRecommendationForFeature(
        array $resolution,
        ?Collection $plans,
        ?int $preferredPlanId,
        bool $subscriptionExpired
    ): array {
        $requiredModules = $this->requiredModulesFromFeatureResolution($resolution);
        $candidatePlans = $this->findFeaturePlanCandidates($requiredModules, $plans, $preferredPlanId);

        return [
            'action' => $subscriptionExpired ? self::ACTION_RENEW_SUBSCRIPTION : self::ACTION_UPGRADE_PLAN,
            'label' => $subscriptionExpired ? 'Renovar subscrição' : 'Actualizar plano',
            'reason_label' => $subscriptionExpired ? 'Subscrição expirada' : 'Subscrição inactiva',
            'reason_details' => $this->subscriptionDetails($resolution),
            'recommended_plan' => $candidatePlans[0] ?? null,
            'recommended_addons' => [],
            'recommended_permissions' => [],
            'recommended_config_keys' => [],
            'alternatives' => array_slice($candidatePlans, 1, 3),
            'message' => $candidatePlans === []
                ? 'Não foi possível encontrar um plano activo que cubra esta funcionalidade.'
                : 'Escolha o plano recomendado para restaurar o acesso à funcionalidade.',
        ];
    }

    private function buildPlanRecommendationForLimit(
        array $resolution,
        ?Collection $plans,
        ?int $preferredPlanId,
        bool $subscriptionExpired
    ): array {
        $limitKey = (string) ($resolution['key'] ?? '');
        $currentUsage = (int) ($resolution['current_usage'] ?? 0);
        $candidatePlans = $this->findLimitPlanCandidates($limitKey, $currentUsage, $plans, $preferredPlanId);

        return [
            'action' => $subscriptionExpired ? self::ACTION_RENEW_SUBSCRIPTION : self::ACTION_UPGRADE_PLAN,
            'label' => $subscriptionExpired ? 'Renovar subscrição' : 'Actualizar plano',
            'reason_label' => (string) ($resolution['state'] === 'near_limit' ? 'Próximo do limite' : 'Limite excedido'),
            'reason_details' => $this->limitDetails($resolution),
            'recommended_plan' => $candidatePlans[0] ?? null,
            'recommended_addons' => [],
            'recommended_permissions' => [],
            'recommended_config_keys' => [],
            'alternatives' => array_slice($candidatePlans, 1, 3),
            'message' => $candidatePlans === []
                ? 'Não foi possível encontrar um plano com margem suficiente para este limite.'
                : 'Escolha o plano recomendado para ultrapassar o limite actual.',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function findFeaturePlanCandidates(array $requiredModules, ?Collection $plans, ?int $preferredPlanId): array
    {
        $requiredModules = array_values(array_unique(array_filter(array_map('strval', $requiredModules))));
        $snapshots = $this->snapshotPlans($plans);
        $candidates = [];

        foreach ($snapshots as $snapshot) {
            if (! $snapshot['status']) {
                continue;
            }

            if (array_diff($requiredModules, $snapshot['modules']) !== []) {
                continue;
            }

            $candidates[] = $this->buildPlanCandidate($snapshot, [
                'required_modules' => $requiredModules,
                'covered_modules' => $requiredModules,
                'missing_modules' => [],
            ]);
        }

        usort($candidates, function (array $left, array $right) use ($preferredPlanId): int {
            $leftPreferred = $preferredPlanId !== null && (int) $left['id'] === $preferredPlanId ? 0 : 1;
            $rightPreferred = $preferredPlanId !== null && (int) $right['id'] === $preferredPlanId ? 0 : 1;

            if ($leftPreferred !== $rightPreferred) {
                return $leftPreferred <=> $rightPreferred;
            }

            $leftFamily = $this->familyRank($left['family']);
            $rightFamily = $this->familyRank($right['family']);

            if ($leftFamily !== $rightFamily) {
                return $leftFamily <=> $rightFamily;
            }

            if ($left['monthly_price'] !== $right['monthly_price']) {
                return $left['monthly_price'] <=> $right['monthly_price'];
            }

            if ($left['yearly_price'] !== $right['yearly_price']) {
                return $left['yearly_price'] <=> $right['yearly_price'];
            }

            return $left['id'] <=> $right['id'];
        });

        return $candidates;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function findLimitPlanCandidates(string $limitKey, int $currentUsage, ?Collection $plans, ?int $preferredPlanId): array
    {
        $snapshots = $this->snapshotPlans($plans);
        $candidates = [];

        foreach ($snapshots as $snapshot) {
            if (! $snapshot['status']) {
                continue;
            }

            $familyLimits = $this->limitMatrixService->resolveFamilyLimits($snapshot['family'], $snapshot);
            $contract = $familyLimits['limits'][$limitKey] ?? null;

            if (! is_array($contract)) {
                continue;
            }

            $contractedLimit = (int) ($contract['value'] ?? 0);
            $satisfies = $contractedLimit < 0 || $contractedLimit >= $currentUsage;

            if (! $satisfies) {
                continue;
            }

            $candidates[] = $this->buildPlanCandidate($snapshot, [
                'limit_key' => $limitKey,
                'limit_label' => $contract['label'] ?? $limitKey,
                'limit_value' => $contractedLimit,
                'current_usage' => $currentUsage,
                'remaining_after_upgrade' => $contractedLimit < 0 ? null : max($contractedLimit - $currentUsage, 0),
            ]);
        }

        usort($candidates, function (array $left, array $right) use ($preferredPlanId): int {
            $leftPreferred = $preferredPlanId !== null && (int) $left['id'] === $preferredPlanId ? 0 : 1;
            $rightPreferred = $preferredPlanId !== null && (int) $right['id'] === $preferredPlanId ? 0 : 1;

            if ($leftPreferred !== $rightPreferred) {
                return $leftPreferred <=> $rightPreferred;
            }

            $leftFamily = $this->familyRank($left['family']);
            $rightFamily = $this->familyRank($right['family']);

            if ($leftFamily !== $rightFamily) {
                return $leftFamily <=> $rightFamily;
            }

            $leftLimit = $left['limit_value'] ?? PHP_INT_MAX;
            $rightLimit = $right['limit_value'] ?? PHP_INT_MAX;

            if ($leftLimit !== $rightLimit) {
                return $leftLimit <=> $rightLimit;
            }

            if ($left['monthly_price'] !== $right['monthly_price']) {
                return $left['monthly_price'] <=> $right['monthly_price'];
            }

            return $left['id'] <=> $right['id'];
        });

        return $candidates;
    }

    private function buildPlanCandidate(array $snapshot, array $metadata): array
    {
        return array_merge([
            'id' => $snapshot['id'],
            'name' => $snapshot['name'],
            'family' => $snapshot['family'],
            'family_label' => $snapshot['family_label'],
            'status' => $snapshot['status'],
            'free_plan' => $snapshot['free_plan'],
            'trial' => $snapshot['trial'],
            'users_limit' => $snapshot['users_limit'],
            'storage_limit_kb' => $snapshot['storage_limit_kb'],
            'monthly_price' => $snapshot['prices']['monthly'],
            'yearly_price' => $snapshot['prices']['yearly'],
            'modules' => $snapshot['modules'],
            'module_count' => $snapshot['module_count'],
        ], $metadata);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function snapshotPlans(?Collection $plans): array
    {
        $plans ??= Plan::query()->orderBy('id')->get();

        return $plans->map(fn (Plan $plan) => $this->snapshotPlan($plan))->values()->all();
    }

    private function snapshotPlan(Plan $plan): array
    {
        $modules = collect($plan->modules ?? [])
            ->map(fn ($module) => trim((string) $module))
            ->filter(fn (string $module) => $module !== '')
            ->values()
            ->all();

        $family = $this->normalizePlanFamily($plan->name);

        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'family' => $family,
            'family_label' => $this->familyLabel($family),
            'status' => (bool) $plan->status,
            'free_plan' => (bool) $plan->free_plan,
            'trial' => (bool) $plan->trial,
            'users_limit' => (int) $plan->number_of_users,
            'storage_limit_kb' => (int) $plan->storage_limit,
            'prices' => [
                'monthly' => (float) $plan->package_price_monthly,
                'yearly' => (float) $plan->package_price_yearly,
            ],
            'modules' => $modules,
            'module_count' => count($modules),
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

    private function familyLabel(string $familyKey): string
    {
        $family = (array) config('assistant_activation.plan_families.' . $familyKey, []);

        if (isset($family['label']) && is_string($family['label']) && $family['label'] !== '') {
            return $family['label'];
        }

        return Str::of($familyKey)->replace('_', ' ')->title()->toString();
    }

    private function familyRank(string $familyKey): int
    {
        $order = array_values(array_keys((array) config('assistant_activation.plan_families', [])));
        $index = array_search($familyKey, $order, true);

        return $index === false ? PHP_INT_MAX : (int) $index;
    }

    private function buildAddonPayload(array $moduleReferences): array
    {
        $moduleReferences = array_values(array_unique(array_filter(array_map('strval', $moduleReferences))));
        $addons = [];

        foreach ($moduleReferences as $moduleReference) {
            $module = $this->moduleCatalogService->find($moduleReference);
            $featureKeys = $this->moduleFeatureBridgeService->featureKeysForModuleReference($moduleReference);

            $addons[] = [
                'reference' => $moduleReference,
                'key' => $module['key'] ?? $moduleReference,
                'label' => $module['label'] ?? $this->humanizeKey($moduleReference),
                'type' => $module['type'] ?? null,
                'package_key' => $module['package_key'] ?? null,
                'menu_groups' => $module['menu_groups'] ?? [],
                'feature_keys' => $featureKeys,
                'feature_count' => count($featureKeys),
            ];
        }

        return $addons;
    }

    private function requiredModulesFromFeatureResolution(array $resolution): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($module) => trim((string) ($module['module'] ?? '')),
            (array) ($resolution['modules'] ?? [])
        ))));
    }

    private function subscriptionDetails(array $resolution): array
    {
        return [
            'subscription_state' => $resolution['subscription_state'] ?? null,
            'plan_id' => data_get($resolution, 'subscription.plan_id'),
            'plan_name' => data_get($resolution, 'subscription.plan_name'),
            'plan_family' => data_get($resolution, 'subscription.plan_family'),
            'plan_expire_date' => data_get($resolution, 'subscription.plan_expire_date'),
            'trial_expire_date' => data_get($resolution, 'subscription.trial_expire_date'),
        ];
    }

    private function limitDetails(array $resolution): array
    {
        return [
            'limit_key' => $resolution['key'] ?? null,
            'contracted_limit' => $resolution['contracted_limit'] ?? null,
            'current_usage' => $resolution['current_usage'] ?? null,
            'usage_percent' => $resolution['usage_percent'] ?? null,
            'remaining' => $resolution['remaining'] ?? null,
            'threshold_percent' => $resolution['threshold_percent'] ?? null,
            'subscription_state' => $resolution['subscription_state'] ?? null,
            'plan_id' => $resolution['plan_id'] ?? null,
            'plan_name' => $resolution['plan_name'] ?? null,
            'plan_family' => $resolution['plan_family'] ?? null,
        ];
    }

    private function issue(string $code, string $label, array $details): array
    {
        return [
            'code' => $code,
            'label' => $label,
            'details' => $details,
        ];
    }

    private function normalizeLabel(?string $label): string
    {
        return Str::of((string) $label)
            ->lower()
            ->replace(['_', '-', '/'], ' ')
            ->squish()
            ->toString();
    }

    private function humanizeKey(string $key): string
    {
        return Str::of($key)->replace(['.', '_', '-'], ' ')->title()->toString();
    }
}
