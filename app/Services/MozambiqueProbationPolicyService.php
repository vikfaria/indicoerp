<?php

namespace App\Services;

use Carbon\Carbon;

class MozambiqueProbationPolicyService
{
    private const CATEGORY_LIMITS = [
        'base_indefinite' => 30,
        'general' => 60,
        'technician_mid' => 90,
        'technician_high' => 180,
        'leadership' => 180,
    ];

    public function allCategoryLimits(): array
    {
        return self::CATEGORY_LIMITS;
    }

    public function categories(): array
    {
        return array_keys(self::CATEGORY_LIMITS);
    }

    public function legalMaxDaysFor(string $category): int
    {
        return self::CATEGORY_LIMITS[$category] ?? self::CATEGORY_LIMITS['general'];
    }

    public function calculateExpectedEndDate(string $startsAt, string $category): string
    {
        return Carbon::parse($startsAt)
            ->startOfDay()
            ->addDays($this->legalMaxDaysFor($category))
            ->toDateString();
    }

    public function buildAlerts(?string $expectedEndAt): array
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

        return [
            'days_remaining' => $daysRemaining,
            'is_overdue' => $daysRemaining < 0,
            'alert_15_days' => $daysRemaining >= 0 && $daysRemaining <= 15,
            'alert_7_days' => $daysRemaining >= 0 && $daysRemaining <= 7,
            'alert_last_day' => $daysRemaining === 0,
        ];
    }
}
