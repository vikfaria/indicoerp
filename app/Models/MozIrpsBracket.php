<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MozIrpsBracket extends Model
{
    protected $table = 'mz_irps_brackets';

    protected $fillable = [
        'irps_table_id',
        'range_from',
        'range_to',
        'fixed_amount',
        'rate_percent',
        'sequence',
    ];

    protected function casts(): array
    {
        return [
            'range_from' => 'decimal:2',
            'range_to' => 'decimal:2',
            'fixed_amount' => 'decimal:2',
            'rate_percent' => 'decimal:4',
            'sequence' => 'integer',
        ];
    }

    public function irpsTable(): BelongsTo
    {
        return $this->belongsTo(MozIrpsTable::class, 'irps_table_id');
    }
}
