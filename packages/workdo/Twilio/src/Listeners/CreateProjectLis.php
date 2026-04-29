<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\Taskly\Events\CreateProject;
use Workdo\Taskly\Models\ProjectUser;
use Workdo\Twilio\Services\SendMsg;

class CreateProjectLis
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
    public function handle(CreateProject $event)
    {
        if (company_setting('Twilio New Project') == 'on') {
            $project = $event->project;

            $projects           = ProjectUser::where('project_id', $project->id)->get()->pluck('user_id');
            $Assign_user_phones = User::whereIn('id', $projects)->get();

            foreach ($Assign_user_phones as $Assign_user_phone) {
                if (!empty($Assign_user_phone->mobile_no)) {
                    $uArr = [
                        'project_name' => $project->name
                    ];

                    SendMsg::SendMsgs($Assign_user_phone->mobile_no, $uArr, 'New Project');
                }
            }
        }
    }
}
