<?php

namespace Workdo\Slack\Listeners;

use Workdo\FixEquipment\Events\CreateFixEquipmentComponent;
use Workdo\FixEquipment\Models\FixEquipmentAsset;
use Workdo\Slack\Services\SendMsg;

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

        if (company_setting('Slack New Fix Equipment Component') == 'on') {
            $uArr = [
                'name' => $component->title,
                'assets'=> $asset->asset_name
            ];

            SendMsg::SendMsgs($uArr, 'New Fix Equipment Component');
        }
    }
}