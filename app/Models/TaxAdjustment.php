<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxAdjustment extends Model
{
    protected $fillable = [
        'company_id', 'fiscal_year', 'type', 'category',
        'description', 'amount', 'legal_basis', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    public function isAddBack(): bool
    {
        return $this->type === 'add_back';
    }

    public function isDeduction(): bool
    {
        return $this->type === 'deduction';
    }
}
