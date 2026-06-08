<?php

namespace App\Services\AssistantActivation;

use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class MyPlanOverviewService
{
    public function __construct(
        private readonly PlanContractService $planContractService,
        private readonly ModuleCatalogService $moduleCatalogService,
        private readonly ModuleFeatureBridgeService $moduleFeatureBridgeService,
        private readonly PlanFeatureResolver $planFeatureResolver,
        private readonly PlanLimitResolver $planLimitResolver,
        private readonly TenantUsageService $tenantUsageService,
        private readonly UpgradeSuggestionService $upgradeSuggestionService,
        private readonly ContextualCtaResolverService $contextualCtaResolverService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(User|int $subject): array
    {
        $subjectUser = $subject instanceof User ? $subject : User::find($subject);

        if (! $subjectUser) {
            throw new \InvalidArgumentException('User not found.');
        }

        $tenantUser = $this->resolveTenantUser($subjectUser);
        $plan = $tenantUser?->active_plan ? Plan::find($tenantUser->active_plan) : null;
        $planFamily = $plan ? $this->planContractService->normalizePlanFamily($plan->name) : 'custom';
        $planFamilyLabel = $this->planContractService->familyLabel($planFamily);

        $planReferences = $this->normalizeList((array) ($plan?->modules ?? []));
        $activeReferences = $this->normalizeList((array) Plan::getUserSubscriptionModules($tenantUser?->id));
        $planModuleKeys = $this->resolveModuleKeys($planReferences);
        $activeModuleKeys = $this->resolveModuleKeys($activeReferences);
        $addonModuleKeys = array_values(array_diff($activeModuleKeys, $planModuleKeys));

        $featureReport = $this->planFeatureResolver->buildReport($subjectUser);
        $limitReport = $this->planLimitResolver->buildReport($subjectUser);
        $usageReport = $this->tenantUsageService->buildReport($subjectUser);

        $includedModules = $this->buildModuleEntries($planModuleKeys, $planModuleKeys, $activeModuleKeys, true);
        $addonModules = $this->buildModuleEntries($addonModuleKeys, $planModuleKeys, $activeModuleKeys, false);
        $limits = $this->buildLimitEntries((array) data_get($limitReport, 'dimensions', []));
        $suggestions = $this->buildSuggestions(
            (array) data_get($featureReport, 'features', []),
            (array) data_get($limitReport, 'dimensions', [])
        );

        $subscription = $this->buildSubscriptionOverview($tenantUser, $plan);

        return [
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'company_id' => $tenantUser?->id,
                'company_name' => $tenantUser?->name,
                'plan_family' => $planFamily,
                'plan_family_label' => $planFamilyLabel,
                'feature_catalog_version' => $featureReport['meta']['catalog_version'] ?? null,
                'limit_catalog_version' => $limitReport['meta']['catalog_version'] ?? null,
            ],
            'overview' => [
                'company_name' => $tenantUser?->name,
                'plan_name' => $plan?->name,
                'plan_description' => $plan?->description,
                'plan_status' => $subscription['status'],
                'plan_status_label' => $this->labelSubscriptionState((string) $subscription['status']),
                'billing_cycle' => $subscription['billing_cycle'],
                'billing_cycle_label' => $this->labelBillingCycle((string) $subscription['billing_cycle']),
                'expires_on' => $subscription['expires_on'],
                'trial_expires_on' => $subscription['trial_expires_on'],
                'is_free' => (bool) ($plan?->free_plan ?? false),
                'monthly_price' => (float) ($plan?->package_price_monthly ?? 0),
                'yearly_price' => (float) ($plan?->package_price_yearly ?? 0),
                'users_limit' => (int) ($plan?->number_of_users ?? 0),
                'storage_limit_kb' => (int) ($plan?->storage_limit ?? 0),
            ],
            'usage' => [
                'summary' => [
                    'current_usage_total' => (int) data_get($usageReport, 'summary.current_usage_total', 0),
                ],
            ],
            'summary' => [
                'plan_modules_total' => count($planModuleKeys),
                'active_modules_total' => count($activeModuleKeys),
                'addon_modules_total' => count($addonModuleKeys),
                'available_modules_total' => count($this->moduleCatalogService->modules()),
                'limit_dimensions_total' => (int) data_get($limitReport, 'summary.dimensions_total', 0),
                'limit_near_total' => (int) data_get($limitReport, 'summary.near_limit_total', 0),
                'limit_exceeded_total' => (int) data_get($limitReport, 'summary.exceeded_total', 0),
                'suggestions_total' => count($suggestions),
            ],
            'modules' => [
                'included' => $includedModules,
                'addons' => $addonModules,
            ],
            'limits' => [
                'summary' => [
                    'dimensions_total' => (int) data_get($limitReport, 'summary.dimensions_total', 0),
                    'near_limit_total' => (int) data_get($limitReport, 'summary.near_limit_total', 0),
                    'exceeded_total' => (int) data_get($limitReport, 'summary.exceeded_total', 0),
                    'current_usage_total' => (int) data_get($usageReport, 'summary.current_usage_total', 0),
                ],
                'dimensions' => $limits,
            ],
            'suggestions' => $suggestions,
        ];
    }

    private function resolveTenantUser(User $user): ?User
    {
        if ($user->type === 'company' || $user->isSuperAdminUser()) {
            return $user;
        }

        return $user->createdBy ?: $user;
    }

    /**
     * @param array<int, string> $references
     * @return array<int, string>
     */
    private function resolveModuleKeys(array $references): array
    {
        $moduleKeys = [];

        foreach ($this->normalizeList($references) as $reference) {
            foreach ($this->moduleFeatureBridgeService->moduleKeysForReference($reference) as $moduleKey) {
                $moduleKeys[$moduleKey] = true;
            }
        }

        return array_values(array_keys($moduleKeys));
    }

    /**
     * @param array<int, string> $moduleKeys
     * @param array<int, string> $planModuleKeys
     * @param array<int, string> $activeModuleKeys
     * @return array<int, array<string, mixed>>
     */
    private function buildModuleEntries(array $moduleKeys, array $planModuleKeys, array $activeModuleKeys, bool $isPlanSection): array
    {
        $entries = [];

        foreach ($this->normalizeList($moduleKeys) as $moduleKey) {
            $module = $this->moduleCatalogService->find($moduleKey) ?? [];
            $featureKeys = $this->moduleFeatureBridgeService->featureKeysForModuleReference($moduleKey);
            $isIncluded = in_array($moduleKey, $planModuleKeys, true);
            $isActive = in_array($moduleKey, $activeModuleKeys, true);

            $entries[] = [
                'key' => $moduleKey,
                'reference' => (string) ($module['package_key'] ?? $module['permission_module'] ?? $moduleKey),
                'label' => (string) ($module['label'] ?? Str::of($moduleKey)->replace('_', ' ')->title()->toString()),
                'type' => (string) ($module['type'] ?? 'core'),
                'package_key' => $module['package_key'] ?? null,
                'permission_module' => $module['permission_module'] ?? null,
                'menu_groups' => $module['menu_groups'] ?? [],
                'notes' => $module['notes'] ?? null,
                'feature_keys' => $featureKeys,
                'feature_count' => count($featureKeys),
                'included_in_plan' => $isIncluded,
                'active' => $isActive,
                'state' => $isActive ? ($isIncluded ? 'active' : 'addon') : ($isPlanSection ? 'locked' : 'inactive'),
            ];
        }

        usort($entries, static fn (array $left, array $right): int => strcmp($left['label'] ?? '', $right['label'] ?? ''));

        return $entries;
    }

    /**
     * @param array<int, array<string, mixed>> $dimensions
     * @return array<int, array<string, mixed>>
     */
    private function buildLimitEntries(array $dimensions): array
    {
        $entries = [];

        foreach ($dimensions as $dimension) {
            $resolution = (array) $dimension;
            $suggestion = $this->upgradeSuggestionService->suggestFromLimitResolution($resolution);
            $cta = $this->contextualCtaResolverService->forRecommendation((array) data_get($suggestion, 'recommendation', []), $resolution);

            $entries[] = [
                'key' => (string) data_get($resolution, 'key', ''),
                'label' => (string) data_get($resolution, 'label', ''),
                'description' => (string) data_get($resolution, 'description', ''),
                'unit' => (string) data_get($resolution, 'unit', ''),
                'state' => (string) data_get($resolution, 'state', 'hidden'),
                'contracted_limit' => data_get($resolution, 'contracted_limit'),
                'contracted_limit_display' => data_get($resolution, 'contracted_limit_display'),
                'current_usage' => (int) data_get($resolution, 'current_usage', 0),
                'remaining' => data_get($resolution, 'remaining'),
                'usage_percent' => data_get($resolution, 'usage_percent'),
                'threshold_percent' => data_get($resolution, 'threshold_percent'),
                'subscription_state' => data_get($resolution, 'subscription_state'),
                'plan_family' => data_get($resolution, 'plan_family'),
                'plan_name' => data_get($resolution, 'plan_name'),
                'plan_id' => data_get($resolution, 'plan_id'),
                'recommendation' => $this->extractRecommendation($suggestion),
                'cta' => $cta,
            ];
        }

        return $entries;
    }

    /**
     * @param array<int, array<string, mixed>> $features
     * @param array<int, array<string, mixed>> $limits
     * @return array<int, array<string, mixed>>
     */
    private function buildSuggestions(array $features, array $limits): array
    {
        $suggestions = [];

        foreach ($features as $feature) {
            $state = (string) data_get($feature, 'state', 'hidden');
            $blockCode = (string) data_get($feature, 'block.code', '');

            if (! in_array($state, ['addon', 'hidden', 'locked'], true)) {
                continue;
            }

            if (! in_array($blockCode, ['addon_required', 'module_unavailable', 'subscription_inactive', 'subscription_expired'], true)) {
                continue;
            }

            $suggestion = $this->upgradeSuggestionService->suggestFromFeatureResolution($feature);
            $cta = $this->contextualCtaResolverService->forRecommendation((array) data_get($suggestion, 'recommendation', []), $feature);

            if (data_get($suggestion, 'recommendation.action') === 'no_action') {
                continue;
            }

            $suggestions[] = $this->buildSuggestionEntry('feature', $feature, $suggestion, $cta);
        }

        foreach ($limits as $limit) {
            $state = (string) data_get($limit, 'state', 'hidden');

            if (! in_array($state, ['near_limit', 'exceeded', 'hidden'], true)) {
                continue;
            }

            $suggestion = $this->upgradeSuggestionService->suggestFromLimitResolution($limit);
            $cta = $this->contextualCtaResolverService->forRecommendation((array) data_get($suggestion, 'recommendation', []), $limit);

            if (data_get($suggestion, 'recommendation.action') === 'no_action') {
                continue;
            }

            $suggestions[] = $this->buildSuggestionEntry('limit', $limit, $suggestion, $cta);
        }

        usort($suggestions, static function (array $left, array $right): int {
            if ($left['priority'] !== $right['priority']) {
                return $left['priority'] <=> $right['priority'];
            }

            return strcmp($left['title'], $right['title']);
        });

        return array_slice($suggestions, 0, 6);
    }

    /**
     * @param array<string, mixed> $resolution
     * @param array<string, mixed> $suggestion
     * @param array<string, mixed>|null $cta
     * @return array<string, mixed>
     */
    private function buildSuggestionEntry(string $kind, array $resolution, array $suggestion, ?array $cta): array
    {
        return [
            'kind' => $kind,
            'key' => (string) data_get($resolution, 'key', ''),
            'title' => (string) data_get($resolution, 'label', ''),
            'state' => (string) data_get($resolution, 'state', 'hidden'),
            'message' => (string) data_get($suggestion, 'recommendation.message', ''),
            'reason_label' => (string) data_get($suggestion, 'block.label', ''),
            'reason_code' => (string) data_get($suggestion, 'block.code', ''),
            'action' => (string) data_get($suggestion, 'recommendation.action', 'no_action'),
            'action_label' => (string) data_get($suggestion, 'recommendation.label', ''),
            'recommended_plan' => data_get($suggestion, 'recommendation.recommended_plan'),
            'recommended_addons' => data_get($suggestion, 'recommendation.recommended_addons', []),
            'recommended_permissions' => data_get($suggestion, 'recommendation.recommended_permissions', []),
            'recommended_config_keys' => data_get($suggestion, 'recommendation.recommended_config_keys', []),
            'cta' => $cta,
            'priority' => $this->suggestionPriority($kind, $resolution, $suggestion),
        ];
    }

    /**
     * @param array<string, mixed> $suggestion
     * @return array<string, mixed>
     */
    private function extractRecommendation(array $suggestion): array
    {
        return [
            'action' => (string) data_get($suggestion, 'recommendation.action', 'no_action'),
            'label' => (string) data_get($suggestion, 'recommendation.label', ''),
            'message' => (string) data_get($suggestion, 'recommendation.message', ''),
            'recommended_plan' => data_get($suggestion, 'recommendation.recommended_plan'),
            'recommended_addons' => data_get($suggestion, 'recommendation.recommended_addons', []),
        ];
    }

    private function suggestionPriority(string $kind, array $resolution, array $suggestion): int
    {
        $state = (string) data_get($resolution, 'state', 'hidden');
        $blockCode = (string) data_get($suggestion, 'block.code', '');

        return match (true) {
            $blockCode === 'subscription_expired' => 0,
            $blockCode === 'subscription_inactive' => 1,
            $kind === 'limit' && $state === 'exceeded' => 2,
            $kind === 'limit' && $state === 'near_limit' => 3,
            $blockCode === 'addon_required' => 4,
            $blockCode === 'module_unavailable' => 5,
            default => 10,
        };
    }

    /**
     * @param array<int, mixed> $values
     * @return array<int, string>
     */
    private function normalizeList(array $values): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            $values
        ))));
    }

    private function buildSubscriptionOverview(?User $tenantUser, ?Plan $plan): array
    {
        if (! $tenantUser || ! $plan) {
            return [
                'status' => 'inactive',
                'billing_cycle' => 'monthly',
                'expires_on' => null,
                'trial_expires_on' => $tenantUser?->trial_expire_date,
            ];
        }

        $billingCycle = $this->resolveBillingCycle($tenantUser, $plan);
        $status = 'active';

        if ((int) $tenantUser->active_plan <= 0) {
            $status = 'inactive';
        } elseif ($tenantUser->trial_expire_date && now()->lte(Carbon::parse($tenantUser->trial_expire_date))) {
            $status = 'trial';
        } elseif ($tenantUser->plan_expire_date && now()->gt(Carbon::parse($tenantUser->plan_expire_date))) {
            $status = 'expired';
        }

        return [
            'status' => $status,
            'billing_cycle' => $billingCycle,
            'expires_on' => $tenantUser->plan_expire_date ? Carbon::parse($tenantUser->plan_expire_date)->toDateString() : null,
            'trial_expires_on' => $tenantUser->trial_expire_date ? Carbon::parse($tenantUser->trial_expire_date)->toDateString() : null,
        ];
    }

    private function resolveBillingCycle(User $tenantUser, Plan $plan): string
    {
        $latestOrder = Order::query()
            ->where('created_by', $tenantUser->id)
            ->where('plan_id', $tenantUser->active_plan)
            ->where('payment_status', 'succeeded')
            ->latest()
            ->first();

        if (! empty($tenantUser->trial_expire_date) && Carbon::parse($tenantUser->trial_expire_date)->isFuture()) {
            return 'trial';
        }

        if (empty($tenantUser->plan_expire_date)) {
            return 'lifetime';
        }

        if ($latestOrder) {
            $diffDays = Carbon::parse($latestOrder->created_at)->diffInDays(Carbon::parse($tenantUser->plan_expire_date));

            if ($diffDays > 40) {
                return 'yearly';
            }
        }

        return $plan->free_plan ? 'lifetime' : 'monthly';
    }

    private function labelSubscriptionState(string $state): string
    {
        return match ($state) {
            'active' => 'Activo',
            'trial' => 'Trial',
            'expired' => 'Expirado',
            'inactive' => 'Sem plano activo',
            'lifetime' => 'Vitalício',
            default => Str::of($state)->replace('_', ' ')->title()->toString(),
        };
    }

    private function labelBillingCycle(string $cycle): string
    {
        return match ($cycle) {
            'trial' => 'Trial',
            'yearly' => 'Anual',
            'monthly' => 'Mensal',
            'lifetime' => 'Vitalício',
            default => Str::of($cycle)->replace('_', ' ')->title()->toString(),
        };
    }
}
