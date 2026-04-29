<?php

namespace Workdo\LandingPage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class LandingPageSetting extends Model
{
    protected $fillable = [
        'company_name',
        'contact_email',
        'contact_phone',
        'contact_address',
        'config_sections'
    ];

    protected $casts = [
        'config_sections' => 'array'
    ];

    protected static function booted()
    {
        static::saved(function () {
            Cache::forget('landing_page_settings');
        });

        static::deleted(function () {
            Cache::forget('landing_page_settings');
        });
    }
}