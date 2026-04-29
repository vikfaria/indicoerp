<?php

namespace App\Services;

use App\Models\MozInssRate;
use App\Models\MozIrpsTable;
use App\Models\MozMinimumWage;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class MozambiquePayrollTaxService
{
    public function calculateIrps(float $taxableIncome, ?int $companyId = null, mixed $effectiveDate = null): array
    {
        $income = max(0, round($taxableIncome, 2));
        $date = $this->resolveDate($effectiveDate);

        if ($income <= 0) {
            return [
                'taxable_income' => $income,
                'irps_amount' => 0.0,
                'rate_percent' => 0.0,
                'fixed_amount' => 0.0,
                'table_id' => null,
                'bracket_id' => null,
                'configured' => false,
            ];
        }

        $table = $this->resolveIrpsTable($companyId, $date);

        if ($table === null) {
            return [
                'taxable_income' => $income,
                'irps_amount' => 0.0,
                'rate_percent' => 0.0,
                'fixed_amount' => 0.0,
                'table_id' => null,
                'bracket_id' => null,
                'configured' => false,
            ];
        }

        $brackets = $table->brackets()->orderBy('sequence')->orderBy('range_from')->get();
        $bracket = $brackets->first(function ($row) use ($income) {
            $rangeFrom = (float) $row->range_from;
            $rangeTo = $row->range_to !== null ? (float) $row->range_to : null;

            if ($income < $rangeFrom) {
                return false;
            }

            return $rangeTo === null || $income <= $rangeTo;
        });

        if ($bracket === null) {
            $bracket = $brackets
                ->filter(fn($row) => $income >= (float) $row->range_from)
                ->sortByDesc('range_from')
                ->first();
        }

        if ($bracket === null) {
            return [
                'taxable_income' => $income,
                'irps_amount' => 0.0,
                'rate_percent' => 0.0,
                'fixed_amount' => 0.0,
                'table_id' => $table->id,
                'bracket_id' => null,
                'configured' => false,
            ];
        }

        $rangeFrom = (float) $bracket->range_from;
        $fixedAmount = (float) $bracket->fixed_amount;
        $ratePercent = (float) $bracket->rate_percent;
        $variableBase = max(0, $income - $rangeFrom);
        $irpsAmount = round($fixedAmount + (($variableBase * $ratePercent) / 100), 2);

        return [
            'taxable_income' => $income,
            'irps_amount' => $irpsAmount,
            'rate_percent' => $ratePercent,
            'fixed_amount' => $fixedAmount,
            'table_id' => $table->id,
            'bracket_id' => $bracket->id,
            'configured' => true,
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
}
