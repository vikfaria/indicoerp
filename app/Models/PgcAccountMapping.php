<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PgcAccountMapping extends Model
{
    protected $fillable = [
        'company_id',
        'legacy_account_code',
        'pgc_account_code',
        'status',
        'notes',
        'created_by',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_id');
    }
}
