<?php

namespace Workdo\Telegram\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\Taskly\Events\UpdateProjectTaskStage;
use Workdo\Taskly\Models\TaskStage;
use Workdo\Telegram\Services\SendMsg;

class UpdateProjectTaskStageLis
{
    public function __construct()
    {
        //
    }

    public function handle(UpdateProjectTaskStage $event)
    {
        if(company_setting('Telegram Task Stage Updated')  == 'on')
        {
            $request = $event->request;
            $task    = $event->task;

            if(!empty($task))
            {
                $new_status   = TaskStage::where('id',$request->new_stage_id)->first();
                $old_status   = TaskStage::where('id',$request->old_stage_id)->first();

                $uArr = [
                    'task_name'  => $task->title,
                    'old_status' => $old_status->name,
                    'new_status' => $new_status->name,
                ];

                SendMsg::SendMsgs($uArr , 'Task Stage Updated');
            }
        }
    }
}
