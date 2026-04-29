<?php

namespace Workdo\Slack\Listeners;

use Workdo\FixEquipment\Events\CreateFixEquipmentConsumable;
use Workdo\FixEquipment\Models\FixEquipmentAsset;
use Workdo\Slack\Services\SendMsg;

class CreateFixEquipmentConsumableLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateFixEquipmentConsumable $event)
    {
        $consumables = $event->fixEquipmentConsumable;
        $asset = FixEquipmentAsset::find($consumables->asset_id);

        if (company_setting('Slack New Consumables') == 'on') {
            $uArr = [
                'name' => $consumables->title,
                'assets' => $asset->asset_name
            ];

            SendMsg::SendMsgs($uArr, 'New Consumables');
        }
    }
}