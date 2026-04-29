<?php

namespace Workdo\Telegram\Listeners;

use Workdo\CMMS\Events\CreateCmmsPos;
use Workdo\Telegram\Services\SendMsg;
use Workdo\CMMS\Events\CreateSupplier;


class CreateCmmsPosLis
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
    public function handle(CreateCmmsPos $event)
    {
        if(company_setting('Telegram New POs')  == 'on')
        {
            $pos    = $event->pos;
            $user   = $pos->user;
            if(!empty($user)){
                $uArr = [
                    'user_name' => $user->name,
                ];
                SendMsg::SendMsgs($uArr , 'New POs');
            }
        }
    }
}
