<?php

namespace Workdo\SupportTicket\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\SupportTicket\Models\SupportTicketCustomPage;

class DestroyCustomPage
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public SupportTicketCustomPage $customPage
    ) {}
}