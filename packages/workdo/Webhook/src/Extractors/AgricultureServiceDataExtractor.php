<?php

namespace Workdo\Webhook\Extractors;

class AgricultureServiceDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->agricultureservice)) return [];

        $agricultureservice = $event->agricultureservice;

        return [
            'agriculture_service' => [
                'id' => $agricultureservice->id,
                'name' => $agricultureservice->name ?? null,
                'activity' => $agricultureservice->activity ? $agricultureservice->activity->name : null,
                'qty' => $agricultureservice->qty ?? null,
                'uom' => $agricultureservice->uom ?? null,
                'unit_price' => $agricultureservice->unit_price ?? null,
                'total_price' => $agricultureservice->total_price ?? null,
                'created_at' => $agricultureservice->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
