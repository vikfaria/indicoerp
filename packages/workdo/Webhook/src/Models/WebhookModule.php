<?php

namespace Workdo\Webhook\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WebhookModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'module',
        'submodule', 
        'type'
    ];

    public function webhooks()
    {
        return $this->hasMany(Webhook::class, 'action');
    }
}