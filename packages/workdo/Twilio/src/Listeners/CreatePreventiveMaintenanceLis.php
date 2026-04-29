<?php

namespace Workdo\Twilio\Listeners;

use Workdo\ProductService\Models\ProductServiceItem;
use Workdo\CMMS\Events\CreatePreventiveMaintenance;
use Workdo\Twilio\Services\SendMsg;

class CreatePreventiveMaintenanceLis
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
    public function handle(CreatePreventiveMaintenance $event)
    {
        if (company_setting('Twilio New Pms') == 'on') {

            $request    = $event->request;
            $parts_item = ProductServiceItem::find($request->parts_ids);
            $to         = \Auth::user()->mobile_no;

            if (!empty($parts_item) && !empty($to)) {

                foreach ($parts_item as $item) {
                    $part[] = $item['name'];
                }
                $parts = implode(',', $part);

                $uArr = [
                    'part_name' => $parts,
                ];

                SendMsg::SendMsgs($to, $uArr, 'New Pms');
            }
        }
    }
}
