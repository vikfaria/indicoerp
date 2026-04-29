<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\Taskly\Models\Project;
use Workdo\Taskly\Events\CreateProjectBug;
use Workdo\Twilio\Services\SendMsg;

class CreateProjectBugLis
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
    public function handle(CreateProjectBug $event)
    {
        $request = $event->bug;

        if (company_setting('Twilio New Bug') == 'on') {

            $project     = Project::where('id', $request->project_id)->first();
            $userIds = is_array($request->assigned_to) ? $request->assigned_to : explode(',', $request->assigned_to ?? '');
            $AssignUsers   = User::whereIn('id', $userIds)->get();

            foreach ($AssignUsers as $AssignUser) {
                $to = $AssignUser->mobile_no;
                if (!empty($to)) {
                    $uArr = [
                        'bug_name'     => $request->title,
                        'project_name' => $project->name,
                    ];

                    SendMsg::SendMsgs($to, $uArr, 'New Bug');
                }
            }
        }
    }
}
