<?php

namespace Workdo\Webhook\Extractors;

class FreightContainerDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->freightContainer)) return [];

        $freightContainer = $event->freightContainer;
        $status = $freightContainer->status == 1 ? 'Active' : 'Inactive';

        return [
            'freight_container' => [
                'id' => $freightContainer->id,
                'name' => $freightContainer->name,
                'size' => $freightContainer->size,
                'size_uom' => $freightContainer->size_uom,
                'volume_price' => $freightContainer->volume_price,
                'code' => $freightContainer->code,
                'container_number' => $freightContainer->container_number,
                'status' => $status,
                'created_at' => $freightContainer->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
