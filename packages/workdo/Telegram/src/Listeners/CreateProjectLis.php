<?php

namespace Workdo\Telegram\Listeners;

use Workdo\Telegram\Services\SendMsg;
use Workdo\Taskly\Events\CreateProject;


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
        if(company_setting('Telegram New Project')  == 'on')
        {
            $project = $event->project;
            if(!empty($project))
            {
                $uArr = [
                    'project_name' => $project->name
                ];
                SendMsg::SendMsgs($uArr , 'New Project');
            }
        }
    }
}
