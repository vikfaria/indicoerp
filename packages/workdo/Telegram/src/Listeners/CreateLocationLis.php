<?php

namespace Workdo\Telegram\Listeners;

use App\Models\User;
use Workdo\Telegram\Services\SendMsg;
use Workdo\CMMS\Events\CreateLocation;


class CreateLocationLis
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
    public function handle(CreateLocation $event)
    {
        if(company_setting('Telegram New Location') == 'on')
        {
            $location = $event->location;
            if(!empty($location)){
                $uArr = [
                    'location_name' => $location->name,
                ];
                SendMsg::SendMsgs($uArr , 'New Location');
            }
        }
    }
}
