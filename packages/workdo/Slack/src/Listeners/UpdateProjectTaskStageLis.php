<?php

namespace Workdo\Slack\Listeners;

use Workdo\Slack\Services\SendMsg;
use Workdo\Taskly\Models\TaskStage;

class UpdateProjectTaskStageLis
{
    public function __construct()
    {
        //
    }

    public function handle($event)
    {
        $request = $event->request;
        $task = $event->task;

        if (company_setting('Slack Task Stage Updated') == 'on') {
            $oldStage = TaskStage::find($task->stage_id);
            $newStage = TaskStage::find($request->stage_id);

            $request['old_stage_id'] = $task->stage_id;
            $request['new_stage_id'] = $request->stage_id;
            $task->update(['stage_id' => $request->stage_id]);
            $uArr = [
                'task_name' => $task->title,
                'old_status' => $oldStage->name,
                'new_status' => $newStage->name,
            ];

            SendMsg::SendMsgs($uArr, 'Task Stage Updated');
        }
    }
}
