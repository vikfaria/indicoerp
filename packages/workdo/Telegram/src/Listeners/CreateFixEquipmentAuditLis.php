<?php

namespace Workdo\Telegram\Listeners;

use Workdo\FixEquipment\Events\CreateFixEquipmentAudit;
use Workdo\FixEquipment\Models\FixEquipmentAsset;
use Workdo\Telegram\Services\SendMsg;
use Workdo\FixEquipment\Models\FixEquipmentAudit;

class CreateFixEquipmentAuditLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateFixEquipmentAudit $event)
    {
        $audit = $event->fixEquipmentAudit;
        $assetIds = is_array($audit->asset_ids)
        ? $audit->asset_ids
        : explode(',', $audit->asset_ids);
        $asset = FixEquipmentAsset::whereIn('id', $assetIds)->pluck('asset_name')->toArray();
        $assets = implode(',', $asset);

        if (company_setting('Telegram New Audit')  == 'on') {

            $uArr = [
                'name' => $audit->title,
                'assets' => $assets
            ];
            SendMsg::SendMsgs($uArr , 'New Audit');
        }
    }
}
