<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IrpcConfiguration extends Model
{
    protected $fillable = [
        'company_id', 'fiscal_year', 'standard_rate', 'reduced_rate',
        'regime', 'payment_on_account_rate', 'is_first_year',
        'fiscal_incentives', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'standard_rate' => 'decimal:2',
            'reduced_rate' => 'decimal:2',
            'payment_on_account_rate' => 'decimal:2',
            'is_first_year' => 'boolean',
            'fiscal_incentives' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    /**
     * Get the applicable tax rate for this configuration.
     */
    public function getApplicableRate(): float
    {
        return $this->reduced_rate ?? $this->standard_rate;
    }
}
