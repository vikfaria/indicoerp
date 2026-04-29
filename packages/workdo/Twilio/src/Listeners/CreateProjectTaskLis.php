<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\Taskly\Events\CreateProjectTask;
use Workdo\Taskly\Models\Project;
use Workdo\Twilio\Services\SendMsg;

class CreateProjectTaskLis
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
    public function handle(CreateProjectTask $event)
    {
        if (company_setting('Twilio New Task') == 'on') {
            $request = $event->task;

            $userIds            = is_array($request->assigned_to) ? $request->assigned_to : explode(',', $request->assigned_to ?? '');
            $Assign_user_phones = User::whereIn('id', $userIds)->get();
            $project            = Project::where('id', $request->project_id)->first();

            foreach ($Assign_user_phones as $Assign_user_phone) {
                if (!empty($Assign_user_phone->mobile_no)) {
                    $uArr = [
                        'task_name'    => $request->title,
                        'project_name' => $project->name
                    ];

                    SendMsg::SendMsgs($Assign_user_phone->mobile_no, $uArr, 'New Task');
                }
            }
        }
    }
}
