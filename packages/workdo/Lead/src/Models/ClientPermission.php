<?php

namespace Workdo\Lead\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ClientPermission extends Model
{
    protected $fillable = [
        'client_id',
        'deal_id',
        'permissions'
    ];

    protected $casts = [
        'permissions' => 'array'
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function deal()
    {
        return $this->belongsTo(Deal::class, 'deal_id');
    }
}