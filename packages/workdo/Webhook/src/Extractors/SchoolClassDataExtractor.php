<?php

namespace Workdo\Webhook\Extractors;

class SchoolClassDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->class)) return [];

        $class = $event->class;
        $status = $class->is_active == true ? 'Active' : 'Inactive';

        return [
            'school_class' => [
                'id' => $class->id,
                'name' => $class->name,
                'code' => $class->code,
                'grade' => $class->grade?->name ?? null,
                'branch' => $class->branch?->name ?? null,
                'capacity' => $class->capacity,
                'description' => $class->description,
                'status' => $status,
                'created_at' => $class->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
