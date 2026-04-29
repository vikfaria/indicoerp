<?php

namespace Workdo\Telegram\Listeners;

use Workdo\Feedback\Events\CreateHistory;
use Workdo\Feedback\Models\TemplateModule;
use Workdo\Telegram\Services\SendMsg;

class CreateHistoryLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateHistory $event)
    {
        $rating = $event->history;
        $module = TemplateModule::find($rating->module_id);
        $user   = (json_decode($rating->content));
        if (company_setting('Telegram New Feedback Rating')  == 'on') {

            if(!empty($module) && !empty($user))
            {
                $uArr = [
                    'module_name' => $module->submodule,
                    'user_name' => $user->name
                ];
                SendMsg::SendMsgs($uArr , 'New Feedback Rating');
            }
        }
    }
}
