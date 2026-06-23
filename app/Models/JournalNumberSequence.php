<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalNumberSequence extends Model
{
    protected $fillable = [
        'created_by',
        'fiscal_year',
        'scope_key',
        'prefix',
        'last_sequence',
    ];

    protected $casts = [
        'last_sequence' => 'integer',
    ];
}
