<?php

namespace Workdo\Slack\Listeners;

use App\Models\User;
use Workdo\Sales\Events\CreateSalesOrder;
use Workdo\Slack\Services\SendMsg;

class CreateSalesOrderLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateSalesOrder $event)
    {
        $salesorder = $event->salesOrder;

        if (company_setting('Slack New Sales Order') == 'on') {
            $uArr = [
                'sales_order_id' => $salesorder->quote_number
            ];

            SendMsg::SendMsgs($uArr, 'New Sales Order');
        }
    }
}
