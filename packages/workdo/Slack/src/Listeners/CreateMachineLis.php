<?php

namespace Workdo\Slack\Listeners;

use Workdo\MachineRepairManagement\Events\CreateMachine;
use Workdo\Slack\Services\SendMsg;

class CreateMachineLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateMachine $event)
    {
        $machine = $event->machine;
        
        if (company_setting('Slack New Machine') == 'on') {
            $uArr = [
                'machine_name' => $machine->machine_name
            ];

            SendMsg::SendMsgs($uArr, 'New Machine');
        }
    }
}