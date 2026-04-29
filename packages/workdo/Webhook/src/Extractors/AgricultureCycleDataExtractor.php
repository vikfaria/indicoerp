<?php

namespace Workdo\Webhook\Extractors;

class AgricultureCycleDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->agriculturecycle)) return [];

        $agriculturecycle = $event->agriculturecycle;

        return [
            'agriculture_cycle' => [
                'id' => $agriculturecycle->id,
                'name' => $agriculturecycle->name ?? null,
                'created_at' => $agriculturecycle->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
