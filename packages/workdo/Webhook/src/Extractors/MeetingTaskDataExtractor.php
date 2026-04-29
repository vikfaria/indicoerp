<?php

namespace Workdo\Webhook\Extractors;

class MeetingTaskDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->meetingMinuteTask)) return [];

        $meetingMinuteTask = $event->meetingMinuteTask;

        return [
            'meeting_task' => [
                'id' => $meetingMinuteTask->id,
                'meeting_minute_caller' => $meetingMinuteTask->meetingMinute ? $meetingMinuteTask->meetingMinute->caller : null,
                'name' => $meetingMinuteTask->name ?? null,
                'date' => $meetingMinuteTask->date ?? null,
                'time' => $meetingMinuteTask->time ?? null,
                'priority' => $meetingMinuteTask->priority ?? null,
                'created_at' => $meetingMinuteTask->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
