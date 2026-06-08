<?php

namespace App\Services\AssistantActivation;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ModuleCatalogService
{
    public function contractVersion(): string
    {
        return (string) config('assistant_activation_modules.catalog_version', 'unknown');
    }

    public function modules(): array
    {
        $modules = (array) config('assistant_activation_modules.modules', []);

        return array_values(array_map(function (array $module): array {
            return $this->normalizeModule($module);
        }, $modules));
    }

    public function indexedModules(): array
    {
        return collect($this->modules())
            ->keyBy('key')
            ->all();
    }

    public function find(string $moduleKey): ?array
    {
        return $this->indexedModules()[$moduleKey] ?? null;
    }

    public function buildReport(): array
    {
        $modules = $this->modules();

        $byType = [];
        $byMenuGroup = [];
        $byPackage = [];

        foreach ($modules as $module) {
            $type = $module['type'];
            $byType[$type] = ($byType[$type] ?? 0) + 1;

            foreach ($module['menu_groups'] as $menuGroup) {
                $byMenuGroup[$menuGroup] = ($byMenuGroup[$menuGroup] ?? 0) + 1;
            }

            if ($module['package_key'] !== null) {
                $byPackage[$module['package_key']] = ($byPackage[$module['package_key']] ?? 0) + 1;
            }
        }

        return [
            'meta' => [
                'catalog_version' => $this->contractVersion(),
                'generated_at' => now()->toIso8601String(),
            ],
            'summary' => [
                'modules_total' => count($modules),
                'by_type' => $byType,
                'by_menu_group' => $byMenuGroup,
                'package_modules_total' => count($byPackage),
            ],
            'modules' => $modules,
        ];
    }

    private function normalizeModule(array $module): array
    {
        $routePrefixes = array_values(array_filter(array_map(
            fn ($prefix) => trim((string) $prefix),
            (array) Arr::get($module, 'route_prefixes', [])
        )));

        $menuGroups = array_values(array_filter(array_map(
            fn ($group) => trim((string) $group),
            (array) Arr::get($module, 'menu_groups', [])
        )));

        return [
            'key' => (string) Arr::get($module, 'key'),
            'label' => (string) Arr::get($module, 'label'),
            'type' => (string) Arr::get($module, 'type', 'core'),
            'package_key' => Arr::get($module, 'package_key'),
            'permission_module' => Arr::get($module, 'permission_module'),
            'plan_gate' => Arr::get($module, 'plan_gate'),
            'route_prefixes' => $routePrefixes,
            'menu_groups' => $menuGroups,
            'notes' => Arr::get($module, 'notes'),
        ];
    }
}
