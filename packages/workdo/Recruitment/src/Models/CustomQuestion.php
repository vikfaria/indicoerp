<?php

namespace Workdo\Recruitment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class CustomQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'type',
        'options',
        'is_required',
        'is_active',
        'sort_order',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'string',
            'is_required' => 'boolean',
            'is_active' => 'boolean'
        ];
    }
}