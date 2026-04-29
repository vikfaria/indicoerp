<?php

namespace Workdo\Webhook\Extractors;

class FreightServiceDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->freightService)) return [];

        $freightService = $event->freightService;

        return [
            'freight_service' => [
                'id' => $freightService->id,
                'name' => $freightService->name,
                'sale_price' => $freightService->sale_price,
                'cost_price' => $freightService->cost_price,
                'created_at' => $freightService->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
