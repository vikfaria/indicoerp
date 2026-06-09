<?php

namespace App\Services\AssistantActivation;

class ReadinessScoreService
{
    public function __construct(
        private readonly OnboardingStepRegistry $onboardingStepRegistry
    ) {
    }

    public function catalogVersion(): string
    {
        return (string) config('assistant_activation_readiness.catalog_version', 'unknown');
    }

    public function formula(): array
    {
        return (array) config('assistant_activation_readiness.formula', []);
    }

    public function moduleWeights(): array
    {
        $weights = (array) config('assistant_activation_readiness.module_weights', []);

        return array_values(array_map(function (array $module): array {
            return $this->normalizeEntry($module);
        }, $weights));
    }

    public function criticalConfigWeights(): array
    {
        $weights = (array) config('assistant_activation_readiness.critical_config_keys', []);

        return array_values(array_map(function (array $configKey): array {
            return $this->normalizeEntry($configKey);
        }, $weights));
    }

    /**
     * @param array<int, string> $completedStepKeys
     * @param array<int, string> $availableConfigKeys
     */
    public function score(array $completedStepKeys = [], array $availableConfigKeys = []): array
    {
        $completedStepKeys = array_values(array_unique(array_filter(array_map(
            fn ($key) => trim((string) $key),
            $completedStepKeys
        ))));
        $availableConfigKeys = array_values(array_unique(array_filter(array_map(
            fn ($key) => trim((string) $key),
            $availableConfigKeys
        ))));

        $modules = $this->onboardingStepRegistry->modules();
        $moduleWeights = collect($this->moduleWeights())->keyBy('key');
        $criticalConfigWeights = collect($this->criticalConfigWeights())->keyBy('key');
        $formula = $this->formula();

        $moduleBreakdown = [];
        $moduleScoreWeightedSum = 0.0;
        $moduleWeightTotal = 0.0;

        foreach ($modules as $module) {
            $weight = (float) ($moduleWeights->get($module['key'], ['weight' => 0])['weight'] ?? 0);
            $moduleWeightTotal += $weight;

            $completedSteps = collect($module['steps'])
                ->filter(fn (array $step): bool => in_array($step['key'], $completedStepKeys, true))
                ->values();

            $moduleScore = $module['step_count'] > 0
                ? round(($completedSteps->count() / $module['step_count']) * 100, 2)
                : 100.0;

            $moduleScoreWeightedSum += ($moduleScore * $weight);

            $moduleBreakdown[] = [
                'key' => $module['key'],
                'label' => $module['label'],
                'weight' => $weight,
                'score' => $moduleScore,
                'completed_steps' => $completedSteps->count(),
                'total_steps' => $module['step_count'],
                'completed_step_keys' => $completedSteps->pluck('key')->values()->all(),
            ];
        }

        $moduleComponentScore = $moduleWeightTotal > 0
            ? round($moduleScoreWeightedSum / $moduleWeightTotal, 2)
            : 100.0;

        $criticalWeightTotal = 0.0;
        $criticalWeightCompleted = 0.0;
        $criticalBreakdown = [];

        foreach ($criticalConfigWeights as $entry) {
            $weight = (float) ($entry['weight'] ?? 0);
            $criticalWeightTotal += $weight;

            $isCompleted = in_array($entry['key'], $availableConfigKeys, true);
            if ($isCompleted) {
                $criticalWeightCompleted += $weight;
            }

            $criticalBreakdown[] = [
                'key' => $entry['key'],
                'label' => $entry['label'],
                'weight' => $weight,
                'completed' => $isCompleted,
                'description' => $entry['description'],
            ];
        }

        $criticalConfigScore = $criticalWeightTotal > 0
            ? round(($criticalWeightCompleted / $criticalWeightTotal) * 100, 2)
            : 100.0;

        $moduleComponentWeight = (float) ($formula['module_component_weight'] ?? 70);
        $criticalComponentWeight = (float) ($formula['critical_config_component_weight'] ?? 30);

        $overallScore = round(
            ($moduleComponentScore * ($moduleComponentWeight / 100))
            + ($criticalConfigScore * ($criticalComponentWeight / 100)),
            2
        );

        return [
            'meta' => [
                'catalog_version' => $this->catalogVersion(),
                'generated_at' => now()->toIso8601String(),
            ],
            'formula' => $formula,
            'summary' => [
                'module_component_score' => $moduleComponentScore,
                'critical_config_score' => $criticalConfigScore,
                'overall_score' => $overallScore,
                'module_component_weight' => $moduleComponentWeight,
                'critical_config_component_weight' => $criticalComponentWeight,
                'ready_threshold' => (int) ($formula['ready_threshold'] ?? 80),
                'warning_threshold' => (int) ($formula['warning_threshold'] ?? 60),
                'blocked_threshold' => (int) ($formula['blocked_threshold'] ?? 40),
            ],
            'modules' => $moduleBreakdown,
            'critical_config_keys' => $criticalBreakdown,
        ];
    }

    public function buildReport(): array
    {
        $modules = $this->onboardingStepRegistry->modules();
        $allStepKeys = collect($modules)->flatMap(fn (array $module) => $module['steps'])->pluck('key')->values()->all();
        $allConfigKeys = collect($this->criticalConfigWeights())->pluck('key')->values()->all();

        $blankScore = $this->score([], []);
        $completeScore = $this->score($allStepKeys, $allConfigKeys);

        return [
            'meta' => [
                'catalog_version' => $this->catalogVersion(),
                'generated_at' => now()->toIso8601String(),
            ],
            'formula' => $this->formula(),
            'summary' => [
                'module_weights_total' => collect($this->moduleWeights())->sum('weight'),
                'critical_config_weights_total' => collect($this->criticalConfigWeights())->sum('weight'),
                'module_component_weight' => (float) ($this->formula()['module_component_weight'] ?? 70),
                'critical_config_component_weight' => (float) ($this->formula()['critical_config_component_weight'] ?? 30),
                'ready_threshold' => (int) (($this->formula()['ready_threshold'] ?? 80)),
                'warning_threshold' => (int) (($this->formula()['warning_threshold'] ?? 60)),
                'blocked_threshold' => (int) (($this->formula()['blocked_threshold'] ?? 40)),
            ],
            'module_weights' => $this->moduleWeights(),
            'critical_config_keys' => $this->criticalConfigWeights(),
            'examples' => [
                'blank' => $blankScore['summary'],
                'complete' => $completeScore['summary'],
            ],
        ];
    }

    private function normalizeEntry(array $entry): array
    {
        return [
            'key' => (string) ($entry['key'] ?? ''),
            'label' => __((string) ($entry['label'] ?? '')),
            'weight' => (float) ($entry['weight'] ?? 0),
            'description' => (string) ($entry['description'] ?? ''),
        ];
    }
}
