<?php

namespace App\Services\AssistantActivation;

use App\Models\Plan;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PlanContractService
{
    public function __construct(
        private readonly ModuleCatalogService $moduleCatalogService,
        private readonly ModuleFeatureBridgeService $moduleFeatureBridgeService,
        private readonly PermissionMatrixService $permissionMatrixService,
        private readonly PlanLimitMatrixService $planLimitMatrixService,
        private readonly OnboardingStepRegistry $onboardingStepRegistry,
        private readonly OnboardingProgressService $onboardingProgressService,
        private readonly OnboardingReadinessService $onboardingReadinessService,
        private readonly OnboardingCompletionService $onboardingCompletionService,
        private readonly OnboardingStepSkipService $onboardingStepSkipService,
        private readonly ReadinessScoreService $readinessScoreService,
        private readonly PlanFeatureResolver $featureResolver
    ) {
    }

    public function contractVersion(): string
    {
        return (string) config('assistant_activation.contract_version', 'unknown');
    }

    public function featureStates(): array
    {
        return array_values((array) config('assistant_activation.feature_states', []));
    }

    public function globalRequirements(): array
    {
        return array_values((array) config('assistant_activation.global_requirements', []));
    }

    public function planFamilies(): array
    {
        return (array) config('assistant_activation.plan_families', []);
    }

    public function priorityDomains(): array
    {
        return (array) config('assistant_activation.priority_domains', []);
    }

    public function normalizePlanFamily(?string $planName): string
    {
        $normalized = $this->normalizeLabel($planName);

        foreach ($this->planFamilies() as $familyKey => $family) {
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

    public function familyLabel(string $familyKey): string
    {
        $family = $this->planFamilies()[$familyKey] ?? null;

        if (is_array($family) && isset($family['label'])) {
            return (string) $family['label'];
        }

        return Str::of($familyKey)->replace('_', ' ')->title()->toString();
    }

    public function buildReport(?Collection $plans = null): array
    {
        $plans ??= Plan::query()->orderBy('id')->get();

        $planSnapshots = $plans->map(fn (Plan $plan) => $this->snapshotPlan($plan))->values()->all();
        $familyCounts = [];

        foreach ($planSnapshots as $snapshot) {
            $family = $snapshot['family'];
            $familyCounts[$family] = ($familyCounts[$family] ?? 0) + 1;
        }

        $domains = [];
        foreach ($this->priorityDomains() as $key => $domain) {
            $domains[$key] = $this->buildDomainReport($key, $domain, $planSnapshots);
        }

        $moduleCatalog = $this->moduleCatalogService->buildReport();
        $moduleFeatureBridge = $this->moduleFeatureBridgeService->buildReport();
        $permissionMatrix = $this->permissionMatrixService->buildReport();
        $planLimitMatrix = $this->planLimitMatrixService->buildReport($planSnapshots);
        $onboardingSteps = $this->onboardingStepRegistry->buildReport();
        $onboardingProgress = $this->onboardingProgressService->buildReport();
        $onboardingReadiness = $this->onboardingReadinessService->buildReport();
        $onboardingCompletion = $this->onboardingCompletionService->buildReport();
        $onboardingStepSkips = $this->onboardingStepSkipService->buildReport();
        $readinessFormula = $this->readinessScoreService->buildReport();
        $featureMatrix = $this->featureResolver->buildCatalogReport();
        $featureStatePayload = FeatureStatePayload::schema();

        return [
            'meta' => [
                'contract_version' => $this->contractVersion(),
                'generated_at' => now()->toIso8601String(),
                'feature_states' => $this->featureStates(),
            ],
            'summary' => [
                'plans_total' => count($planSnapshots),
                'family_counts' => $familyCounts,
                'domains_total' => count($domains),
                'modules_total' => $moduleCatalog['summary']['modules_total'],
                'limit_families_total' => $planLimitMatrix['summary']['families_total'],
                'limit_dimensions_total' => $planLimitMatrix['summary']['dimensions_total'],
                'module_feature_links_total' => $moduleFeatureBridge['summary']['links_total'],
                'module_feature_modules_with_links_total' => $moduleFeatureBridge['summary']['modules_with_feature_links_total'],
                'module_feature_features_with_links_total' => $moduleFeatureBridge['summary']['features_with_module_links_total'],
                'onboarding_modules_total' => $onboardingSteps['summary']['modules_total'],
                'onboarding_steps_total' => $onboardingSteps['summary']['steps_total'],
                'onboarding_progress_step_states_total' => $onboardingProgress['summary']['step_states_total'],
                'onboarding_progress_checklist_states_total' => $onboardingProgress['summary']['checklist_states_total'],
                'onboarding_readiness_state_codes_total' => $onboardingReadiness['summary']['state_codes_total'],
                'onboarding_readiness_block_types_total' => $onboardingReadiness['summary']['critical_block_types_total'],
                'onboarding_readiness_checks_total' => $onboardingReadiness['summary']['critical_config_checks_total'],
                'onboarding_completion_decision_states_total' => $onboardingCompletion['summary']['decision_states_total'],
                'onboarding_completion_blocker_codes_total' => $onboardingCompletion['summary']['blocker_codes_total'],
                'onboarding_completion_validation_checks_total' => $onboardingCompletion['summary']['validation_checks_total'],
                'onboarding_step_skip_decision_states_total' => $onboardingStepSkips['summary']['decision_states_total'],
                'onboarding_step_skip_blocker_codes_total' => $onboardingStepSkips['summary']['blocker_codes_total'],
                'onboarding_step_skip_validation_checks_total' => $onboardingStepSkips['summary']['validation_checks_total'],
                'onboarding_step_skip_audit_fields_total' => $onboardingStepSkips['summary']['audit_fields_total'],
                'readiness_module_weights_total' => $readinessFormula['summary']['module_weights_total'],
                'readiness_critical_weights_total' => $readinessFormula['summary']['critical_config_weights_total'],
                'readiness_module_component_weight' => $readinessFormula['summary']['module_component_weight'],
                'readiness_critical_component_weight' => $readinessFormula['summary']['critical_config_component_weight'],
                'feature_matrix_total' => $featureMatrix['summary']['features_total'],
                'feature_matrix_domains_total' => $featureMatrix['summary']['domains_total'],
                'feature_matrix_modules_total' => $featureMatrix['summary']['modules_total'],
                'feature_matrix_permissions_total' => $featureMatrix['summary']['permissions_total'],
                'feature_matrix_config_keys_total' => $featureMatrix['summary']['config_keys_total'],
                'feature_state_fields_total' => count($featureStatePayload['top_level_fields']),
                'feature_state_surfaces_total' => count($featureStatePayload['surface_keys']),
            ],
            'global_requirements' => $this->globalRequirements(),
            'plan_families' => $this->planFamilies(),
            'domains' => $domains,
            'module_catalog' => $moduleCatalog,
            'module_feature_bridge' => $moduleFeatureBridge,
            'permission_matrix' => $permissionMatrix,
            'plan_limits' => $planLimitMatrix,
            'onboarding_steps' => $onboardingSteps,
            'onboarding_progress' => $onboardingProgress,
            'onboarding_readiness' => $onboardingReadiness,
            'onboarding_completion' => $onboardingCompletion,
            'onboarding_step_skips' => $onboardingStepSkips,
            'readiness_formula' => $readinessFormula,
            'feature_matrix' => $featureMatrix,
            'feature_state_payload' => $featureStatePayload,
            'plans' => $planSnapshots,
        ];
    }

    public function snapshotPlan(Plan $plan): array
    {
        $modules = collect($plan->modules ?? [])
            ->map(fn ($module) => trim((string) $module))
            ->filter(fn ($module) => $module !== '')
            ->values()
            ->all();

        $family = $this->normalizePlanFamily($plan->name);
        $onboardingSteps = $this->onboardingStepRegistry->buildPlanReport($modules, $plan->name);
        $onboardingProgress = $this->onboardingProgressService->buildReport();
        $onboardingReadiness = $this->onboardingReadinessService->buildReport();
        $onboardingCompletion = $this->onboardingCompletionService->buildReport();
        $onboardingStepSkips = $this->onboardingStepSkipService->buildReport();

        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'family' => $family,
            'family_label' => $this->familyLabel($family),
            'status' => (bool) $plan->status,
            'free_plan' => (bool) $plan->free_plan,
            'trial' => (bool) $plan->trial,
            'trial_days' => (int) $plan->trial_days,
            'users_limit' => (int) $plan->number_of_users,
            'storage_limit_kb' => (int) $plan->storage_limit,
            'prices' => [
                'monthly' => (float) $plan->package_price_monthly,
                'yearly' => (float) $plan->package_price_yearly,
            ],
            'modules' => $modules,
            'module_count' => count($modules),
            'onboarding_steps' => [
                'summary' => $onboardingSteps['summary'],
            ],
            'onboarding_progress' => [
                'summary' => $onboardingProgress['summary'],
            ],
            'onboarding_readiness' => [
                'summary' => $onboardingReadiness['summary'],
            ],
            'onboarding_completion' => [
                'summary' => $onboardingCompletion['summary'],
            ],
            'onboarding_step_skips' => [
                'summary' => $onboardingStepSkips['summary'],
            ],
        ];
    }

    private function buildDomainReport(string $domainKey, array $domain, array $planSnapshots): array
    {
        $recommendedModules = array_values(array_unique(array_map('strval', (array) ($domain['recommended_modules'] ?? []))));
        $requiredPermissions = array_values(array_unique(array_map('strval', (array) ($domain['required_permissions'] ?? []))));
        $requiredConfigKeys = array_values(array_unique(array_map('strval', (array) ($domain['required_config_keys'] ?? []))));

        $planCoverage = [];

        foreach ($planSnapshots as $snapshot) {
            $coverage = $this->calculateCoverage($snapshot['modules'], $recommendedModules);
            $planCoverage[] = [
                'plan_id' => $snapshot['id'],
                'plan_name' => $snapshot['name'],
                'family' => $snapshot['family'],
                'coverage_percent' => $coverage['coverage_percent'],
                'matched_modules' => $coverage['matched_modules'],
                'missing_modules' => $coverage['missing_modules'],
                'covered_modules' => $coverage['covered_modules'],
                'total_modules' => $coverage['total_modules'],
            ];
        }

        return [
            'key' => $domainKey,
            'label' => (string) ($domain['label'] ?? $domainKey),
            'priority' => (int) ($domain['priority'] ?? 0),
            'recommended_modules' => $recommendedModules,
            'required_permissions' => $requiredPermissions,
            'required_config_keys' => $requiredConfigKeys,
            'checklist' => array_values((array) ($domain['checklist'] ?? [])),
            'plan_coverage' => $planCoverage,
        ];
    }

    private function calculateCoverage(array $planModules, array $requiredModules): array
    {
        $normalizedPlanModules = array_map(fn ($module) => strtolower(trim((string) $module)), $planModules);
        $normalizedRequiredModules = array_map(fn ($module) => strtolower(trim((string) $module)), $requiredModules);

        $covered = [];
        $missing = [];

        foreach ($normalizedRequiredModules as $index => $requiredModule) {
            if ($requiredModule !== '' && in_array($requiredModule, $normalizedPlanModules, true)) {
                $covered[] = $requiredModules[$index];
            } elseif ($requiredModule !== '') {
                $missing[] = $requiredModules[$index];
            }
        }

        $total = count($normalizedRequiredModules);
        $matched = count($covered);
        $coveragePercent = $total > 0 ? (int) round(($matched / $total) * 100) : 100;

        return [
            'total_modules' => $total,
            'matched_modules' => $matched,
            'coverage_percent' => $coveragePercent,
            'covered_modules' => $covered,
            'missing_modules' => $missing,
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
}
