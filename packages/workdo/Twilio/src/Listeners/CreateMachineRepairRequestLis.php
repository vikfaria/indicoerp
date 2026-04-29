<?php

namespace Workdo\Twilio\Listeners;

use Workdo\MachineRepairManagement\Events\CreateMachineRepairRequest;
use Workdo\MachineRepairManagement\Models\Machine;
use Workdo\Twilio\Services\SendMsg;

class CreateMachineRepairRequestLis
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
    public function handle(CreateMachineRepairRequest $event)
    {
        if (company_setting('Twilio New Repair Request') == 'on') {

            $repair_request = $event->machinerepairrequest;

            $machine = Machine::find($repair_request->machine_id);
            $to      = \Auth::user()->mobile_no;

            if (!empty($machine) && !empty($to)) {
                $uArr = [
                    'machine_name' => $machine->machine_name
                ];

                SendMsg::SendMsgs($to, $uArr, 'New Repair Request');
            }
        }
    }
}
