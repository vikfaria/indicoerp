<?php

namespace App\Services\AssistantActivation;

class PermissionMatrixService
{
    public function catalogVersion(): string
    {
        return (string) config('assistant_activation_permissions.catalog_version', 'unknown');
    }

    public function areas(): array
    {
        $areas = (array) config('assistant_activation_permissions.areas', []);

        return array_values(array_map(function (array $area): array {
            return $this->normalizeArea($area);
        }, $areas));
    }

    public function roleTemplates(): array
    {
        $templates = (array) config('assistant_activation_permissions.role_templates', []);

        return array_values(array_map(function (array $template): array {
            return $this->normalizeRoleTemplate($template);
        }, $templates));
    }

    public function findRoleTemplate(string $roleName): ?array
    {
        return collect($this->roleTemplates())
            ->firstWhere('name', $roleName);
    }

    public function buildReport(): array
    {
        $areas = $this->areas();
        $roleTemplates = $this->roleTemplates();
        $areaPermissions = collect($areas)->flatMap(fn (array $area) => $area['permissions']);
        $rolePermissions = collect($roleTemplates)->flatMap(fn (array $template) => $template['permissions']);

        $areaSummary = [];
        foreach ($areas as $area) {
            $areaSummary[] = [
                'key' => $area['key'],
                'label' => $area['label'],
                'permission_count' => count($area['permissions']),
                'permissions' => $area['permissions'],
            ];
        }

        $roleSummary = [];
        foreach ($roleTemplates as $template) {
            $roleSummary[] = [
                'name' => $template['name'],
                'label' => $template['label'],
                'permission_count' => count($template['permissions']),
                'permissions' => $template['permissions'],
            ];
        }

        return [
            'meta' => [
                'catalog_version' => $this->catalogVersion(),
                'generated_at' => now()->toIso8601String(),
            ],
            'summary' => [
                'areas_total' => count($areas),
                'role_templates_total' => count($roleTemplates),
                'area_permissions_total' => $areaPermissions->unique()->count(),
                'role_permissions_total' => $rolePermissions->unique()->count(),
                'permissions_total' => $areaPermissions->merge($rolePermissions)->unique()->count(),
            ],
            'areas' => $areaSummary,
            'role_templates' => $roleSummary,
        ];
    }

    private function normalizeArea(array $area): array
    {
        return [
            'key' => (string) ($area['key'] ?? ''),
            'label' => (string) ($area['label'] ?? ''),
            'permissions' => array_values(array_filter(array_map(
                fn ($permission) => trim((string) $permission),
                (array) ($area['permissions'] ?? [])
            ))),
        ];
    }

    private function normalizeRoleTemplate(array $template): array
    {
        return [
            'name' => (string) ($template['name'] ?? ''),
            'label' => (string) ($template['label'] ?? ''),
            'permissions' => array_values(array_filter(array_map(
                fn ($permission) => trim((string) $permission),
                (array) ($template['permissions'] ?? [])
            ))),
        ];
    }
}
