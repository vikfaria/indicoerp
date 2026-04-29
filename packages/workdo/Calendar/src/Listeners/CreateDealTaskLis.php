<?php

namespace Workdo\Calendar\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Workdo\Lead\Events\CreateDealTask;
use Workdo\Calendar\Models\CalenderUtility;

class CreateDealTaskLis
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
    public function handle(CreateDealTask $event)
    {
        if (module_is_active('Calendar') && $event->request->get('sync_to_google_calendar') == true) {
            $calendarDealTask = $event->dealTask;
            $calendarRequest = $event->request;

            $type = 'deal_task';
            $calendarDealTask->title = $calendarRequest->name;
            $calendarDealTask->start_date = $calendarRequest->date . ' ' . $calendarRequest->time;
            $calendarDealTask->end_date = $calendarRequest->date . ' ' . $calendarRequest->time;

            CalenderUtility::addCalendarData($calendarDealTask, $type, $calendarDealTask->created_by);
        }
    }
}
