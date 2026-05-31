<?php

namespace App\Services;

use Carbon\Carbon;
use Workdo\Hrm\Models\Employee;

class MozambiqueTerminationSettlementService
{
    private const DEFAULT_NOTICE_DAYS = [
        'up_to_six_months' => 0,
        'up_to_three_years' => 15,
        'above_three_years' => 30,
    ];

    private const DEFAULT_INDEMNITY_DAYS_PER_YEAR = 45.0;

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function build(
        ?Employee $employee,
        string $noticeDate,
        string $effectiveDate,
        array $input = [],
        bool $defaultApplyIndemnity = true,
        ?int $companyId = null
    ): array {
        $effectiveAt = Carbon::parse($effectiveDate)->startOfDay();
        $noticeAt = Carbon::parse($noticeDate)->startOfDay();
        $serviceStartAt = $this->resolveServiceStartDate($employee, $noticeAt);

        $serviceDays = max(0, $serviceStartAt->diffInDays($effectiveAt, false));
        $providedNoticeDays = max(0, $noticeAt->diffInDays($effectiveAt, false));
        $requiredNoticeDays = $this->requiredNoticeDays($serviceDays, $companyId);
        $missingNoticeDays = max(0, $requiredNoticeDays - $providedNoticeDays);

        $baseSalary = round((float) ($employee?->basic_salary ?? 0), 2);
        $dailySalary = round($baseSalary / 30, 2);
        $workedDaysInMonth = (int) $effectiveAt->day;
        $salaryUntilExit = round($dailySalary * $workedDaysInMonth, 2);

        $unusedLeaveDays = round(max(0, (float) ($input['settlement_unused_leave_days'] ?? 0)), 2);
        $unusedLeaveAmount = round($dailySalary * $unusedLeaveDays, 2);

        $otherEarningsAmount = round(max(0, (float) ($input['settlement_other_earnings_amount'] ?? 0)), 2);
        $otherDeductionsAmount = round(max(0, (float) ($input['settlement_other_deductions_amount'] ?? 0)), 2);

        $applyIndemnity = $this->toBool($input['settlement_apply_indemnity'] ?? null, $defaultApplyIndemnity);
        $indemnityDaysPerYear = round(max(0, (float) ($input['settlement_indemnity_days_per_year'] ?? $this->indemnityDaysPerYear($companyId))), 2);
        $indemnityYears = round($serviceDays / 365, 4);
        $indemnityAmount = $applyIndemnity ? round($dailySalary * $indemnityDaysPerYear * $indemnityYears, 2) : 0.0;

        $grossAmount = round($salaryUntilExit + $unusedLeaveAmount + $otherEarningsAmount + $indemnityAmount, 2);
        $totalDeductionsAmount = $otherDeductionsAmount;
        $netAmount = round($grossAmount - $totalDeductionsAmount, 2);

        return [
            'legal_notice_required_days' => $requiredNoticeDays,
            'legal_notice_provided_days' => $providedNoticeDays,
            'legal_notice_missing_days' => $missingNoticeDays,
            'legal_notice_compliant' => $missingNoticeDays === 0,
            'settlement_base_salary_amount' => $baseSalary,
            'settlement_daily_salary_amount' => $dailySalary,
            'settlement_salary_until_exit_amount' => $salaryUntilExit,
            'settlement_unused_leave_days' => $unusedLeaveDays,
            'settlement_unused_leave_amount' => $unusedLeaveAmount,
            'settlement_other_earnings_amount' => $otherEarningsAmount,
            'settlement_other_deductions_amount' => $otherDeductionsAmount,
            'settlement_apply_indemnity' => $applyIndemnity,
            'settlement_indemnity_days_per_year' => $indemnityDaysPerYear,
            'settlement_indemnity_years' => $indemnityYears,
            'settlement_indemnity_amount' => $indemnityAmount,
            'settlement_gross_amount' => $grossAmount,
            'settlement_total_deductions_amount' => $totalDeductionsAmount,
            'settlement_net_amount' => $netAmount,
            'settlement_generated_at' => now(),
        ];
    }

    private function resolveServiceStartDate(?Employee $employee, Carbon $fallback): Carbon
    {
        if ($employee?->date_of_joining) {
            return Carbon::parse($employee->date_of_joining)->startOfDay();
        }

        if ($employee?->user?->created_at) {
            return Carbon::parse($employee->user->created_at)->startOfDay();
        }

        return $fallback->copy();
    }

    private function requiredNoticeDays(int $serviceDays, ?int $companyId): int
    {
        if ($serviceDays <= 183) {
            return $this->intSetting('mz_notice_days_up_to_six_months', $companyId, self::DEFAULT_NOTICE_DAYS['up_to_six_months']);
        }

        if ($serviceDays <= 1095) {
            return $this->intSetting('mz_notice_days_up_to_three_years', $companyId, self::DEFAULT_NOTICE_DAYS['up_to_three_years']);
        }

        return $this->intSetting('mz_notice_days_above_three_years', $companyId, self::DEFAULT_NOTICE_DAYS['above_three_years']);
    }

    private function indemnityDaysPerYear(?int $companyId): float
    {
        $value = company_setting('mz_indemnity_days_per_year', $companyId ?? creatorId());

        if ($value === null || $value === '') {
            return self::DEFAULT_INDEMNITY_DAYS_PER_YEAR;
        }

        return max(0.0, (float) $value);
    }

    private function intSetting(string $key, ?int $companyId, int $default): int
    {
        $value = company_setting($key, $companyId ?? creatorId());

        if ($value === null || $value === '') {
            return $default;
        }

        return max(0, (int) $value);
    }

    private function toBool(mixed $value, bool $default): bool
    {
        if ($value === null) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }

        return (bool) $value;
    }
}
