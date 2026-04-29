<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\FixEquipment\Events\CreateFixEquipmentMaintenance;
use Workdo\FixEquipment\Models\FixEquipmentAsset;
use Workdo\Twilio\Services\SendMsg;

class CreateFixEquipmentMaintenanceLis
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
    public function handle(CreateFixEquipmentMaintenance $event)
    {
        if (company_setting('Twilio New Maintenance') == 'on') {

            $maintenance = $event->fixEquipmentMaintenance;

            $asset = FixEquipmentAsset::find($maintenance->asset_id);
            $user  = User::find($maintenance->created_by);

            if (!empty($user->mobile_no)) {
                $uArr = [
                    'name'  => $maintenance->maintenance_type,
                    'asset' => $asset->asset_name,
                    'date'  => $maintenance->maintenance_date
                ];

                SendMsg::SendMsgs($user->mobile_no, $uArr, 'New Maintenance');
            }
        }
    }
}
