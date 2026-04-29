<?php

namespace Workdo\Webhook\Extractors;

class AgricultureOfficeDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->agricultureoffice)) return [];

        $agricultureoffice = $event->agricultureoffice;

        return [
            'agriculture_claim_type' => [
                'id' => $agricultureoffice->id,
                'name' => $agricultureoffice->name ?? null,
                'department' => $agricultureoffice->department ? $agricultureoffice->department->name : null,
                'created_at' => $agricultureoffice->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
