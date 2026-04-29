<?php

namespace Workdo\Twilio\Listeners;

use Workdo\MachineRepairManagement\Events\CreateMachine;
use Workdo\Twilio\Services\SendMsg;

class CreateMachineLis
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
    public function handle(CreateMachine $event)
    {
        if (company_setting('Twilio New Machine') == 'on') {

            $machine = $event->machine;
            $to      = \Auth::user()->mobile_no;

            if (!empty($machine) && !empty($to)) {
                $uArr = [
                    'machine_name' => $machine->machine_name
                ];

                SendMsg::SendMsgs($to, $uArr, 'New Machine');
            }
        }
    }
}
