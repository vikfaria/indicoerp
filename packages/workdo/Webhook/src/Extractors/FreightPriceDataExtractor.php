<?php

namespace Workdo\Webhook\Extractors;

class FreightPriceDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->freightPrice)) return [];

        $freightPrice = $event->freightPrice;

        return [
            'freight_price' => [
                'id' => $freightPrice->id,
                'name' => $freightPrice->name,
                'volume_price' => $freightPrice->volume_price,
                'weight_price' => $freightPrice->weight_price,
                'service_price' => $freightPrice->service_price,
                'created_at' => $freightPrice->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
