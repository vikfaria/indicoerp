<?php

namespace Workdo\SupportTicket\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketCategory extends Model
{
    protected $fillable = [
        'name',
        'color',
        'creator_id',
        'created_by'
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'category');
    }
}