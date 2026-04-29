<?php

namespace Workdo\Recruitment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class JobLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'remote_work',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'status',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'remote_work' => 'boolean',
            'status' => 'boolean'
        ];
    }
}