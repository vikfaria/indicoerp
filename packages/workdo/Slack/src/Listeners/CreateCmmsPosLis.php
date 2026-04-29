<?php

namespace Workdo\Slack\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\CMMS\Events\CreateCmmsPos;
use Workdo\Slack\Services\SendMsg;

class CreateCmmsPosLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateCmmsPos $event)
    {
        $request = $event->request;
        $user = User::find($request->user_id);

        if (company_setting('Slack New POs') == 'on') {
            $uArr = [
                'user_name' => $user->name,
            ];

            SendMsg::SendMsgs($uArr, 'New POs');
        }
    }
}
