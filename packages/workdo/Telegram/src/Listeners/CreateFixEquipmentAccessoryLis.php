<?php

namespace Workdo\Telegram\Listeners;

use Workdo\Telegram\Services\SendMsg;
use Workdo\FixEquipment\Events\CreateFixEquipmentAccessory;
use App\Models\User;

class CreateFixEquipmentAccessoryLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateFixEquipmentAccessory $event)
    {
        $accessories = $event->fixEquipmentAccessory;
        $supplier = User::find($accessories->supplier_id);

        if (company_setting('Telegram New Accessories')  == 'on') {

            $uArr = [
                'name' => $accessories->title,
                'supplier_name' => $supplier->name
            ];
            SendMsg::SendMsgs($uArr , 'New Accessories');

        }
    }
}
