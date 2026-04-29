<?php

namespace Workdo\Webhook\Extractors;

class JobInterviewScheduleDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->interview)) return [];

        $interview = $event->interview;

        return [
            'interview_schedule' => [
                'id' => $interview->id,
                'job' => $interview->jobPosting?->title ?? null,
                'interview_round' => $interview->interviewRound?->name ?? null,
                'interview_type' => $interview->interviewType?->name ?? null,
                'scheduled_date' => $interview->scheduled_date,
                'scheduled_time' => $interview->scheduled_time,
                'duration' => $interview->duration,
                'location' => $interview->location,
                'meeting_link' => $interview->meeting_link,
                'created_at' => $interview->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}