<?php

namespace App\Services\AssistantActivation;

use Illuminate\Support\Str;

class ModuleFeatureBridgeService
{
    public function __construct(
        private readonly ModuleCatalogService $moduleCatalogService,
        private readonly FeatureCatalogService $featureCatalogService
    ) {
    }

    public function catalogVersion(): string
    {
        return $this->featureCatalogService->catalogVersion();
    }

    public function featureKeysForModuleReference(string $moduleReference): array
    {
        return $this->resolveFeatureKeysForModuleReference($moduleReference);
    }

    public function moduleKeysForReference(string $moduleReference): array
    {
        return $this->resolveModuleKeysForReference(
            $moduleReference,
            $this->buildModuleAliasIndex()
        );
    }

    public function moduleKeysForFeature(string $featureKey): array
    {
        $feature = $this->featureCatalogService->find($featureKey);

        if (! $feature) {
            return [];
        }

        $moduleIndex = $this->buildModuleAliasIndex();
        $moduleKeys = [];

        foreach ($feature['modules'] as $moduleReference) {
            foreach ($this->resolveModuleKeysForReference($moduleReference, $moduleIndex) as $moduleKey) {
                $moduleKeys[$moduleKey] = true;
            }
        }

        return array_values(array_keys($moduleKeys));
    }

    public function buildReport(): array
    {
        $modules = $this->moduleCatalogService->modules();
        $features = $this->featureCatalogService->features();
        $moduleIndex = $this->buildModuleAliasIndex($modules);
        $moduleFeatureMap = [];
        $featureModuleMap = [];
        $linksTotal = 0;

        foreach ($features as $feature) {
            $featureModuleKeys = [];

            foreach ($feature['modules'] as $moduleReference) {
                foreach ($this->resolveModuleKeysForReference($moduleReference, $moduleIndex) as $moduleKey) {
                    if (! isset($moduleFeatureMap[$moduleKey])) {
                        $moduleFeatureMap[$moduleKey] = [];
                    }

                    $moduleFeatureMap[$moduleKey][$feature['key']] = true;
                    $featureModuleKeys[$moduleKey] = true;
                    $linksTotal++;
                }
            }

            $featureModuleMap[$feature['key']] = array_values(array_keys($featureModuleKeys));
        }

        $moduleReports = [];
        $modulesWithFeatureLinks = 0;
        $linkedFeatureKeys = [];

        foreach ($modules as $module) {
            $featureKeys = array_values(array_keys($moduleFeatureMap[$module['key']] ?? []));
            sort($featureKeys);

            if ($featureKeys) {
                $modulesWithFeatureLinks++;
            }

            foreach ($featureKeys as $featureKey) {
                $linkedFeatureKeys[$featureKey] = true;
            }

            $moduleReports[] = [
                'key' => $module['key'],
                'label' => $module['label'],
                'type' => $module['type'],
                'package_key' => $module['package_key'],
                'permission_module' => $module['permission_module'],
                'route_prefixes' => $module['route_prefixes'],
                'menu_groups' => $module['menu_groups'],
                'feature_keys' => $featureKeys,
                'feature_count' => count($featureKeys),
            ];
        }

        $featureReports = [];
        $featuresWithModuleLinks = 0;
        $linkedModuleKeys = [];

        foreach ($features as $feature) {
            $moduleKeys = array_values(array_unique($featureModuleMap[$feature['key']] ?? []));
            sort($moduleKeys);

            if ($moduleKeys) {
                $featuresWithModuleLinks++;
            }

            foreach ($moduleKeys as $moduleKey) {
                $linkedModuleKeys[$moduleKey] = true;
            }

            $featureReports[] = [
                'key' => $feature['key'],
                'label' => $feature['label'],
                'domain' => $feature['domain'],
                'modules' => $feature['modules'],
                'module_keys' => $moduleKeys,
                'module_count' => count($moduleKeys),
                'permissions_all' => $feature['permissions_all'],
                'permissions_any' => $feature['permissions_any'],
                'config_keys' => $feature['config_keys'],
                'route_prefixes' => $feature['route_prefixes'],
                'menu_groups' => $feature['menu_groups'],
                'notes' => $feature['notes'],
            ];
        }

        return [
            'meta' => [
                'catalog_version' => $this->catalogVersion(),
                'generated_at' => now()->toIso8601String(),
            ],
            'summary' => [
                'modules_total' => count($modules),
                'modules_with_feature_links_total' => $modulesWithFeatureLinks,
                'features_total' => count($features),
                'features_with_module_links_total' => $featuresWithModuleLinks,
                'links_total' => $linksTotal,
                'linked_module_keys_total' => count($linkedModuleKeys),
                'linked_feature_keys_total' => count($linkedFeatureKeys),
            ],
            'modules' => $moduleReports,
            'features' => $featureReports,
        ];
    }

    private function resolveFeatureKeysForModuleReference(string $moduleReference): array
    {
        $moduleIndex = $this->buildModuleAliasIndex();
        $moduleKeys = $this->resolveModuleKeysForReference($moduleReference, $moduleIndex);

        if ($moduleKeys === []) {
            return [];
        }

        $featureKeys = [];

        foreach ($this->featureCatalogService->features() as $feature) {
            foreach ($feature['modules'] as $featureModuleReference) {
                foreach ($this->resolveModuleKeysForReference($featureModuleReference, $moduleIndex) as $candidateModuleKey) {
                    if (! in_array($candidateModuleKey, $moduleKeys, true)) {
                        continue;
                    }

                    $featureKeys[$feature['key']] = true;
                }
            }
        }

        return array_values(array_keys($featureKeys));
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function buildModuleAliasIndex(?array $modules = null): array
    {
        $modules ??= $this->moduleCatalogService->modules();
        $index = [];

        foreach ($modules as $module) {
            $aliases = array_filter([
                $module['key'] ?? null,
                $module['package_key'] ?? null,
                $module['permission_module'] ?? null,
            ]);

            foreach ($aliases as $alias) {
                $normalized = $this->normalizeIdentifier((string) $alias);
                if ($normalized === '') {
                    continue;
                }

                $index[$normalized] ??= [];
                $index[$normalized][] = (string) $module['key'];
            }
        }

        foreach ($index as $normalized => $moduleKeys) {
            $index[$normalized] = array_values(array_unique($moduleKeys));
        }

        return $index;
    }

    /**
     * @param array<string, array<int, string>> $moduleIndex
     * @return array<int, string>
     */
    private function resolveModuleKeysForReference(string $moduleReference, array $moduleIndex): array
    {
        $normalized = $this->normalizeIdentifier($moduleReference);

        return $moduleIndex[$normalized] ?? [];
    }

    private function normalizeIdentifier(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->toString();
    }
}
