<?php

namespace Workdo\Telegram\Listeners;

use App\Models\User;
use Workdo\Telegram\Services\SendMsg;
use Workdo\CMMS\Events\CreateComponent;


class CreateComponentLis
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
    public function handle(CreateComponent $event)
    {
        if(company_setting('Telegram New Component')  == 'on')
        {
            $component = $event->component;
            if(!empty($component)){
                $uArr = [
                    'component_name' => $component->name,
                ];
                SendMsg::SendMsgs($uArr , 'New Component');
            }
        }
    }
}
