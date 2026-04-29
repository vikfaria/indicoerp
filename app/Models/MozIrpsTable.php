<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MozIrpsTable extends Model
{
    protected $table = 'mz_irps_tables';

    protected $fillable = [
        'name',
        'effective_from',
        'effective_to',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function brackets(): HasMany
    {
        return $this->hasMany(MozIrpsBracket::class, 'irps_table_id');
    }
}
