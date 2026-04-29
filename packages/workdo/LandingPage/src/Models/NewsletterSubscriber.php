<?php

namespace Workdo\LandingPage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $table = 'newsletter_subscribers';

    protected $fillable = [
        'email',
        'subscribed_at',
        'ip_address',
        'country',
        'city',
        'region',
        'country_code',
        'isp',
        'org',
        'timezone',
        'latitude',
        'longitude',
        'user_agent',
        'browser',
        'os',
        'device',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }
}