<?php

namespace App\Services;

use App\Models\MozInssRate;
use App\Models\MozIrpsTable;
use App\Models\MozMinimumWage;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class MozambiquePayrollTaxService
{
    private const DEFAULT_NON_RESIDENT_FLAT_RATE_PERCENT = 20.0;
    private const DEFAULT_MINIMUM_NON_TAXABLE_AMOUNT = 18750.0;
    private const DEFAULT_DEPENDENT_DEDUCTION_AMOUNT = 150.0;
    private const SETTING_IRPS_MINIMUM_NON_TAXABLE_AMOUNT = 'mz_irps_minimum_non_taxable_amount';
    private const SETTING_IRPS_DEPENDENT_DEDUCTION_AMOUNT = 'mz_irps_dependent_deduction_amount';
    private const SETTING_IRPS_MAX_DEPENDENTS_FOR_DEDUCTION = 'mz_irps_max_dependents_for_deduction';
    private const SETTING_IRPS_NON_RESIDENT_FLAT_RATE_PERCENT = 'mz_irps_non_resident_flat_rate_percent';

    public function calculateIrps(
        float $taxableIncome,
        ?int $companyId = null,
        mixed $effectiveDate = null,
        array $context = []
    ): array
    {
        $income = max(0, round($taxableIncome, 2));
        $date = $this->resolveDate($effectiveDate);
        $irpsContext = $this->resolveIrpsContext($companyId, $context);

        $minimumNonTaxableAmount = $irpsContext['minimum_non_taxable_amount'];
        $adjustedIncome = max(0, round($income - $minimumNonTaxableAmount, 2));

        $effectiveDependents = $irpsContext['eligible_dependents_count'];
        if ($irpsContext['max_dependents_for_deduction'] !== null) {
            $effectiveDependents = min($effectiveDependents, $irpsContext['max_dependents_for_deduction']);
        }

        $dependentDeductionTotal = round(
            $effectiveDependents * $irpsContext['dependent_deduction_amount'],
            2
        );
        if ($income <= 0) {
            return [
                'taxable_income' => $income,
                'adjusted_taxable_income' => $adjustedIncome,
                'minimum_non_taxable_amount' => $minimumNonTaxableAmount,
                'tax_before_dependent_deductions' => 0.0,
                'irps_amount' => 0.0,
                'rate_percent' => 0.0,
                'fixed_amount' => 0.0,
                'table_id' => null,
                'bracket_id' => null,
                'configured' => false,
                'residency_status' => $irpsContext['residency_status'],
                'eligible_dependents_count' => $irpsContext['eligible_dependents_count'],
                'effective_dependents_count' => $effectiveDependents,
                'dependent_deduction_amount' => $irpsContext['dependent_deduction_amount'],
                'dependent_deduction_total' => 0.0,
                'rule' => 'none',
            ];
        }

        if (
            $irpsContext['residency_status'] === 'non_resident'
            && $irpsContext['apply_non_resident_flat_rate']
            && $irpsContext['non_resident_flat_rate_percent'] > 0
        ) {
            $taxBeforeDependentDeductions = round(($income * $irpsContext['non_resident_flat_rate_percent']) / 100, 2);

            return [
                'taxable_income' => $income,
                'adjusted_taxable_income' => $income,
                'minimum_non_taxable_amount' => $minimumNonTaxableAmount,
                'tax_before_dependent_deductions' => $taxBeforeDependentDeductions,
                'irps_amount' => $taxBeforeDependentDeductions,
                'rate_percent' => $irpsContext['non_resident_flat_rate_percent'],
                'fixed_amount' => 0.0,
                'table_id' => null,
                'bracket_id' => null,
                'configured' => true,
                'residency_status' => $irpsContext['residency_status'],
                'eligible_dependents_count' => $irpsContext['eligible_dependents_count'],
                'effective_dependents_count' => $effectiveDependents,
                'dependent_deduction_amount' => $irpsContext['dependent_deduction_amount'],
                'dependent_deduction_total' => 0.0,
                'rule' => 'non_resident_flat',
            ];
        }

        $table = $this->resolveIrpsTable($companyId, $date);

        if ($table === null) {
            return [
                'taxable_income' => $income,
                'adjusted_taxable_income' => $adjustedIncome,
                'minimum_non_taxable_amount' => $minimumNonTaxableAmount,
                'tax_before_dependent_deductions' => 0.0,
                'irps_amount' => 0.0,
                'rate_percent' => 0.0,
                'fixed_amount' => 0.0,
                'table_id' => null,
                'bracket_id' => null,
                'configured' => false,
                'residency_status' => $irpsContext['residency_status'],
                'eligible_dependents_count' => $irpsContext['eligible_dependents_count'],
                'effective_dependents_count' => $effectiveDependents,
                'dependent_deduction_amount' => $irpsContext['dependent_deduction_amount'],
                'dependent_deduction_total' => $dependentDeductionTotal,
                'rule' => 'table_missing',
            ];
        }

        $brackets = $table->brackets()->orderBy('sequence')->orderBy('range_from')->get();
        $bracket = $brackets->first(function ($row) use ($adjustedIncome) {
            $rangeFrom = (float) $row->range_from;
            $rangeTo = $row->range_to !== null ? (float) $row->range_to : null;

            if ($adjustedIncome < $rangeFrom) {
                return false;
            }

            return $rangeTo === null || $adjustedIncome <= $rangeTo;
        });

        if ($bracket === null) {
            $bracket = $brackets
                ->filter(fn($row) => $adjustedIncome >= (float) $row->range_from)
                ->sortByDesc('range_from')
                ->first();
        }

        if ($bracket === null) {
            return [
                'taxable_income' => $income,
                'adjusted_taxable_income' => $adjustedIncome,
                'minimum_non_taxable_amount' => $minimumNonTaxableAmount,
                'tax_before_dependent_deductions' => 0.0,
                'irps_amount' => 0.0,
                'rate_percent' => 0.0,
                'fixed_amount' => 0.0,
                'table_id' => $table->id,
                'bracket_id' => null,
                'configured' => false,
                'residency_status' => $irpsContext['residency_status'],
                'eligible_dependents_count' => $irpsContext['eligible_dependents_count'],
                'effective_dependents_count' => $effectiveDependents,
                'dependent_deduction_amount' => $irpsContext['dependent_deduction_amount'],
                'dependent_deduction_total' => $dependentDeductionTotal,
                'rule' => 'no_matching_bracket',
            ];
        }

        $rangeFrom = (float) $bracket->range_from;
        $fixedAmount = (float) $bracket->fixed_amount;
        $ratePercent = (float) $bracket->rate_percent;
        $variableBase = max(0, $adjustedIncome - $rangeFrom);
        $taxBeforeDependentDeductions = round($fixedAmount + (($variableBase * $ratePercent) / 100), 2);
        $irpsAmount = round(max(0, $taxBeforeDependentDeductions - $dependentDeductionTotal), 2);

        return [
            'taxable_income' => $income,
            'adjusted_taxable_income' => $adjustedIncome,
            'minimum_non_taxable_amount' => $minimumNonTaxableAmount,
            'tax_before_dependent_deductions' => $taxBeforeDependentDeductions,
            'irps_amount' => $irpsAmount,
            'rate_percent' => $ratePercent,
            'fixed_amount' => $fixedAmount,
            'table_id' => $table->id,
            'bracket_id' => $bracket->id,
            'configured' => true,
            'residency_status' => $irpsContext['residency_status'],
            'eligible_dependents_count' => $irpsContext['eligible_dependents_count'],
            'effective_dependents_count' => $effectiveDependents,
            'dependent_deduction_amount' => $irpsContext['dependent_deduction_amount'],
            'dependent_deduction_total' => $dependentDeductionTotal,
            'rule' => 'table_bracket',
        ];
    }

    public function calculateInss(float $baseAmount, ?int $companyId = null, mixed $effectiveDate = null): array
    {
        $base = max(0, round($baseAmount, 2));
        $date = $this->resolveDate($effectiveDate);
        $rate = $this->resolveInssRate($companyId, $date);

        $employeeRate = $rate ? (float) $rate->employee_rate : 3.0;
        $employerRate = $rate ? (float) $rate->employer_rate : 4.0;

        $employeeAmount = round(($base * $employeeRate) / 100, 2);
        $employerAmount = round(($base * $employerRate) / 100, 2);

        return [
            'base_amount' => $base,
            'employee_rate' => $employeeRate,
            'employee_amount' => $employeeAmount,
            'employer_rate' => $employerRate,
            'employer_amount' => $employerAmount,
            'configured' => $rate !== null,
            'rate_id' => $rate?->id,
        ];
    }

    public function validateMinimumWage(
        ?string $sectorCode,
        float $basicSalary,
        ?int $companyId = null,
        mixed $effectiveDate = null
    ): array {
        $salary = max(0, round($basicSalary, 2));
        $date = $this->resolveDate($effectiveDate);
        $code = $sectorCode ? strtoupper(trim($sectorCode)) : null;

        $wage = $this->resolveMinimumWage($code, $companyId, $date);
        $required = $wage ? (float) $wage->monthly_amount : null;

        if ($required === null) {
            return [
                'configured' => false,
                'sector_code' => $code,
                'minimum_required' => null,
                'provided_salary' => $salary,
                'is_compliant' => true,
                'gap' => 0.0,
                'wage_id' => null,
            ];
        }

        $isCompliant = $salary >= $required;
        $gap = round(max(0, $required - $salary), 2);

        return [
            'configured' => true,
            'sector_code' => strtoupper((string) $wage->sector_code),
            'minimum_required' => $required,
            'provided_salary' => $salary,
            'is_compliant' => $isCompliant,
            'gap' => $gap,
            'wage_id' => $wage->id,
        ];
    }

    private function resolveIrpsTable(?int $companyId, CarbonInterface $date): ?MozIrpsTable
    {
        return MozIrpsTable::query()
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $date->toDateString())
            ->where(function ($query) use ($date): void {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date->toDateString());
            })
            ->where(function ($query) use ($companyId): void {
                if ($companyId !== null) {
                    $query->where('created_by', $companyId)->orWhereNull('created_by');
                } else {
                    $query->whereNull('created_by');
                }
            })
            ->orderByRaw('CASE WHEN created_by IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('effective_from')
            ->first();
    }

    private function resolveInssRate(?int $companyId, CarbonInterface $date): ?MozInssRate
    {
        return MozInssRate::query()
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $date->toDateString())
            ->where(function ($query) use ($date): void {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date->toDateString());
            })
            ->where(function ($query) use ($companyId): void {
                if ($companyId !== null) {
                    $query->where('created_by', $companyId)->orWhereNull('created_by');
                } else {
                    $query->whereNull('created_by');
                }
            })
            ->orderByRaw('CASE WHEN created_by IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('effective_from')
            ->first();
    }

    private function resolveMinimumWage(?string $sectorCode, ?int $companyId, CarbonInterface $date): ?MozMinimumWage
    {
        $wages = MozMinimumWage::query()
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $date->toDateString())
            ->where(function ($query) use ($date): void {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date->toDateString());
            })
            ->where(function ($query) use ($companyId): void {
                if ($companyId !== null) {
                    $query->where('created_by', $companyId)->orWhereNull('created_by');
                } else {
                    $query->whereNull('created_by');
                }
            })
            ->orderByRaw('CASE WHEN created_by IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('effective_from')
            ->get();

        $candidateCodes = array_filter([
            $sectorCode ? strtoupper($sectorCode) : null,
            'GENERAL',
        ]);

        foreach ($candidateCodes as $candidateCode) {
            $match = $wages->first(fn($row) => strtoupper((string) $row->sector_code) === $candidateCode);
            if ($match !== null) {
                return $match;
            }
        }

        return null;
    }

    private function resolveDate(mixed $value): CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            return Carbon::parse($value);
        }

        return now();
    }

    private function resolveIrpsContext(?int $companyId, array $context): array
    {
        $settings = $companyId !== null ? getCompanyAllSetting($companyId) : [];

        $residencyStatus = strtolower((string) ($context['residency_status'] ?? 'resident'));
        if (!in_array($residencyStatus, ['resident', 'non_resident'], true)) {
            $residencyStatus = 'resident';
        }

        $eligibleDependentsCount = (int) ($context['eligible_dependents_count'] ?? $context['dependents_count'] ?? 0);
        $eligibleDependentsCount = max(0, $eligibleDependentsCount);

        $minimumNonTaxableAmount = (float) (
            $context['minimum_non_taxable_amount']
            ?? $settings[self::SETTING_IRPS_MINIMUM_NON_TAXABLE_AMOUNT]
            ?? self::DEFAULT_MINIMUM_NON_TAXABLE_AMOUNT
        );
        $minimumNonTaxableAmount = round(max(0, $minimumNonTaxableAmount), 2);

        $dependentDeductionAmount = (float) (
            $context['dependent_deduction_amount']
            ?? $settings[self::SETTING_IRPS_DEPENDENT_DEDUCTION_AMOUNT]
            ?? self::DEFAULT_DEPENDENT_DEDUCTION_AMOUNT
        );
        $dependentDeductionAmount = round(max(0, $dependentDeductionAmount), 2);

        $maxDependentsRaw = $context['max_dependents_for_deduction']
            ?? $settings[self::SETTING_IRPS_MAX_DEPENDENTS_FOR_DEDUCTION]
            ?? null;
        $maxDependents = null;
        if ($maxDependentsRaw !== null && $maxDependentsRaw !== '') {
            $parsed = (int) $maxDependentsRaw;
            if ($parsed > 0) {
                $maxDependents = $parsed;
            }
        }

        $nonResidentRate = (float) (
            $context['non_resident_flat_rate_percent']
            ?? $settings[self::SETTING_IRPS_NON_RESIDENT_FLAT_RATE_PERCENT]
            ?? self::DEFAULT_NON_RESIDENT_FLAT_RATE_PERCENT
        );
        $nonResidentRate = max(0, min(100, round($nonResidentRate, 4)));

        $applyNonResidentFlatRate = array_key_exists('apply_non_resident_flat_rate', $context)
            ? (bool) $context['apply_non_resident_flat_rate']
            : true;

        return [
            'residency_status' => $residencyStatus,
            'eligible_dependents_count' => $eligibleDependentsCount,
            'minimum_non_taxable_amount' => $minimumNonTaxableAmount,
            'dependent_deduction_amount' => $dependentDeductionAmount,
            'max_dependents_for_deduction' => $maxDependents,
            'non_resident_flat_rate_percent' => $nonResidentRate,
            'apply_non_resident_flat_rate' => $applyNonResidentFlatRate,
        ];
    }
}
