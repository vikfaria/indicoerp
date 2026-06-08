<?php

namespace App\Services\AssistantActivation;

use Illuminate\Support\Arr;

class FeatureCatalogService
{
    public function __construct(
        private readonly ModuleCatalogService $moduleCatalogService
    ) {
    }

    public function catalogVersion(): string
    {
        return (string) config('assistant_activation_features.catalog_version', 'unknown');
    }

    public function features(): array
    {
        $features = (array) config('assistant_activation_features.features', []);

        return array_values(array_map(function (array $feature): array {
            return $this->normalizeFeature($feature);
        }, $features));
    }

    public function indexedFeatures(): array
    {
        return collect($this->features())
            ->keyBy('key')
            ->all();
    }

    public function find(string $featureKey): ?array
    {
        return $this->indexedFeatures()[$featureKey] ?? null;
    }

    public function buildReport(): array
    {
        $features = $this->features();
        $domains = [];
        $moduleNames = [];
        $permissionNames = [];
        $configKeys = [];
        $packageModules = [];

        foreach ($features as $feature) {
            $domainKey = $feature['domain'] !== '' ? $feature['domain'] : 'general';

            if (!isset($domains[$domainKey])) {
                $domains[$domainKey] = [
                    'key' => $domainKey,
                    'label' => $this->labelForDomain($domainKey),
                    'features_total' => 0,
                    'modules' => [],
                    'permissions' => [],
                    'config_keys' => [],
                ];
            }

            $domains[$domainKey]['features_total']++;

            foreach ($feature['modules'] as $module) {
                $moduleNames[$module] = true;
                $domains[$domainKey]['modules'][$module] = true;

                $moduleDefinition = $this->moduleCatalogService->find($module);
                if (is_array($moduleDefinition) && ($moduleDefinition['type'] ?? null) === 'package') {
                    $packageModules[$module] = true;
                }
            }

            foreach (array_merge($feature['permissions_all'], $feature['permissions_any']) as $permission) {
                $permissionNames[$permission] = true;
                $domains[$domainKey]['permissions'][$permission] = true;
            }

            foreach ($feature['config_keys'] as $configKey) {
                $configKeys[$configKey] = true;
                $domains[$domainKey]['config_keys'][$configKey] = true;
            }
        }

        $domainReports = [];
        foreach ($domains as $domain) {
            $domainReports[] = [
                'key' => $domain['key'],
                'label' => $domain['label'],
                'features_total' => $domain['features_total'],
                'modules' => array_values(array_keys($domain['modules'])),
                'permissions' => array_values(array_keys($domain['permissions'])),
                'config_keys' => array_values(array_keys($domain['config_keys'])),
            ];
        }

        usort($domainReports, fn (array $left, array $right) => strcmp($left['key'], $right['key']));

        return [
            'meta' => [
                'catalog_version' => $this->catalogVersion(),
                'generated_at' => now()->toIso8601String(),
            ],
            'summary' => [
                'features_total' => count($features),
                'domains_total' => count($domainReports),
                'modules_total' => count($moduleNames),
                'permissions_total' => count($permissionNames),
                'config_keys_total' => count($configKeys),
                'package_modules_total' => count($packageModules),
            ],
            'domains' => $domainReports,
            'features' => $features,
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

    private function labelForDomain(string $domainKey): string
    {
        return match ($domainKey) {
            'billing' => 'Facturação',
            'accounting' => 'Contabilidade',
            'treasury' => 'Tesouraria',
            'inventory' => 'Inventário',
            'hr' => 'Recursos Humanos',
            default => ucfirst($domainKey),
        };
    }
}
