<?php

namespace Workdo\Slack\Listeners;

use App\Models\User;
use Workdo\FixEquipment\Events\CreateFixEquipmentAsset;
use Workdo\FixEquipment\Models\FixEquipmentLocation;
use Workdo\Slack\Services\SendMsg;

class CreateFixEquipmentAssetLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateFixEquipmentAsset $event)
    {
        $asset = $event->fixEquipmentAsset;
        $location = FixEquipmentLocation::find($asset->location_id);
        
        if (company_setting('Slack New Asset') == 'on') {
            $uArr = [
                'name' => $asset->asset_name,
                'supplier_name' => $asset->supplier->name,
                'location' => $location->name
            ];

            SendMsg::SendMsgs($uArr, 'New Asset');
        }
    }
}