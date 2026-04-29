<?php

namespace Workdo\SupportTicket\Listeners;

use Workdo\SupportTicket\Events\CreatePublicTicket;
use App\Models\EmailTemplate;

class SendPublicTicketCreatedEmail
{
    public function handle(CreatePublicTicket $event)
    {
        if (Module_is_active('SupportTicket')) {
            $ticket = $event->ticket;
            
            $obj = [
                'ticket_name' => $ticket->name,
                'email' => $ticket->email,
                'ticket_id' => $ticket->ticket_id,
                'ticket_url' => route('SupportTicket/Show', $ticket->ticket_id),
            ];

            EmailTemplate::sendEmailTemplate('New Ticket', [$ticket->email], $obj, $ticket->created_by);
        }
    }
}