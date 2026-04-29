<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MozMinimumWage extends Model
{
    protected $table = 'mz_minimum_wages';

    protected $fillable = [
        'sector_code',
        'sector_name',
        'monthly_amount',
        'effective_from',
        'effective_to',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'monthly_amount' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
