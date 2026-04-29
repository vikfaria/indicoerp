<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\FixEquipment\Events\CreateFixEquipmentComponent;
use Workdo\FixEquipment\Models\FixEquipmentAsset;
use Workdo\Twilio\Services\SendMsg;

class CreateFixEquipmentComponentLis
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
    public function handle(CreateFixEquipmentComponent $event)
    {
        if (company_setting('Twilio New Fix Equipment Component') == 'on') {

            $component = $event->fixEquipmentComponent;

            $asset = FixEquipmentAsset::find($component->asset_id);
            $user  = User::find($component->created_by);

            if (!empty($user->mobile_no)) {
                $uArr = [
                    'name'   => $component->title,
                    'assets' => $asset->asset_name
                ];

                SendMsg::SendMsgs($user->mobile_no, $uArr, 'New Fix Equipment Component');
            }
        }
    }
}
