<?php

namespace Workdo\SupportTicket\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use Workdo\SupportTicket\Models\Ticket;
use Workdo\SupportTicket\Models\Conversion;

class CreateTicketConversion
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Request $request,
        public Ticket $ticket,
        public Conversion $conversion,
    ) {}
}