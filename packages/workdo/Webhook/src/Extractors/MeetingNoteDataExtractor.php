<?php

namespace Workdo\Webhook\Extractors;

class MeetingNoteDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->meetingMinuteNote)) return [];

        $meetingMinuteNote = $event->meetingMinuteNote;

        return [
            'meeting_note' => [
                'id' => $meetingMinuteNote->id,
                'meeting_minute_caller' => $meetingMinuteNote->meetingMinute ? $meetingMinuteNote->meetingMinute->caller : null,
                'note' => $meetingMinuteNote->note ?? null,
                'user' => $meetingMinuteNote->user ? $meetingMinuteNote->user->name : null,
                'created_at' => $meetingMinuteNote->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
