<?php

namespace Workdo\Lead\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserLead extends Model
{
    protected $fillable = [
        'user_id',
        'lead_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
}