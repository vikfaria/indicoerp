<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditTrail extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'route',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'changes',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'user_id' => 'integer',
            'auditable_id' => 'integer',
            'old_values' => 'array',
            'new_values' => 'array',
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
