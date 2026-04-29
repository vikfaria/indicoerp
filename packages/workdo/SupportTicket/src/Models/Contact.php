<?php

namespace Workdo\SupportTicket\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $table = 'support_contacts';
    
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'subject',
        'message',
        'creator_id',
        'created_by'
    ];
}