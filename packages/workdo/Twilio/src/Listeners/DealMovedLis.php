<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\Lead\Events\DealMoved;
use Workdo\Lead\Models\DealStage;
use Workdo\Lead\Models\UserDeal;
use Workdo\Twilio\Services\SendMsg;

class DealMovedLis
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
    public function handle(DealMoved $event)
    {
        if (company_setting('Twilio Deal Moved') == 'on') {
            $deal    = $event->deal;
            $request = $event->request;

            $newStage    = DealStage::where('id', $request->stage_id)->first();
            $user        = UserDeal::where('deal_id', $deal->id)->get()->pluck('user_id');
            $AssignUsers = User::find($user);

            foreach ($AssignUsers as $AssignUser) {
                $to = $AssignUser->mobile_no;

                if (!empty($to)) {
                    $uArr = [
                        'deal_name' => $deal->name,
                        'old_stage' => $deal->stage->name,
                        'new_stage' => $newStage->name,
                    ];

                    SendMsg::SendMsgs($to, $uArr, 'Deal moved');
                }
            }
        }
    }
}
