<?php

namespace Workdo\Twilio\Listeners;

use Workdo\VisitorManagement\Events\CreateVisitor;
use Workdo\Twilio\Services\SendMsg;

class CreateVisitorLis
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(CreateVisitor $event)
    {
        if (company_setting('Twilio New Visitor') == 'on') {
            $visitor = $event->visitor;

            if (!empty($visitor->phone)) {
                $uArr = [
                    'name' => $visitor->name,
                ];

                SendMsg::SendMsgs($visitor->phone, $uArr, 'New Visitor');
            }
        }
    }
}
