<?php

namespace Workdo\Slack\Listeners;

use App\Events\CreateWarehouse;
use Workdo\Slack\Services\SendMsg;

class CreateWarehouseLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateWarehouse $event)
    {
        $warehouse = $event->warehouse;

        if (company_setting('Slack New Warehouse') == 'on') {
            $uArr = [
                'warehouse_name' => $warehouse->name,
            ];
            
            SendMsg::SendMsgs($uArr, 'New Warehouse');
        }
    }
}
