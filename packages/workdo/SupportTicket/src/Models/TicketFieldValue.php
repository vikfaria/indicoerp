<?php

namespace Workdo\SupportTicket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketFieldValue extends Model
{
    protected $fillable = [
        'record_id',
        'field_id',
        'value'
    ];

    public function field(): BelongsTo
    {
        return $this->belongsTo(TicketField::class, 'field_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'record_id');
    }
}