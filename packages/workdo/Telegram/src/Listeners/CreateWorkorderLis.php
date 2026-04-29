<?php

namespace Workdo\Telegram\Listeners;

use App\Models\User;
use Workdo\Telegram\Services\SendMsg;
use Workdo\CMMS\Events\CreateWorkorder;

class CreateWorkorderLis
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
    public function handle(CreateWorkorder $event)
    {
        if(company_setting('Telegram Work Order Assigned')  == 'on')
        {
            $workorder  = $event->workorder;
            $user_ids = is_array($workorder->user_ids)
            ? $workorder->user_ids
            : json_decode($workorder->user_ids, true);
            $user       = User::whereIn('id', $user_ids)->pluck('name')->toArray();
            $user_names = implode(',', $user);
            if(!empty($user_names)){
                $uArr = [
                    'wo_name'   => $workorder->workorder_name,
                    'user_name' => $user_names,
                ];
                SendMsg::SendMsgs($uArr , 'Work Order Assigned');
            }
        }
    }
}
