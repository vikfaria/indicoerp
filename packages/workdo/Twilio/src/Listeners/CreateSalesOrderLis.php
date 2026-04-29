<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\Sales\Events\CreateSalesOrder;
use Workdo\Twilio\Services\SendMsg;

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
        if (company_setting('Twilio New Sales Order') == 'on') {
            $salesorder = $event->salesOrder;

            $AssignUser = User::find($salesorder->assign_user_id);
            $to         = $AssignUser->mobile_no;

            if (!empty($to)) {
                $uArr = [
                    'sales_order_id' => $salesorder->quote_number
                ];

                SendMsg::SendMsgs($to, $uArr, 'New Sales Order');
            }
        }
    }
}
