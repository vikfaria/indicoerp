<?php

namespace Workdo\Slack\Listeners;

use Workdo\FormBuilder\Events\FormConverted;
use Workdo\Slack\Services\SendMsg;

class FormConvertedLis
{
    public function __construct()
    {
        //
    }

    public function handle(FormConverted $event)
    {
        if (company_setting('Slack Convert To Modal') == 'on') {
            $uArr = [];

            SendMsg::SendMsgs($uArr, 'Convert To Modal');
        }
    }
}