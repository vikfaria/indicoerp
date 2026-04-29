<?php

namespace Workdo\Webhook\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Webhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'method',
        'action',
        'url',
        'is_active',
        'creator_id',
        'created_by'
    ];
    
    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function webhookModule()
    {
        return $this->belongsTo(WebhookModule::class, 'action');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}