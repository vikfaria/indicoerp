<?php

namespace Workdo\SupportTicket\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory;
    protected $fillable = [
        'ticket_id',
        'name',
        'email',
        'user_id',
        'account_type',
        'category',
        'subject',
        'status',
        'description',
        'attachments',
        'note',
        'creator_id',
        'created_by'
    ];

    protected $casts = [
        'attachments' => 'array'
    ];

    public function conversions(): HasMany
    {
        return $this->hasMany(Conversion::class, 'ticket_id')->orderBy('id');
    }

    public function tcategory(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category');
    }
}