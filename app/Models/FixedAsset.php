<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAsset extends Model
{
    protected $fillable = [
        'company_id', 'asset_code', 'name', 'description', 'category', 'sub_category',
        'acquisition_date', 'acquisition_cost', 'residual_value', 'useful_life_months',
        'depreciation_method', 'depreciation_rate', 'accumulated_depreciation',
        'net_book_value', 'impairment_losses', 'revaluation_surplus',
        'last_depreciation_date', 'status', 'disposal_date', 'disposal_proceeds',
        'location', 'responsible_person', 'pgc_asset_account', 'pgc_depreciation_account',
        'pgc_expense_account', 'serial_number', 'supplier', 'invoice_reference', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'acquisition_cost' => 'decimal:2',
            'residual_value' => 'decimal:2',
            'depreciation_rate' => 'decimal:2',
            'accumulated_depreciation' => 'decimal:2',
            'net_book_value' => 'decimal:2',
            'impairment_losses' => 'decimal:2',
            'revaluation_surplus' => 'decimal:2',
            'last_depreciation_date' => 'date',
            'disposal_date' => 'date',
            'disposal_proceeds' => 'decimal:2',
        ];
    }

    public function depreciationEntries(): HasMany
    {
        return $this->hasMany(DepreciationEntry::class);
    }

    /**
     * Calculate monthly depreciation (straight-line).
     */
    public function getMonthlyDepreciation(): float
    {
        if ($this->status !== 'active') return 0;
        $depreciableBase = $this->acquisition_cost - $this->residual_value;
        return round($depreciableBase / $this->useful_life_months, 2);
    }

    /**
     * Check if the asset is fully depreciated.
     */
    public function isFullyDepreciated(): bool
    {
        return $this->accumulated_depreciation >= ($this->acquisition_cost - $this->residual_value);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
