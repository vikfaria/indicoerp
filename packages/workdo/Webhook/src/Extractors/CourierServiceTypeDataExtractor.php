<?php

namespace Workdo\Webhook\Extractors;

class CourierServiceTypeDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->serviceType)) return [];

        $serviceType = $event->serviceType;

        return [
            'courier_service_type' => [
                'id' => $serviceType->id,
                'name' => $serviceType->name,
                'created_at' => $serviceType->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
