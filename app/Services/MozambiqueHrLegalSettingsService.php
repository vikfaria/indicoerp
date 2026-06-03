<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class MozambiqueHrLegalSettingsService
{
    private const DEFAULTS = [
        'company_profile' => [
            'sector_activity' => '',
            'operation_province' => '',
            'labour_regime' => '',
            'collective_agreements' => '',
            'labour_directorate' => '',
        ],
        'foreign_quota' => [
            'micro_max_workers' => 10,
            'small_max_workers' => 30,
            'medium_max_workers' => 100,
            'micro_quota_percent' => 15.0,
            'small_quota_percent' => 10.0,
            'medium_quota_percent' => 8.0,
            'large_quota_percent' => 5.0,
        ],
        'probation_limits_days' => [
            'base_indefinite' => 30,
            'general' => 60,
            'technician_mid' => 90,
            'technician_high' => 180,
            'leadership' => 180,
        ],
        'probation_alert_days' => [
            'primary' => 15,
            'secondary' => 7,
        ],
        'policy_requirements' => [
            'require_internal_regulation' => true,
            'require_code_of_conduct' => true,
            'require_anti_harassment_policy' => true,
            'require_disciplinary_policy' => true,
            'require_vacation_policy' => true,
            'require_data_protection_policy' => true,
            'require_equipment_use_policy' => true,
            'require_remote_work_policy' => false,
            'code_of_conduct_min_workers' => 7,
        ],
    ];

    private const SETTING_MAP = [
        'company_profile.sector_activity' => 'mz_company_sector_activity',
        'company_profile.operation_province' => 'mz_company_operation_province',
        'company_profile.labour_regime' => 'mz_company_labour_regime',
        'company_profile.collective_agreements' => 'mz_company_collective_agreements',
        'company_profile.labour_directorate' => 'mz_company_labour_directorate',
        'foreign_quota.micro_max_workers' => 'mz_foreign_quota_micro_max_workers',
        'foreign_quota.small_max_workers' => 'mz_foreign_quota_small_max_workers',
        'foreign_quota.medium_max_workers' => 'mz_foreign_quota_medium_max_workers',
        'foreign_quota.micro_quota_percent' => 'mz_foreign_quota_micro_percent',
        'foreign_quota.small_quota_percent' => 'mz_foreign_quota_small_percent',
        'foreign_quota.medium_quota_percent' => 'mz_foreign_quota_medium_percent',
        'foreign_quota.large_quota_percent' => 'mz_foreign_quota_large_percent',
        'probation_limits_days.base_indefinite' => 'mz_probation_limit_base_indefinite_days',
        'probation_limits_days.general' => 'mz_probation_limit_general_days',
        'probation_limits_days.technician_mid' => 'mz_probation_limit_technician_mid_days',
        'probation_limits_days.technician_high' => 'mz_probation_limit_technician_high_days',
        'probation_limits_days.leadership' => 'mz_probation_limit_leadership_days',
        'probation_alert_days.primary' => 'mz_probation_alert_primary_days',
        'probation_alert_days.secondary' => 'mz_probation_alert_secondary_days',
        'policy_requirements.require_internal_regulation' => 'mz_policy_require_internal_regulation',
        'policy_requirements.require_code_of_conduct' => 'mz_policy_require_code_of_conduct',
        'policy_requirements.require_anti_harassment_policy' => 'mz_policy_require_anti_harassment',
        'policy_requirements.require_disciplinary_policy' => 'mz_policy_require_disciplinary',
        'policy_requirements.require_vacation_policy' => 'mz_policy_require_vacation',
        'policy_requirements.require_data_protection_policy' => 'mz_policy_require_data_protection',
        'policy_requirements.require_equipment_use_policy' => 'mz_policy_require_equipment_use',
        'policy_requirements.require_remote_work_policy' => 'mz_policy_require_remote_work',
        'policy_requirements.code_of_conduct_min_workers' => 'mz_code_of_conduct_min_workers',
    ];

    public function getSettings(int $companyId): array
    {
        $settings = self::DEFAULTS;

        foreach (self::SETTING_MAP as $path => $key) {
            $rawValue = company_setting($key, $companyId);
            if ($rawValue === null || $rawValue === '') {
                continue;
            }

            $this->assignPathValue($settings, $path, $rawValue);
        }

        return $this->normalize($settings);
    }

    public function updateSettings(int $companyId, array $payload): array
    {
        $settings = $this->normalize($payload);

        foreach (self::SETTING_MAP as $path => $key) {
            $value = $this->readPathValue($settings, $path);
            if ($value === null) {
                continue;
            }

            Setting::query()->updateOrCreate(
                ['key' => $key, 'created_by' => $companyId],
                ['value' => $this->serializeSettingValue($value), 'is_public' => false]
            );
        }

        Cache::forget('company_settings_' . $companyId);
        Cache::forget('company_settings_' . $companyId . '_public');

        return $settings;
    }

    public function defaultSettings(): array
    {
        return self::DEFAULTS;
    }

    private function normalize(array $settings): array
    {
        $companyProfile = $settings['company_profile'] ?? [];
        $foreignQuota = $settings['foreign_quota'] ?? [];
        $probationLimits = $settings['probation_limits_days'] ?? [];
        $probationAlerts = $settings['probation_alert_days'] ?? [];
        $policyRequirements = $settings['policy_requirements'] ?? [];

        $microMaxWorkers = max(1, (int) ($foreignQuota['micro_max_workers'] ?? self::DEFAULTS['foreign_quota']['micro_max_workers']));
        $smallMaxWorkers = max($microMaxWorkers + 1, (int) ($foreignQuota['small_max_workers'] ?? self::DEFAULTS['foreign_quota']['small_max_workers']));
        $mediumMaxWorkers = max($smallMaxWorkers + 1, (int) ($foreignQuota['medium_max_workers'] ?? self::DEFAULTS['foreign_quota']['medium_max_workers']));

        $primaryAlert = max(1, (int) ($probationAlerts['primary'] ?? self::DEFAULTS['probation_alert_days']['primary']));
        $secondaryAlert = max(0, min($primaryAlert, (int) ($probationAlerts['secondary'] ?? self::DEFAULTS['probation_alert_days']['secondary'])));
        $codeOfConductThreshold = max(
            1,
            (int) ($policyRequirements['code_of_conduct_min_workers'] ?? self::DEFAULTS['policy_requirements']['code_of_conduct_min_workers'])
        );

        return [
            'company_profile' => [
                'sector_activity' => $this->normalizeString($companyProfile['sector_activity'] ?? self::DEFAULTS['company_profile']['sector_activity'], 191),
                'operation_province' => $this->normalizeString($companyProfile['operation_province'] ?? self::DEFAULTS['company_profile']['operation_province'], 191),
                'labour_regime' => $this->normalizeString($companyProfile['labour_regime'] ?? self::DEFAULTS['company_profile']['labour_regime'], 191),
                'collective_agreements' => $this->normalizeString($companyProfile['collective_agreements'] ?? self::DEFAULTS['company_profile']['collective_agreements'], 255),
                'labour_directorate' => $this->normalizeString($companyProfile['labour_directorate'] ?? self::DEFAULTS['company_profile']['labour_directorate'], 191),
            ],
            'foreign_quota' => [
                'micro_max_workers' => $microMaxWorkers,
                'small_max_workers' => $smallMaxWorkers,
                'medium_max_workers' => $mediumMaxWorkers,
                'micro_quota_percent' => $this->normalizePercent($foreignQuota['micro_quota_percent'] ?? self::DEFAULTS['foreign_quota']['micro_quota_percent']),
                'small_quota_percent' => $this->normalizePercent($foreignQuota['small_quota_percent'] ?? self::DEFAULTS['foreign_quota']['small_quota_percent']),
                'medium_quota_percent' => $this->normalizePercent($foreignQuota['medium_quota_percent'] ?? self::DEFAULTS['foreign_quota']['medium_quota_percent']),
                'large_quota_percent' => $this->normalizePercent($foreignQuota['large_quota_percent'] ?? self::DEFAULTS['foreign_quota']['large_quota_percent']),
            ],
            'probation_limits_days' => [
                'base_indefinite' => $this->normalizeDays($probationLimits['base_indefinite'] ?? self::DEFAULTS['probation_limits_days']['base_indefinite']),
                'general' => $this->normalizeDays($probationLimits['general'] ?? self::DEFAULTS['probation_limits_days']['general']),
                'technician_mid' => $this->normalizeDays($probationLimits['technician_mid'] ?? self::DEFAULTS['probation_limits_days']['technician_mid']),
                'technician_high' => $this->normalizeDays($probationLimits['technician_high'] ?? self::DEFAULTS['probation_limits_days']['technician_high']),
                'leadership' => $this->normalizeDays($probationLimits['leadership'] ?? self::DEFAULTS['probation_limits_days']['leadership']),
            ],
            'probation_alert_days' => [
                'primary' => $primaryAlert,
                'secondary' => $secondaryAlert,
            ],
            'policy_requirements' => [
                'require_internal_regulation' => $this->normalizeBool(
                    $policyRequirements['require_internal_regulation'] ?? self::DEFAULTS['policy_requirements']['require_internal_regulation']
                ),
                'require_code_of_conduct' => $this->normalizeBool(
                    $policyRequirements['require_code_of_conduct'] ?? self::DEFAULTS['policy_requirements']['require_code_of_conduct']
                ),
                'require_anti_harassment_policy' => $this->normalizeBool(
                    $policyRequirements['require_anti_harassment_policy'] ?? self::DEFAULTS['policy_requirements']['require_anti_harassment_policy']
                ),
                'require_disciplinary_policy' => $this->normalizeBool(
                    $policyRequirements['require_disciplinary_policy'] ?? self::DEFAULTS['policy_requirements']['require_disciplinary_policy']
                ),
                'require_vacation_policy' => $this->normalizeBool(
                    $policyRequirements['require_vacation_policy'] ?? self::DEFAULTS['policy_requirements']['require_vacation_policy']
                ),
                'require_data_protection_policy' => $this->normalizeBool(
                    $policyRequirements['require_data_protection_policy'] ?? self::DEFAULTS['policy_requirements']['require_data_protection_policy']
                ),
                'require_equipment_use_policy' => $this->normalizeBool(
                    $policyRequirements['require_equipment_use_policy'] ?? self::DEFAULTS['policy_requirements']['require_equipment_use_policy']
                ),
                'require_remote_work_policy' => $this->normalizeBool(
                    $policyRequirements['require_remote_work_policy'] ?? self::DEFAULTS['policy_requirements']['require_remote_work_policy']
                ),
                'code_of_conduct_min_workers' => $codeOfConductThreshold,
            ],
        ];
    }

    private function normalizePercent(mixed $value): float
    {
        return round(max(0.0, min(100.0, (float) $value)), 2);
    }

    private function normalizeDays(mixed $value): int
    {
        return max(1, min(365, (int) $value));
    }

    private function normalizeString(mixed $value, int $maxLength = 191): string
    {
        $normalized = trim((string) ($value ?? ''));

        if ($normalized === '') {
            return '';
        }

        return mb_substr($normalized, 0, $maxLength);
    }

    private function normalizeBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            return in_array($normalized, ['1', 'true', 'on', 'yes'], true);
        }

        return false;
    }

    private function serializeSettingValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    private function assignPathValue(array &$target, string $path, mixed $value): void
    {
        $segments = explode('.', $path);
        $cursor = &$target;

        foreach ($segments as $index => $segment) {
            if ($index === count($segments) - 1) {
                $cursor[$segment] = $value;
                return;
            }

            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }

            $cursor = &$cursor[$segment];
        }
    }

    private function readPathValue(array $target, string $path): mixed
    {
        $segments = explode('.', $path);
        $cursor = $target;

        foreach ($segments as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return null;
            }

            $cursor = $cursor[$segment];
        }

        return $cursor;
    }
}
