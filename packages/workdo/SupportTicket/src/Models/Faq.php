<?php

namespace Workdo\SupportTicket\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Faq extends Model
{
    protected $table = 'support_ticket_faqs';
    protected $fillable = [
        'title',
        'description',
        'creator_id',
        'created_by'
    ];
}