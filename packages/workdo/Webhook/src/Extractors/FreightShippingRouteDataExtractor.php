<?php

namespace Workdo\Webhook\Extractors;

class FreightShippingRouteDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->freightShippingRoute)) return [];

        $freightShippingRoute = $event->freightShippingRoute;

        return [
            'freight_shipping_route' => [
                'id' => $freightShippingRoute->id,
                'shipping_customer_name' => $freightShippingRoute->shipping?->customer_name ?? null,
                'route_operation' => $freightShippingRoute->route_operation,
                'source_location' => $freightShippingRoute->source_location,
                'destination_location' => $freightShippingRoute->destination_location,
                'send_date' => $freightShippingRoute->send_date,
                'received_date' => $freightShippingRoute->received_date,
                'created_at' => $freightShippingRoute->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
