<?php

namespace Workdo\Slack\Listeners;

use Workdo\FixEquipment\Events\CreateFixEquipmentLicense;
use Workdo\FixEquipment\Models\FixEquipmentAsset;
use Workdo\Slack\Services\SendMsg;

class CreateFixEquipmentLicenseLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateFixEquipmentLicense $event)
    {
        $license = $event->fixEquipmentLicense;
        $asset = FixEquipmentAsset::find($license->asset_id);

        if (company_setting('Slack New Licence') == 'on') {
            $uArr = [
                'name' => $license->title,
                'assets' => $asset->asset_name
            ];

            SendMsg::SendMsgs($uArr, 'New Licence');
        }
    }
}