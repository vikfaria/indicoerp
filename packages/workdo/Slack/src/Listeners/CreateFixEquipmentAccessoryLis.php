<?php

namespace Workdo\Slack\Listeners;

use Workdo\FixEquipment\Events\CreateFixEquipmentAccessory;
use Workdo\Slack\Services\SendMsg;

class CreateFixEquipmentAccessoryLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateFixEquipmentAccessory $event)
    {
        $accessories = $event->fixEquipmentAccessory;

        if (company_setting('Slack New Accessories') == 'on') {
            $uArr = [
                'name' => $accessories->title,
                'supplier_name' => $accessories->supplier->name
            ];

            SendMsg::SendMsgs($uArr, 'New Accessories');
        }
    }
}
