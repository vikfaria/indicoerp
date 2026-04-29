<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\FixEquipment\Events\CreateFixEquipmentAudit;
use Workdo\FixEquipment\Models\FixEquipmentAsset;
use Workdo\Twilio\Services\SendMsg;

class CreateFixEquipmentAuditLis
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
    public function handle(CreateFixEquipmentAudit $event)
    {
        if (company_setting('Twilio New Audit') == 'on') {

            $audit = $event->fixEquipmentAudit;

            $asset = FixEquipmentAsset::whereIn('id', $audit->asset_ids)->get()->pluck('asset_name');

            $asset_detail = [];
            if (count($asset) > 0) {
                foreach ($asset as $datasand) {
                    $asset_detail[] = $datasand;
                }
            }
            $assets = implode(',', $asset_detail);
            $user   = User::find($audit->created_by);

            if (!empty($user->mobile_no)) {
                $uArr = [
                    'name'   => $audit->title,
                    'assets' => $assets
                ];

                SendMsg::SendMsgs($user->mobile_no, $uArr, 'New Audit');
            }
        }
    }
}
