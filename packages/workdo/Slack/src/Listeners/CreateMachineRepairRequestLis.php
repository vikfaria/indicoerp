<?php

namespace Workdo\Slack\Listeners;

use Workdo\MachineRepairManagement\Events\CreateMachineRepairRequest;
use Workdo\MachineRepairManagement\Models\Machine;
use Workdo\Slack\Services\SendMsg;

class CreateMachineRepairRequestLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateMachineRepairRequest $event)
    {
        $repair_request = $event->machinerepairrequest;
        $machine = Machine::find($repair_request->machine_id);
        
        if (company_setting('Slack New Repair Request') == 'on') {
            $uArr = [
                'machine_name' => $machine->machine_name
            ];

            SendMsg::SendMsgs($uArr, 'New Repair Request');
        }
    }
}