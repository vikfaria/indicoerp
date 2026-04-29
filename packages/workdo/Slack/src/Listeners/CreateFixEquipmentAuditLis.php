<?php

namespace Workdo\Slack\Listeners;

use Workdo\FixEquipment\Events\CreateFixEquipmentAsset;
use Workdo\FixEquipment\Events\CreateFixEquipmentAudit;
use Workdo\FixEquipment\Models\FixEquipmentAsset;
use Workdo\Slack\Services\SendMsg;

class CreateFixEquipmentAuditLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateFixEquipmentAudit $event)
    {
        $audit = $event->fixEquipmentAudit;

        $auditId = is_array($audit->asset_ids) ? $audit->asset_ids : explode(',', $audit->asset_ids);

        $assetTitles = FixEquipmentAsset::whereIn('id', $auditId)
            ->pluck('asset_name')
            ->toArray();

        $assets = implode(', ', $assetTitles);

        if (company_setting('Slack New Audit') == 'on') {
            $uArr = [
                'name'   => $audit->title,
                'assets' => $assets
            ];

            SendMsg::SendMsgs($uArr, 'New Audit');
        }
    }
}
