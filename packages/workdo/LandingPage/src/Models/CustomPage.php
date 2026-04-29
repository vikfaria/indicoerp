<?php

namespace Workdo\LandingPage\Models;

use Illuminate\Database\Eloquent\Model;

class CustomPage extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'is_active',
        'is_disabled'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_disabled' => 'boolean'
    ];
}