<?php

namespace Workdo\Slack\Listeners;

use App\Models\User;
use Workdo\CMMS\Events\CreateWorkOrder;
use Workdo\Slack\Services\SendMsg;

class CreateWorkorderLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateWorkOrder $event)
    {
        $request = $event->request;
        $workorder_name = $request->workorder_name;

        if (company_setting('Slack Work Order Assigned') == 'on') {

            $userIds = is_array($event->workOrder->user_ids)
                ? $event->workOrder->user_ids
                : explode(',', $event->workOrder->user_ids);

            $userNames = User::whereIn('id', $userIds)->pluck('name')->toArray();

            if (!empty($user)) {
                $user = implode(',', $userNames);

                $uArr = [
                    'wo_name' => $workorder_name,
                    'user_name' => $user,
                ];
                SendMsg::SendMsgs($uArr, 'Work Order Assigned');
            }
        }
    }
}
