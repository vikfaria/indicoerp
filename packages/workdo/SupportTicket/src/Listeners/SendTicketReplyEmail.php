<?php

namespace Workdo\SupportTicket\Listeners;

use Workdo\SupportTicket\Events\ReplyTicket;
use App\Models\EmailTemplate;

class SendTicketReplyEmail
{
    public function handle(ReplyTicket $event)
    {
        if (Module_is_active('SupportTicket')) {
            $ticket = $event->ticket;
            $conversion = $event->conversion;
            
            $obj = [
                'ticket_name' => $ticket->name,
                'ticket_id' => $ticket->ticket_id,
                'reply_description' => $conversion->description,
            ];

            EmailTemplate::sendEmailTemplate('New Ticket Reply', [$ticket->email], $obj, $ticket->created_by);
        }
    }
}