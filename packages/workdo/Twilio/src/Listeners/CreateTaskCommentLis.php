<?php

namespace Workdo\Twilio\Listeners;

use Workdo\Taskly\Events\CreateTaskComment;
use Workdo\Taskly\Models\ProjectTask;
use Workdo\Twilio\Services\SendMsg;

class CreateTaskCommentLis
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
    public function handle(CreateTaskComment $event)
    {
        if (company_setting('Twilio New Task Comment') == 'on') {

            $comment = $event->comment;
            $to      = \Auth::user()->mobile_no;

            if (!empty($comment) && !empty($to)) {
                $task = ProjectTask::where('id', $comment->task_id)->first();
                $uArr = [
                    'task_name' => $task->title,
                ];

                SendMsg::SendMsgs($to, $uArr, 'New Task Comment');
            }
        }
    }
}
