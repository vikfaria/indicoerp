<?php

namespace Workdo\SupportTicket\Listeners;

use Workdo\SupportTicket\Events\CreateTicket;
use App\Models\EmailTemplate;

class SendTicketCreatedEmail
{
    public function handle(CreateTicket $event)
    {
        if (Module_is_active('SupportTicket')) {
            $ticket = $event->ticket;
            
            $obj = [
                'ticket_name' => $ticket->name,
                'email' => $ticket->email,
                'ticket_id' => $ticket->ticket_id,
                'ticket_url' => route('support-tickets.show', \Illuminate\Support\Facades\Crypt::encrypt($ticket->id)),
            ];

            EmailTemplate::sendEmailTemplate('New Ticket', [$ticket->email], $obj, $ticket->created_by);
        }
    }
}