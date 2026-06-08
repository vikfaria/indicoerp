<?php

namespace App\Services\AssistantActivation;

class PlanLimitMatrixService
{
    public function catalogVersion(): string
    {
        return (string) config('assistant_activation_limits.catalog_version', 'unknown');
    }

    public function dimensions(): array
    {
        $dimensions = (array) config('assistant_activation_limits.dimensions', []);

        return array_values(array_map(function (array $dimension): array {
            return $this->normalizeDimension($dimension);
        }, $dimensions));
    }

    public function families(): array
    {
        $families = (array) config('assistant_activation_limits.plan_families', []);
        $planFamilies = (array) config('assistant_activation.plan_families', []);

        $normalized = [];

        foreach ($families as $familyKey => $family) {
            $normalized[$familyKey] = $this->normalizeFamily(
                (string) $familyKey,
                (array) $family,
                (array) ($planFamilies[$familyKey] ?? [])
            );
        }

        return $normalized;
    }

    public function findFamily(string $familyKey): ?array
    {
        return $this->families()[$familyKey] ?? null;
    }

    public function resolveFamilyLimits(string $familyKey, ?array $planSnapshot = null): array
    {
        $family = $this->findFamily($familyKey);

        if (! $family) {
            return [
                'family' => $familyKey,
                'label' => $familyKey,
                'notes' => null,
                'source_plan_id' => $planSnapshot['id'] ?? null,
                'source_plan_name' => $planSnapshot['name'] ?? null,
                'limits' => [],
            ];
        }

        $dimensions = collect($this->dimensions())->keyBy('key');
        $limits = [];

        foreach ($family['limits'] as $dimensionKey => $defaultValue) {
            $dimension = $dimensions->get($dimensionKey, [
                'key' => $dimensionKey,
                'label' => $dimensionKey,
                'unit' => '',
                'source' => 'contract',
                'field' => null,
                'enforcement' => 'manual',
                'description' => null,
            ]);

            $value = $defaultValue;
            $resolvedFrom = 'default';

            if (($dimension['source'] ?? null) === 'plan_field' && is_array($planSnapshot)) {
                $field = $dimension['field'] ?? null;
                $snapshotValue = $field === 'number_of_users'
                    ? ($planSnapshot['users_limit'] ?? null)
                    : ($field === 'storage_limit' ? ($planSnapshot['storage_limit_kb'] ?? null) : null);

                if ($snapshotValue !== null && $snapshotValue !== '' && (int) $snapshotValue !== 0) {
                    $value = is_numeric($snapshotValue) ? (int) $snapshotValue : $snapshotValue;
                    $resolvedFrom = 'plan';
                }
            }

            $limits[$dimensionKey] = [
                'key' => $dimension['key'],
                'label' => $dimension['label'],
                'value' => $value,
                'default_value' => $defaultValue,
                'unit' => $dimension['unit'],
                'source' => $dimension['source'],
                'enforcement' => $dimension['enforcement'],
                'description' => $dimension['description'],
                'resolved_from' => $resolvedFrom,
            ];
        }

        return [
            'family' => $family['key'],
            'label' => $family['label'],
            'description' => $family['description'],
            'notes' => $family['notes'],
            'source_plan_id' => $planSnapshot['id'] ?? null,
            'source_plan_name' => $planSnapshot['name'] ?? null,
            'limits' => $limits,
        ];
    }

    /**
     * @param array<int, array<string, mixed>>|null $planSnapshots
     */
    public function buildReport(?array $planSnapshots = null): array
    {
        $dimensions = $this->dimensions();
        $families = $this->families();
        $familyReports = [];

        foreach ($families as $familyKey => $family) {
            $planSnapshot = $this->findSnapshotForFamily($familyKey, $planSnapshots);
            $familyReports[] = $this->resolveFamilyLimits($familyKey, $planSnapshot);
        }

        return [
            'meta' => [
                'catalog_version' => $this->catalogVersion(),
                'generated_at' => now()->toIso8601String(),
            ],
            'summary' => [
                'families_total' => count($families),
                'dimensions_total' => count($dimensions),
                'runtime_dimensions_total' => collect($dimensions)->where('source', 'plan_field')->count(),
                'contract_dimensions_total' => collect($dimensions)->where('source', 'contract')->count(),
                'families_with_plan_snapshot_total' => collect($familyReports)->filter(fn (array $family) => $family['source_plan_id'] !== null)->count(),
            ],
            'dimensions' => $dimensions,
            'families' => $familyReports,
        ];
    }

    private function findSnapshotForFamily(string $familyKey, ?array $planSnapshots): ?array
    {
        if (! is_array($planSnapshots)) {
            return null;
        }

        foreach ($planSnapshots as $snapshot) {
            if ((string) ($snapshot['family'] ?? '') === $familyKey) {
                return $snapshot;
            }
        }

        return null;
    }

    private function normalizeDimension(array $dimension): array
    {
        return [
            'key' => (string) ($dimension['key'] ?? ''),
            'label' => (string) ($dimension['label'] ?? ''),
            'unit' => (string) ($dimension['unit'] ?? ''),
            'source' => (string) ($dimension['source'] ?? 'contract'),
            'field' => $dimension['field'] ?? null,
            'enforcement' => (string) ($dimension['enforcement'] ?? 'manual'),
            'description' => (string) ($dimension['description'] ?? ''),
        ];
    }

    private function normalizeFamily(string $familyKey, array $family, array $contractFamily): array
    {
        return [
            'key' => $familyKey,
            'label' => (string) ($contractFamily['label'] ?? ($family['label'] ?? $familyKey)),
            'description' => (string) ($contractFamily['description'] ?? ''),
            'notes' => (string) ($family['notes'] ?? ''),
            'limits' => $this->normalizeLimits((array) ($family['limits'] ?? [])),
        ];
    }

    private function normalizeLimits(array $limits): array
    {
        $normalized = [];

        foreach ($limits as $key => $value) {
            $normalized[(string) $key] = is_numeric($value) ? (int) $value : $value;
        }

        return $normalized;
    }
}
