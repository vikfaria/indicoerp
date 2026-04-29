<?php

namespace Workdo\Webhook\Extractors;

class TimeTrackerDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->timeTracker)) return [];

        $timeTracker = $event->timeTracker;

        return [
            'time_tracker' => [
                'id' => $timeTracker->id,
                'name' => $timeTracker->name ?? null,
                'project' => $timeTracker->project ? $timeTracker->project->name : null,
                'start_time' => $timeTracker->start_time ?? null,
                'traker_id' => $timeTracker->traker_id ?? null,
                'created_at' => $timeTracker->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
