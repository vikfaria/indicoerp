<?php

namespace Workdo\Lead\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientDeal extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'deal_id',
    ];

    protected function casts(): array
    {
        return [
            'client_id' => 'integer',
            'deal_id' => 'integer',
        ];
    }

    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }
}