<?php

namespace Workdo\Calendar\Listeners;

use Workdo\Calendar\Models\CalenderUtility;

class CreateWorkorderLis
{
    public function handle($event)
    {
        try {
            if (module_is_active('Calendar') && $event->request->get('sync_to_google_calendar') == true) {
                $workorder = $event->workOrder;
                $request = $event->request;
                $type = 'work_order';
                $workorder->title = $request->workorder_name;
                $workorder->start_date = $request->due_date . ' ' . $request->time;
                $workorder->end_date = $request->due_date . ' ' . $request->time;
                $created_by = $workorder->created_by ?? null;

                CalenderUtility::addCalendarData($workorder, $type, $created_by);
            }
        } catch (\Exception $e) {
            // Silent fail
        }
    }
}
