<?php

namespace Workdo\Webhook\Extractors;

class AgricultureProcessDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->agricultureprocess)) return [];

        $agricultureprocess = $event->agricultureprocess;

        return [
            'agriculture_process' => [
                'id' => $agricultureprocess->id,
                'name' => $agricultureprocess->name ?? null,
                'hours' => $agricultureprocess->hours ?? null,
                'description' => $agricultureprocess->description ?? null,
                'created_at' => $agricultureprocess->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
