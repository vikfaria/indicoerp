<?php

namespace App\Services\AssistantActivation;

class OnboardingStepRegistry
{
    public function catalogVersion(): string
    {
        return (string) config('assistant_activation_onboarding_steps.catalog_version', 'unknown');
    }

    public function modules(): array
    {
        $modules = (array) config('assistant_activation_onboarding_steps.modules', []);

        return array_values(array_map(function (array $module, string $moduleKey): array {
            return $this->normalizeModule($moduleKey, $module);
        }, $modules, array_keys($modules)));
    }

    public function findModule(string $moduleKey): ?array
    {
        return collect($this->modules())
            ->firstWhere('key', $moduleKey);
    }

    public function findStep(string $stepKey): ?array
    {
        foreach ($this->modules() as $module) {
            foreach ($module['steps'] as $step) {
                if ($step['key'] === $stepKey) {
                    return $step + [
                        'module_key' => $module['key'],
                        'module_label' => $module['label'],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Build a plan-aware onboarding report using the modules enabled for that plan.
     *
     * @param array<int, string> $planModules
     */
    public function buildPlanReport(array $planModules, ?string $planLabel = null): array
    {
        $planModules = $this->normalizeRefs($planModules);
        $modules = $this->modules();
        $allSteps = collect();

        $normalizedModules = array_map(function (array $module) use ($planModules, $allSteps): array {
            $steps = array_map(function (array $step) use ($module, $planModules, $allSteps): array {
                $stepModuleRefs = $step['module_refs'] ?: $module['technical_modules'];
                $availability = $this->resolveAvailability($stepModuleRefs, $planModules);

                $normalizedStep = $step + $availability;
                $allSteps->push($normalizedStep);

                return $normalizedStep;
            }, $module['steps']);

            $availableSteps = collect($steps)->where('available', true);

            return array_merge($module, [
                'steps' => $steps,
                'available_step_count' => $availableSteps->count(),
                'unavailable_step_count' => collect($steps)->where('available', false)->count(),
                'available_required_step_count' => $availableSteps->where('required', true)->count(),
                'plan_modules' => $planModules,
            ]);
        }, $modules);

        return [
            'meta' => [
                'catalog_version' => $this->catalogVersion(),
                'generated_at' => now()->toIso8601String(),
                'plan_label' => $planLabel,
                'plan_modules' => $planModules,
            ],
            'summary' => [
                'modules_total' => count($normalizedModules),
                'steps_total' => $allSteps->count(),
                'available_steps_total' => $allSteps->where('available', true)->count(),
                'unavailable_steps_total' => $allSteps->where('available', false)->count(),
                'required_steps_total' => $allSteps->where('required', true)->count(),
                'available_required_steps_total' => $allSteps->where('required', true)->where('available', true)->count(),
                'technical_modules_total' => collect($normalizedModules)->flatMap(fn (array $module) => $module['technical_modules'])->unique()->values()->count(),
            ],
            'modules' => $normalizedModules,
        ];
    }

    public function buildReport(): array
    {
        $modules = $this->modules();
        $steps = collect($modules)->flatMap(fn (array $module) => $module['steps']);
        $technicalModules = collect($modules)->flatMap(fn (array $module) => $module['technical_modules'])->unique()->values();

        return [
            'meta' => [
                'catalog_version' => $this->catalogVersion(),
                'generated_at' => now()->toIso8601String(),
            ],
            'summary' => [
                'modules_total' => count($modules),
                'steps_total' => $steps->count(),
                'required_steps_total' => $steps->where('required', true)->count(),
                'technical_modules_total' => $technicalModules->count(),
            ],
            'modules' => $modules,
        ];
    }

    private function normalizeModule(string $moduleKey, array $module): array
    {
        $steps = array_values(array_map(function (array $step): array {
            return $this->normalizeStep($step);
        }, (array) ($module['steps'] ?? [])));

        usort($steps, fn (array $a, array $b): int => ($a['order'] <=> $b['order']) ?: strcmp($a['key'], $b['key']));

        $technicalModules = array_values(array_unique(array_filter(array_map(
            fn ($moduleRef) => trim((string) $moduleRef),
            (array) ($module['technical_modules'] ?? [])
        ))));

        $requiredPermissions = array_values(array_unique(array_filter(array_map(
            fn ($permission) => trim((string) $permission),
            (array) ($module['required_permissions'] ?? [])
        ))));

        $requiredConfigKeys = array_values(array_unique(array_filter(array_map(
            fn ($configKey) => trim((string) $configKey),
            (array) ($module['required_config_keys'] ?? [])
        ))));

        return [
            'key' => $moduleKey,
            'label' => (string) ($module['label'] ?? $moduleKey),
            'priority' => (int) ($module['priority'] ?? 0),
            'technical_modules' => $technicalModules,
            'required_permissions' => $requiredPermissions,
            'required_config_keys' => $requiredConfigKeys,
            'step_count' => count($steps),
            'required_step_count' => collect($steps)->where('required', true)->count(),
            'steps' => $steps,
        ];
    }

    private function normalizeStep(array $step): array
    {
        return [
            'key' => (string) ($step['key'] ?? ''),
            'checklist_key' => (string) ($step['checklist_key'] ?? ''),
            'label' => (string) ($step['label'] ?? ''),
            'description' => (string) ($step['description'] ?? ''),
            'order' => (int) ($step['order'] ?? 0),
            'required' => (bool) ($step['required'] ?? true),
            'module_refs' => array_values(array_unique(array_filter(array_map(
                fn ($moduleRef) => trim((string) $moduleRef),
                (array) ($step['module_refs'] ?? [])
            )))),
            'permissions' => array_values(array_unique(array_filter(array_map(
                fn ($permission) => trim((string) $permission),
                (array) ($step['permissions'] ?? [])
            )))),
            'config_keys' => array_values(array_unique(array_filter(array_map(
                fn ($configKey) => trim((string) $configKey),
                (array) ($step['config_keys'] ?? [])
            )))),
            'evidence' => (string) ($step['evidence'] ?? ''),
        ];
    }

    /**
     * @param array<int, string> $refs
     * @param array<int, string> $planModules
     */
    private function resolveAvailability(array $refs, array $planModules): array
    {
        $missingRefs = array_values(array_diff($this->normalizeRefs($refs), $planModules));

        return [
            'available' => $missingRefs === [],
            'availability_state' => $missingRefs === [] ? 'available' : 'unavailable',
            'missing_module_refs' => $missingRefs,
        ];
    }

    /**
     * @param array<int, string> $refs
     * @return array<int, string>
     */
    private function normalizeRefs(array $refs): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($ref) => trim((string) $ref),
            $refs
        ))));
    }
}
