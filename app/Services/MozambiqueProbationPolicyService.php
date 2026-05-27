<?php

namespace App\Services;

use Carbon\Carbon;

class MozambiqueProbationPolicyService
{
    private const DEFAULT_CATEGORY_LIMITS = [
        'base_indefinite' => 30,
        'general' => 60,
        'technician_mid' => 90,
        'technician_high' => 180,
        'leadership' => 180,
    ];

    public function __construct(
        private readonly MozambiqueHrLegalSettingsService $legalSettingsService
    ) {}

    public function allCategoryLimits(?int $companyId = null): array
    {
        if (!$companyId) {
            return self::DEFAULT_CATEGORY_LIMITS;
        }

        $settings = $this->legalSettingsService->getSettings($companyId);
        $limits = $settings['probation_limits_days'] ?? [];

        return [
            'base_indefinite' => max(1, (int) ($limits['base_indefinite'] ?? self::DEFAULT_CATEGORY_LIMITS['base_indefinite'])),
            'general' => max(1, (int) ($limits['general'] ?? self::DEFAULT_CATEGORY_LIMITS['general'])),
            'technician_mid' => max(1, (int) ($limits['technician_mid'] ?? self::DEFAULT_CATEGORY_LIMITS['technician_mid'])),
            'technician_high' => max(1, (int) ($limits['technician_high'] ?? self::DEFAULT_CATEGORY_LIMITS['technician_high'])),
            'leadership' => max(1, (int) ($limits['leadership'] ?? self::DEFAULT_CATEGORY_LIMITS['leadership'])),
        ];
    }

    public function categories(?int $companyId = null): array
    {
        return array_keys($this->allCategoryLimits($companyId));
    }

    public function legalMaxDaysFor(string $category, ?int $companyId = null): int
    {
        $limits = $this->allCategoryLimits($companyId);

        return $limits[$category] ?? $limits['general'];
    }

    public function calculateExpectedEndDate(string $startsAt, string $category, ?int $companyId = null): string
    {
        return Carbon::parse($startsAt)
            ->startOfDay()
            ->addDays($this->legalMaxDaysFor($category, $companyId))
            ->toDateString();
    }

    public function buildAlerts(?string $expectedEndAt, ?int $companyId = null): array
    {
        if (empty($expectedEndAt)) {
            return [
                'days_remaining' => null,
                'is_overdue' => false,
                'alert_15_days' => false,
                'alert_7_days' => false,
                'alert_last_day' => false,
            ];
        }

        $today = Carbon::today();
        $endDate = Carbon::parse($expectedEndAt)->startOfDay();
        $daysRemaining = $today->diffInDays($endDate, false);
        $settings = $companyId
            ? $this->legalSettingsService->getSettings($companyId)
            : $this->legalSettingsService->defaultSettings();
        $primaryAlertDays = max(1, (int) ($settings['probation_alert_days']['primary'] ?? 15));
        $secondaryAlertDays = max(0, min($primaryAlertDays, (int) ($settings['probation_alert_days']['secondary'] ?? 7)));

        return [
            'days_remaining' => $daysRemaining,
            'is_overdue' => $daysRemaining < 0,
            'alert_15_days' => $daysRemaining >= 0 && $daysRemaining <= $primaryAlertDays,
            'alert_7_days' => $daysRemaining >= 0 && $daysRemaining <= $secondaryAlertDays,
            'alert_last_day' => $daysRemaining === 0,
        ];
    }
}
