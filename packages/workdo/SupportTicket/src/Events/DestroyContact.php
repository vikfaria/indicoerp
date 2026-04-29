<?php

namespace Workdo\SupportTicket\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\SupportTicket\Models\Contact;

class DestroyContact
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Contact $contact
    ) {}
}