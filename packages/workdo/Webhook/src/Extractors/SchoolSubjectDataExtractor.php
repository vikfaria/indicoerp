<?php

namespace Workdo\Webhook\Extractors;

class SchoolSubjectDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->subject)) return [];

        $subject = $event->subject;
        $status = $subject->is_active == true ? 'Active' : 'Inactive';

        return [
            'school_subject' => [
                'id' => $subject->id,
                'name' => $subject->name,
                'code' => $subject->code,
                'description' => $subject->description,
                'subject_type' => $subject->subject_type,
                'credit_hours' => $subject->credit_hours,
                'status' => $status,
                'created_at' => $subject->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
