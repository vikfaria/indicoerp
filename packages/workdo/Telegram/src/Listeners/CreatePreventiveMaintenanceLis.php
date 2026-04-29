<?php

namespace Workdo\Telegram\Listeners;

use Workdo\Telegram\Services\SendMsg;
use Workdo\CMMS\Events\CreatePreventiveMaintenance;
use Workdo\ProductService\Models\ProductServiceItem;

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
        if(company_setting('Telegram New Pms')  == 'on')
        {
            $pms      = $event->preventiveMaintenance;
            $ids        = explode(',', $pms->parts_id);
            if(Module_is_active('ProductService')) {
                $parts_item = ProductServiceItem::whereIn('id', $ids)->get();
                $part       = $parts_item->pluck('name')->toArray();
                $parts      = implode(',', $part);
            } else {
                $parts = '';
            }

            if(!empty($parts)){
                $uArr = [
                    'part_name' => $parts,
                ];
                SendMsg::SendMsgs($uArr , 'New Pms');
            }
        }
    }
}
