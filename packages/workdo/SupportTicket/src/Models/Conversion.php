<?php

namespace Workdo\SupportTicket\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversion extends Model
{
    protected $table = 'support_ticket_conversions';
    
    protected $fillable = [
        'ticket_id',
        'sender',
        'description',
        'attachments',
        'creator_id',
        'created_by'
    ];

    protected $casts = [
        'attachments' => 'array'
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function replyBy()
    {
        if ($this->sender == 'admin') {
            return $this->createdBy;
        } else {
            return $this->ticket->creator ?? (object)['name' => 'User'];
        }
    }
}