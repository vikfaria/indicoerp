<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\CMMS\Events\CreateWorkrequest;
use Workdo\CMMS\Models\CmmsComponent;
use Workdo\Twilio\Services\SendMsg;

class CreateWorkRequestLis
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
    public function handle(CreateWorkrequest $event)
    {
        $request   = $event->request;
        $workorder = $event->workOrder;

        if (company_setting('Twilio Work Order Request', $workorder->created_by) == 'on') {

            $user      = $request->user_name;
            $component = CmmsComponent::find($request->components_id);
            $to        = User::find($component->created_by)->mobile_no;

            if (!empty($component) && !empty($to)) {
                $uArr = [
                    'component_name' => $component->name,
                    'user_name'      => $user,
                ];

                SendMsg::SendMsgs($to, $uArr, 'Work Order Request', $component->created_by);
            }
        }
    }
}
