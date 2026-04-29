<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\CMMS\Events\CreateWorkorder;
use Workdo\Twilio\Services\SendMsg;

class CreateWorkOrderLis
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
    public function handle(CreateWorkOrder $event)
    {
        if (company_setting('Twilio Work Order Assigned') == 'on') {

            $request = $event->request;

            $wo_name = $request->workorder_name;
            $users   = User::find($request->user_ids);
            $to      = \Auth::user()->mobile_no;

            if (!empty($users) && !empty($to)) {
                foreach ($users as $item) {
                    $user[] = $item['name'];
                }
                $user = implode(',', $user);

                $uArr = [
                    'wo_name'   => $wo_name,
                    'user_name' => $user,
                ];

                SendMsg::SendMsgs($to, $uArr, 'Work Order Assigned');
            }
        }
    }
}
