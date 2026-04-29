<?php

namespace Workdo\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class DealCall extends Model
{
    protected $fillable = [
        'deal_id',
        'subject',
        'call_type',
        'duration',
        'user_id',
        'description',
        'call_result',
    ];

    protected $casts = [
        'deal_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function deal()
    {
        return $this->belongsTo(Deal::class, 'deal_id');
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