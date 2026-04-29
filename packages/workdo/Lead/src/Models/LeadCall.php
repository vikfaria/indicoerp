<?php

namespace Workdo\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class LeadCall extends Model
{
    protected $fillable = [
        'lead_id',
        'subject',
        'call_type',
        'duration',
        'user_id',
        'description',
        'call_result',
    ];

    protected $casts = [
        'lead_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}