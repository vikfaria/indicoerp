<?php

namespace Workdo\Webhook\Extractors;

class ZoomMeetingDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->meeting)) return [];

        $meeting = $event->meeting;

        return [
            'zoom_meeting' => [
                'id' => $meeting->id,
                'title' => $meeting->title,
                'description' => $meeting->description,
                'meeting_id' => $meeting->meeting_id,
                'meeting_password' => $meeting->meeting_password,
                'start_url' => $meeting->start_url,
                'join_url' => $meeting->join_url,
                'start_time' => $meeting->start_time,
                'duration' => $meeting->duration,
                'status' => $meeting->status,
                'created_at' => $meeting->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}