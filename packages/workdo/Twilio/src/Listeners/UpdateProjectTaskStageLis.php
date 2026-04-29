<?php

namespace Workdo\Twilio\Listeners;

use Workdo\Taskly\Models\TaskStage;
use Workdo\Twilio\Services\SendMsg;
use Workdo\Taskly\Events\UpdateProjectTaskStage;
use Illuminate\Support\Facades\Auth;

class UpdateProjectTaskStageLis
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
    public function handle(UpdateProjectTaskStage $event)
    {
        if (company_setting('Twilio Task Stage Updated') == 'on') {

            $request = $event->request;
            $task    = $event->task;
            $to      = Auth::user()->mobile_no;

            if (!empty($task) && !empty($to)) {
                $new_status = TaskStage::where('id', $request->new_stage_id)->first();
                $old_status = TaskStage::where('id', $request->old_stage_id)->first();

                $uArr = [
                    'task_name'  => $task->title,
                    'old_status' => $old_status->name,
                    'new_status' => $new_status->name,
                ];

                SendMsg::SendMsgs($to, $uArr, 'Task Stage Updated');
            }
        }
    }
}
