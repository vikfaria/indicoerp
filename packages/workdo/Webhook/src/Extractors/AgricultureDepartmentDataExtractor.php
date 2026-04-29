<?php

namespace Workdo\Webhook\Extractors;

class AgricultureDepartmentDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->agriculturedepartment)) return [];

        $agriculturedepartment = $event->agriculturedepartment;

        return [
            'agriculture_department' => [
                'id' => $agriculturedepartment->id,
                'name' => $agriculturedepartment->name ?? null,
                'description' => $agriculturedepartment->description ?? null,
                'created_at' => $agriculturedepartment->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
