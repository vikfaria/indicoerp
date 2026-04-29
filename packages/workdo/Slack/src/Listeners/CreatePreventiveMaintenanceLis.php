<?php

namespace Workdo\Slack\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\CMMS\Events\CreatePreventiveMaintenance;
use Workdo\ProductService\Models\ProductServiceItem;
use Workdo\Slack\Services\SendMsg;

class CreatePreventiveMaintenanceLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreatePreventiveMaintenance $event)
    {
        $request = $event->request;

        if (company_setting('Slack New Pms') == 'on') {
            $partIds = is_array($request->parts_ids)
                ? $request->parts_ids
                : explode(',', $request->parts_ids);

            $partNames = ProductServiceItem::whereIn('id', $partIds)->pluck('name')->toArray();
            $part = implode(',', $partNames);

            $uArr = [
                'part_name' => $part,
            ];

            SendMsg::SendMsgs($uArr, 'New Pms');
        }
    }
}
