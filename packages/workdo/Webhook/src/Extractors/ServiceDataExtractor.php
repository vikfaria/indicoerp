<?php

namespace Workdo\Webhook\Extractors;

class ServiceDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->service)) return [];

        $service = $event->service;

        return [
            'service' => [
                'id' => $service->id,
                'card_number' => $service->card_number,
                'title' => $service->title,
                'service_date' => $service->service_date,
                'return_date' => $service->return_date,
                'service_charge' => $service->service_charge,
                'notes' => $service->notes,
                'assign_to' => $service->assignTo?->name ?? null,
                'vehicle_type' => $service->vehicleType?->name ?? null,
                'repair_category' => $service->repairCategory?->name ?? null,
                'created_at' => $service->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
