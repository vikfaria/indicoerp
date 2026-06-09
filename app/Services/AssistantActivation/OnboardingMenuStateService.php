<?php

namespace App\Services\AssistantActivation;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class OnboardingMenuStateService
{
    public function __construct(
        private readonly OnboardingReadinessService $onboardingReadinessService,
        private readonly OnboardingStepRegistry $onboardingStepRegistry,
        private readonly AssistantActivationCacheService $assistantActivationCacheService,
        private readonly ContextualCtaResolverService $contextualCtaResolverService
    ) {
    }

    public function catalogVersion(): string
    {
        return $this->onboardingReadinessService->catalogVersion();
    }

    /**
     * @param array<int, string> $planModules
     */
    public function snapshot(User|int $company, ?array $planModules = null, ?string $planLabel = null): array
    {
        $company = $company instanceof User ? $company : User::find($company);

        if (! $company) {
            throw new InvalidArgumentException('Company not found.');
        }

        $cacheKey = $this->cacheKey($company, $planModules, $planLabel);

        return Cache::remember(
            $cacheKey,
            now()->addMinutes($this->ttlMinutes()),
            fn () => $this->buildSnapshot($company, $planModules, $planLabel)
        );
    }

    /**
     * @param array<int, string>|null $planModules
     */
    private function buildSnapshot(User $company, ?array $planModules, ?string $planLabel): array
    {
        $readiness = $this->onboardingReadinessService->calculateForCompany($company, $planModules, $planLabel);
        $criticalBlocks = array_values((array) data_get($readiness, 'critical_blocks', []));
        $moduleStates = $this->buildModuleStates($criticalBlocks);
        $blockedModuleKeys = array_values(array_map(
            static fn (array $moduleState): string => (string) $moduleState['key'],
            array_filter(
                $moduleStates,
                static fn (array $moduleState): bool => (bool) ($moduleState['blocked'] ?? false)
            )
        ));

        $signature = $this->buildSignature($company, $readiness, $moduleStates);

        return [
            'meta' => [
                'catalog_version' => (string) data_get($readiness, 'meta.catalog_version', $this->catalogVersion()),
                'generated_at' => now()->toIso8601String(),
                'company_id' => $company->id,
                'company_name' => $company->name,
                'plan_label' => (string) data_get($readiness, 'meta.plan_label', $planLabel),
                'session_status' => (string) data_get($readiness, 'meta.session_status', null),
                'readiness_state' => (string) data_get($readiness, 'summary.readiness_state', 'unknown'),
                'signature' => $signature,
            ],
            'summary' => [
                'readiness_state' => (string) data_get($readiness, 'summary.readiness_state', 'unknown'),
                'overall_score' => (float) data_get($readiness, 'summary.overall_score', 0),
                'critical_blocks_total' => count($criticalBlocks),
                'blocked_modules_total' => count($blockedModuleKeys),
                'available_modules_total' => (int) data_get($readiness, 'summary.applicable_modules_total', 0),
            ],
            'modules' => $moduleStates,
            'blocked_module_keys' => $blockedModuleKeys,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $criticalBlocks
     * @return array<string, array<string, mixed>>
     */
    private function buildModuleStates(array $criticalBlocks): array
    {
        $moduleStates = [];

        foreach ($this->onboardingStepRegistry->modules() as $module) {
            $moduleStates[$module['key']] = [
                'key' => (string) $module['key'],
                'label' => (string) $module['label'],
                'blocked' => false,
                'block_count' => 0,
                'critical_items' => [],
                'primary_block' => null,
                'message' => null,
                'cta_label' => 'Abrir onboarding',
                'cta_href' => route('onboarding.index'),
                'cta_action' => 'review',
                'cta_message' => 'Abra o onboarding para resolver as pendências deste módulo.',
                'cta_tone' => 'secondary',
            ];
        }

        foreach ($criticalBlocks as $block) {
            foreach ($this->resolveBlockModuleKeys($block) as $moduleKey) {
                if (! array_key_exists($moduleKey, $moduleStates)) {
                    continue;
                }

                $moduleStates[$moduleKey]['blocked'] = true;
                $moduleStates[$moduleKey]['block_count']++;
                $moduleStates[$moduleKey]['critical_items'][] = array_merge(
                    $this->presentCriticalBlock($block),
                    [
                        'module_key' => $moduleKey,
                        'module_label' => (string) ($moduleStates[$moduleKey]['label'] ?? $moduleKey),
                    ]
                );

                if ($moduleStates[$moduleKey]['primary_block'] === null) {
                    $moduleStates[$moduleKey]['primary_block'] = $this->presentCriticalBlock($block);
                }
            }
        }

        foreach ($moduleStates as $moduleKey => $state) {
            if (! $state['blocked']) {
                continue;
            }

            $cta = $this->contextualCtaResolverService->forBlock((array) $state['primary_block']);

            if ($cta === null) {
                $cta = [
                    'action' => 'review',
                    'label' => 'Abrir onboarding',
                    'href' => route('onboarding.index'),
                    'message' => 'Abra o onboarding para resolver as pendências deste módulo.',
                    'tone' => 'secondary',
                ];
            }

            $moduleStates[$moduleKey]['cta_action'] = (string) ($cta['action'] ?? 'review');
            $moduleStates[$moduleKey]['cta_label'] = (string) ($cta['label'] ?? 'Abrir onboarding');
            $moduleStates[$moduleKey]['cta_href'] = (string) ($cta['href'] ?? route('onboarding.index'));
            $moduleStates[$moduleKey]['cta_message'] = (string) ($cta['message'] ?? 'Abra o onboarding para resolver as pendências deste módulo.');
            $moduleStates[$moduleKey]['cta_tone'] = (string) ($cta['tone'] ?? 'secondary');
            $moduleStates[$moduleKey]['message'] = $this->buildModuleMessage($state);
        }

        return $moduleStates;
    }

    /**
     * @param array<string, mixed> $block
     * @return array<int, string>
     */
    private function resolveBlockModuleKeys(array $block): array
    {
        $type = (string) data_get($block, 'type', '');

        $moduleKeys = match ($type) {
            'config_missing' => (array) data_get($block, 'applicable_owner_modules', data_get($block, 'owner_modules', [])),
            'step_incomplete' => [(string) data_get($block, 'module_key', '')],
            default => array_values(array_filter(array_map(
                static fn ($value): string => trim((string) $value),
                (array) data_get($block, 'details.owner_modules', [])
            ))),
        };

        return array_values(array_unique(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            $moduleKeys
        ))));
    }

    /**
     * @param array<string, mixed> $block
     * @return array<string, mixed>
     */
    private function presentCriticalBlock(array $block): array
    {
        return [
            'type' => (string) data_get($block, 'type', 'step_incomplete'),
            'code' => (string) data_get($block, 'reason', data_get($block, 'key', 'onboarding_pending')),
            'key' => (string) data_get($block, 'key', ''),
            'label' => (string) data_get($block, 'label', 'Bloqueio crítico'),
            'message' => (string) data_get($block, 'message', ''),
        ];
    }

    /**
     * @param array<string, mixed> $state
     */
    private function buildModuleMessage(array $state): string
    {
        $count = (int) ($state['block_count'] ?? 0);
        $label = (string) ($state['label'] ?? 'módulo');
        $primaryBlock = (array) ($state['primary_block'] ?? []);
        $primaryMessage = trim((string) ($primaryBlock['message'] ?? ''));

        if ($count === 1 && $primaryMessage !== '') {
            return $primaryMessage;
        }

        return sprintf(
            '%d pendência(s) crítica(s) em %s.',
            $count,
            $label
        );
    }

    /**
     * @param array<string, mixed> $readiness
     * @param array<string, array<string, mixed>> $moduleStates
     */
    private function buildSignature(User $company, array $readiness, array $moduleStates): string
    {
        $payload = [
            'company_id' => $company->id,
            'company_version' => $this->assistantActivationCacheService->currentCompanyVersion($company->id),
            'plan_version' => $this->resolvePlanVersion($company),
            'module_version' => $this->assistantActivationCacheService->currentModuleVersion(),
            'catalog_version' => (string) data_get($readiness, 'meta.catalog_version', $this->catalogVersion()),
            'readiness_state' => (string) data_get($readiness, 'summary.readiness_state', 'unknown'),
            'overall_score' => (float) data_get($readiness, 'summary.overall_score', 0),
            'module_state_hash' => collect($moduleStates)
                ->map(fn (array $state): array => [
                    'key' => $state['key'],
                    'blocked' => (bool) $state['blocked'],
                    'block_count' => (int) $state['block_count'],
                    'primary_code' => (string) data_get($state, 'primary_block.code', ''),
                ])
                ->values()
                ->all(),
        ];

        return hash('sha256', json_encode($payload));
    }

    private function resolvePlanVersion(User $company): string
    {
        $planId = (int) ($company->active_plan ?? 0);

        if ($planId <= 0) {
            return 'none';
        }

        return $this->assistantActivationCacheService->currentPlanVersion($planId);
    }

    /**
     * @param array<int, string>|null $planModules
     */
    private function cacheKey(User $company, ?array $planModules, ?string $planLabel): string
    {
        $payload = [
            'company_id' => $company->id,
            'company_version' => $this->assistantActivationCacheService->currentCompanyVersion($company->id),
            'plan_version' => $this->resolvePlanVersion($company),
            'module_version' => $this->assistantActivationCacheService->currentModuleVersion(),
            'catalog_version' => $this->catalogVersion(),
            'plan_label' => $planLabel,
            'plan_modules' => $planModules ? array_values($planModules) : null,
        ];

        return 'assistant_activation:menu_state:' . hash('sha256', json_encode($payload));
    }

    private function ttlMinutes(): int
    {
        return max(1, (int) config('assistant_activation.cache.ttl_minutes', 15));
    }
}
