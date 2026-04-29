<?php

namespace Workdo\Twilio\Listeners;

use App\Events\CreateWarehouse;
use Workdo\Twilio\Services\SendMsg;

class CreateWarehouseLis
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
    public function handle(CreateWarehouse $event)
    {
        if (company_setting('Twilio New Warehouse') == 'on') {

            $warehouse = $event->warehouse;
            $to        = \Auth::user()->mobile_no;

            if (!empty($warehouse) && !empty($to)) {
                $uArr = [
                    'warehouse_name' => $warehouse->name,
                ];

                SendMsg::SendMsgs($to, $uArr, 'New Warehouse');
            }
        }
    }
}
