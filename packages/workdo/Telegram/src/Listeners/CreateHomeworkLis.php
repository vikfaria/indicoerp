<?php

namespace Workdo\Telegram\Listeners;

use Workdo\School\Events\CreateHomework;
use Workdo\Telegram\Services\SendMsg;

class CreateHomeworkLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateHomework $event)
    {
        $homework = $event->homework;
        $class    = $homework->class;
        $subject  = $homework->subject;

        if (company_setting('Telegram New Homework')  == 'on') {

            if(!empty($homework) && !empty($class) && !empty($subject))
            {
                $uArr = [
                    'homework_title' => $homework->title,
                    'class_name'     => $class->name,
                    'subject'        => $subject->name
                ];
                SendMsg::SendMsgs($uArr , 'New Homework');
            }
        }
    }
}
