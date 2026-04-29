<?php

namespace Workdo\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'creator_id',
        'created_by',
    ];

}