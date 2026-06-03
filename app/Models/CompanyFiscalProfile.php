<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyFiscalProfile extends Model
{
    protected $fillable = [
        'company_id',
        'nuit',
        'legal_name',
        'fiscal_regime',
        'entity_classification',
        'accounting_framework',
        'fiscal_year_start_month',
        'economic_activity_code',
        'economic_activity_description',
        'business_license_number',
        'license_expiry_date',
        'entity_type',
        'taxpayer_type',
        'state_of_certification',
        'software_certificate_number',
        'structured_bank_details',
        'tax_office',
        'province',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year_start_month' => 'integer',
            'license_expiry_date' => 'date',
            'structured_bank_details' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    /**
     * Determine the suggested accounting framework based on entity classification.
     */
    public function suggestedFramework(): string
    {
        return match ($this->entity_classification) {
            'large', 'medium' => 'pgc_nirf',
            'small', 'micro' => 'pgc_pe',
            'ispc' => 'ispc',
            default => 'pgc_nirf',
        };
    }

    /**
     * Check if the license is valid.
     */
    public function isLicenseValid(): bool
    {
        if ($this->license_expiry_date === null) {
            return true;
        }

        return $this->license_expiry_date->isFuture();
    }

    /**
     * Get fiscal year boundaries for a given calendar year.
     */
    public function getFiscalYearDates(int $year): array
    {
        $startMonth = $this->fiscal_year_start_month;

        if ($startMonth === 1) {
            return [
                'start' => "{$year}-01-01",
                'end' => "{$year}-12-31",
            ];
        }

        $startYear = $year;
        $endYear = $year + 1;
        $endMonth = $startMonth - 1;

        return [
            'start' => sprintf('%d-%02d-01', $startYear, $startMonth),
            'end' => date('Y-m-t', strtotime(sprintf('%d-%02d-01', $endYear, $endMonth))),
        ];
    }
}
