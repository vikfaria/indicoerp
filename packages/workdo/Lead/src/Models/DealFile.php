<?php

namespace Workdo\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealFile extends Model
{
    protected $fillable = [
        'deal_id',
        'file_name',
        'file_path'
    ];

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }
}