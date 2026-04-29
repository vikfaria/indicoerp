<?php

namespace Workdo\Telegram\Listeners;

use Workdo\FixEquipment\Events\CreateFixEquipmentComponent;
use Workdo\FixEquipment\Models\FixEquipmentAsset;
use Workdo\Telegram\Services\SendMsg;

class CreateFixEquipmentComponentLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateFixEquipmentComponent $event)
    {
        $component = $event->fixEquipmentComponent;
        $asset = FixEquipmentAsset::find($component->asset_id);

        if (company_setting('Telegram New Fix Equipment Component')  == 'on') {

            $uArr = [
                'name'   => $component->title,
                'assets' => $asset->asset_name
            ];
            SendMsg::SendMsgs($uArr , 'New Fix Equipment Component');
        }
    }
}
