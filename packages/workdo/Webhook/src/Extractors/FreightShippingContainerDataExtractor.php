<?php

namespace Workdo\Webhook\Extractors;

class FreightShippingContainerDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->freightShippingRequest)) return [];

        $freightShippingRequest = $event->freightShippingRequest;

        return [
            'freight_shipping_container' => [
                'id' => $freightShippingRequest->id,
                'customer_name' => $freightShippingRequest->customer_name,
                'customer_email' => $freightShippingRequest->customer_email,
                'loading_port' => $freightShippingRequest->loading_port,
                'discharge_port' => $freightShippingRequest->discharge_port,
                'vessel' => $freightShippingRequest->vessel,
                'date' => $freightShippingRequest->date,
                'barcode' => $freightShippingRequest->barcode,
                'tracking_no' => $freightShippingRequest->tracking_no,
                'quantity' => $freightShippingRequest->quantity,
                'volume' => $freightShippingRequest->volume,
                'created_at' => $freightShippingRequest->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
