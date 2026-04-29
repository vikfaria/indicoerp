<?php

namespace Workdo\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadFile extends Model
{
    protected $fillable = [
        'lead_id',
        'file_name',
        'file_path'
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}