<?php

namespace App\Services;

use App\Models\Setting;

class MozambiqueHrLegalSettingsService
{
    private const DEFAULTS = [
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
    ];

    private const SETTING_MAP = [
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
                ['value' => (string) $value, 'is_public' => false]
            );
        }

        return $settings;
    }

    public function defaultSettings(): array
    {
        return self::DEFAULTS;
    }

    private function normalize(array $settings): array
    {
        $foreignQuota = $settings['foreign_quota'] ?? [];
        $probationLimits = $settings['probation_limits_days'] ?? [];
        $probationAlerts = $settings['probation_alert_days'] ?? [];

        $microMaxWorkers = max(1, (int) ($foreignQuota['micro_max_workers'] ?? self::DEFAULTS['foreign_quota']['micro_max_workers']));
        $smallMaxWorkers = max($microMaxWorkers + 1, (int) ($foreignQuota['small_max_workers'] ?? self::DEFAULTS['foreign_quota']['small_max_workers']));
        $mediumMaxWorkers = max($smallMaxWorkers + 1, (int) ($foreignQuota['medium_max_workers'] ?? self::DEFAULTS['foreign_quota']['medium_max_workers']));

        $primaryAlert = max(1, (int) ($probationAlerts['primary'] ?? self::DEFAULTS['probation_alert_days']['primary']));
        $secondaryAlert = max(0, min($primaryAlert, (int) ($probationAlerts['secondary'] ?? self::DEFAULTS['probation_alert_days']['secondary'])));

        return [
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

