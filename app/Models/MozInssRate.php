<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MozInssRate extends Model
{
    protected $table = 'mz_inss_rates';

    protected $fillable = [
        'employee_rate',
        'employer_rate',
        'effective_from',
        'effective_to',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'employee_rate' => 'decimal:4',
            'employer_rate' => 'decimal:4',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
