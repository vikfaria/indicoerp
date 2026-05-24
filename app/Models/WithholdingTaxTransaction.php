<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithholdingTaxTransaction extends Model
{
    protected $fillable = [
        'company_id', 'withholding_rule_id', 'vendor_id',
        'vendor_nuit', 'vendor_name', 'transaction_date',
        'document_reference', 'gross_amount', 'withholding_rate',
        'withholding_amount', 'net_amount', 'fiscal_year',
        'fiscal_month', 'status', 'journal_entry_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'gross_amount' => 'decimal:2',
            'withholding_rate' => 'decimal:2',
            'withholding_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'fiscal_month' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(WithholdingTaxRule::class, 'withholding_rule_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForMonth($query, string $year, int $month)
    {
        return $query->where('fiscal_year', $year)->where('fiscal_month', $month);
    }
}
