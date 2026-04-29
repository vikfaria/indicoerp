<?php

namespace Workdo\Webhook\Extractors;

class SchoolHomeworkDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->homework)) return [];

        $homework = $event->homework;
        $status = $homework->is_active == true ? 'Active' : 'Inactive';

        return [
            'school_homework' => [
                'id' => $homework->id,
                'title' => $homework->title,
                'description' => $homework->description,
                'class' => $homework->class?->name ?? null,
                'subject' => $homework->subject?->name ?? null,
                'assigned_date' => $homework->assigned_date,
                'due_date' => $homework->due_date,
                'priority' => $homework->priority,
                'instructions' => $homework->instructions,
                'status' => $status,
                'created_at' => $homework->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
