<?php

namespace Workdo\Slack\Listeners;

use Workdo\School\Events\CreateParent;
use Workdo\Slack\Services\SendMsg;

class CreateParentLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateParent $event)
    {
        $parent = $event->parent;

        if (company_setting('Slack New Parents') == 'on') {
            $uArr = [
                'parent_name' => $parent->father_name ?? $parent->mother_name ?? $parent->guardian_name
            ];

            SendMsg::SendMsgs($uArr, 'New Parents');
        }
    }
}