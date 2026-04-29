<?php

namespace Workdo\Hrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'max_days_per_year',
        'is_paid',
        'color',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean'
        ];
    }




}