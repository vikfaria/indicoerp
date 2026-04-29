<?php

namespace Workdo\Webhook\Extractors;

class CourierTrackingStatusDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->trackingStatus)) return [];

        $trackingStatus = $event->trackingStatus;

        return [
            'courier_tracking_status' => [
                'id' => $trackingStatus->id,
                'name' => $trackingStatus->name,
                'color' => $trackingStatus->color,
                'icon' => $trackingStatus->icon,
                'created_at' => $trackingStatus->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
