<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\FixEquipment\Events\CreateFixEquipmentAccessory;
use Workdo\Twilio\Services\SendMsg;

class CreateFixEquipmentAccessoryLis
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
    public function handle(CreateFixEquipmentAccessory $event)
    {
        if (company_setting('Twilio New Accessories') == 'on') {

            $accessories = $event->fixEquipmentAccessory;
            $supplier    = User::find($accessories->supplier_id);
            $to          = $supplier->mobile_no;

            if (!empty($to)) {
                $uArr = [
                    'name'          => $accessories->title,
                    'supplier_name' => $supplier->name
                ];

                SendMsg::SendMsgs($to, $uArr, 'New Accessories');
            }
        }
    }
}
