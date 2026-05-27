<?php

namespace App\Services;

use App\Models\CostCenter;
use App\Models\Setting;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\PayrollEntry;

class PayrollCostCenterAllocatorService
{
    private const MODE_CONFIGURED = 'configured';
    private const MODE_CONFIGURED_WITH_HEURISTIC = 'configured_with_heuristic';
    private const SETTING_KEY_MODE = 'mz_payroll_cost_center_mapping_mode';
    private const SETTING_KEY_RULES = 'mz_payroll_cost_center_mapping_rules';

    /**
     * @var array<int, array<string, array<string, CostCenter>>>
     */
    private array $costCenterIndexByCompany = [];
    private array $configurationCache = [];

    public function resolveCostCenterForEntry(PayrollEntry $entry, int $companyId): ?CostCenter
    {
        $entry->loadMissing([
            'employee.department:id,department_name',
            'employee.branch:id,branch_name',
        ]);

        return $this->resolveCostCenterForEmployee($entry->employee, $companyId);
    }

    public function resolveCostCenterForEmployee(?Employee $employee, int $companyId): ?CostCenter
    {
        if (!$employee) {
            return null;
        }

        $configuration = $this->getConfiguration($companyId);
        $index = $this->indexForCompany($companyId);
        $department = $employee->department;
        $branch = $employee->branch;

        $mappedCostCenter = $this->resolveFromConfiguredRules($employee, $index, $configuration['mappings']);
        if ($mappedCostCenter) {
            return $mappedCostCenter;
        }

        if ($configuration['mode'] === self::MODE_CONFIGURED_WITH_HEURISTIC) {
            return $this->resolveFromHeuristic($department?->department_name, $branch?->branch_name, $department?->id, $branch?->id, $index);
        }

        return null;
    }

    public function getConfiguration(int $companyId): array
    {
        if (isset($this->configurationCache[$companyId])) {
            return $this->configurationCache[$companyId];
        }

        $settings = Setting::query()
            ->where('created_by', $companyId)
            ->whereIn('key', [self::SETTING_KEY_MODE, self::SETTING_KEY_RULES])
            ->pluck('value', 'key');

        $mode = (string) ($settings[self::SETTING_KEY_MODE] ?? self::MODE_CONFIGURED_WITH_HEURISTIC);
        if (!in_array($mode, [self::MODE_CONFIGURED, self::MODE_CONFIGURED_WITH_HEURISTIC], true)) {
            $mode = self::MODE_CONFIGURED_WITH_HEURISTIC;
        }

        $rawMappings = json_decode((string) ($settings[self::SETTING_KEY_RULES] ?? '{}'), true);
        if (!is_array($rawMappings)) {
            $rawMappings = [];
        }

        $configuration = [
            'mode' => $mode,
            'mappings' => $this->normalizeMappings($rawMappings),
        ];

        $this->configurationCache[$companyId] = $configuration;

        return $configuration;
    }

    public function saveConfiguration(int $companyId, array $configuration): array
    {
        $mode = (string) ($configuration['mode'] ?? self::MODE_CONFIGURED_WITH_HEURISTIC);
        if (!in_array($mode, [self::MODE_CONFIGURED, self::MODE_CONFIGURED_WITH_HEURISTIC], true)) {
            $mode = self::MODE_CONFIGURED_WITH_HEURISTIC;
        }

        $mappings = $this->normalizeMappings($configuration['mappings'] ?? []);

        Setting::query()->updateOrCreate(
            ['key' => self::SETTING_KEY_MODE, 'created_by' => $companyId],
            ['value' => $mode, 'is_public' => false]
        );

        Setting::query()->updateOrCreate(
            ['key' => self::SETTING_KEY_RULES, 'created_by' => $companyId],
            ['value' => json_encode($mappings, JSON_UNESCAPED_UNICODE), 'is_public' => false]
        );

        $saved = [
            'mode' => $mode,
            'mappings' => $mappings,
        ];

        $this->configurationCache[$companyId] = $saved;

        return $saved;
    }

    private function resolveFromConfiguredRules(Employee $employee, array $index, array $mappings): ?CostCenter
    {
        $employeeKey = (string) $employee->id;
        $departmentKey = (string) ($employee->department_id ?? '');
        $branchKey = (string) ($employee->branch_id ?? '');

        $sourceOrder = [
            ['type' => 'employee', 'key' => $employeeKey],
            ['type' => 'department', 'key' => $departmentKey],
            ['type' => 'branch', 'key' => $branchKey],
        ];

        foreach ($sourceOrder as $source) {
            if ($source['key'] === '') {
                continue;
            }

            $targetCostCenterId = (string) ($mappings[$source['type']][$source['key']] ?? '');
            if ($targetCostCenterId === '') {
                continue;
            }

            if (isset($index['byId'][$targetCostCenterId])) {
                return $index['byId'][$targetCostCenterId];
            }
        }

        return null;
    }

    private function resolveFromHeuristic(?string $departmentName, ?string $branchName, ?int $departmentId, ?int $branchId, array $index): ?CostCenter
    {
        if (empty($index['byCode']) && empty($index['byName'])) {
            return null;
        }

        $codeCandidates = array_filter([
            $departmentId ? 'DEP-' . $departmentId : null,
            $branchId ? 'BR-' . $branchId : null,
            $departmentName,
            $branchName,
        ]);

        foreach ($codeCandidates as $candidate) {
            $normalized = $this->normalizeCode($candidate);
            if ($normalized !== '' && isset($index['byCode'][$normalized])) {
                return $index['byCode'][$normalized];
            }
        }

        $nameCandidates = array_filter([$departmentName, $branchName]);

        foreach ($nameCandidates as $candidate) {
            $normalized = $this->normalizeName($candidate);
            if ($normalized !== '' && isset($index['byName'][$normalized])) {
                return $index['byName'][$normalized];
            }
        }

        return null;
    }

    private function normalizeMappings(array $rawMappings): array
    {
        $normalized = [
            'employee' => [],
            'department' => [],
            'branch' => [],
        ];

        foreach (['employee', 'department', 'branch'] as $type) {
            $items = $rawMappings[$type] ?? [];
            if (!is_array($items)) {
                continue;
            }

            foreach ($items as $sourceId => $costCenterId) {
                $source = (int) $sourceId;
                $target = (int) $costCenterId;
                if ($source <= 0 || $target <= 0) {
                    continue;
                }

                $normalized[$type][(string) $source] = $target;
            }
        }

        return $normalized;
    }

    private function indexForCompany(int $companyId): array
    {
        if (!isset($this->costCenterIndexByCompany[$companyId])) {
            $centers = CostCenter::query()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->get(['id', 'code', 'name', 'company_id']);

            $byCode = [];
            $byName = [];
            $byId = [];

            foreach ($centers as $center) {
                $normalizedCode = $this->normalizeCode($center->code);
                $normalizedName = $this->normalizeName($center->name);
                $byId[(string) $center->id] = $center;

                if ($normalizedCode !== '' && !isset($byCode[$normalizedCode])) {
                    $byCode[$normalizedCode] = $center;
                }

                if ($normalizedName !== '' && !isset($byName[$normalizedName])) {
                    $byName[$normalizedName] = $center;
                }
            }

            $this->costCenterIndexByCompany[$companyId] = [
                'byId' => $byId,
                'byCode' => $byCode,
                'byName' => $byName,
            ];
        }

        return $this->costCenterIndexByCompany[$companyId];
    }

    private function normalizeCode(?string $value): string
    {
        return strtoupper(trim((string) $value));
    }

    private function normalizeName(?string $value): string
    {
        return strtolower(trim((string) $value));
    }
}
