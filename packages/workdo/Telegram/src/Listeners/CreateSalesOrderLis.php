<?php

namespace Workdo\Telegram\Listeners;

use Workdo\Telegram\Services\SendMsg;
use Workdo\Sales\Events\CreateSalesOrder;


class CreateSalesOrderLis
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
    public function handle(CreateSalesOrder $event)
    {
        if(company_setting('Telegram New Sales Order')  == 'on')
        {
            $salesorder = $event->salesOrder;
            if(!empty($salesorder))
            {
                $uArr = [
                    'sales_order_id' => $salesorder->quote_number
                ];
                SendMsg::SendMsgs($uArr , 'New Sales Order');
            }
        }
    }
}
