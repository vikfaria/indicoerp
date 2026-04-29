<?php

namespace Workdo\Webhook\Extractors;

class MeetingMinuteDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->meetingMinute)) return [];

        $meetingMinute = $event->meetingMinute;

        return [
            'meeting_minute' => [
                'id' => $meetingMinute->id,
                'caller' => $meetingMinute->caller ?? null,
                'contact_user' => $meetingMinute->contactUser ? $meetingMinute->contactUser->name : null,
                'log_type' => $meetingMinute->log_type ?? null,
                'phone_no' => $meetingMinute->phone_no ?? null,
                'call_start_time' => $meetingMinute->call_start_time ?? null,
                'call_end_time' => $meetingMinute->call_end_time ?? null,
                'status' => $meetingMinute->status ?? null,
                'priority' => $meetingMinute->priority ?? null,
                'note' => $meetingMinute->note ?? null,
                'completed' => $meetingMinute->completed ?? null,
                'important' => $meetingMinute->important ?? null,
                'duration' => $meetingMinute->duration ?? null,
                'created_at' => $meetingMinute->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
