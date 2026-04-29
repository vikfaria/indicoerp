<?php

namespace Workdo\Webhook\Extractors;

class FreightShippingServiceDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->freightShippingService)) return [];

        $freightShippingService = $event->freightShippingService;

        return [
            'freight_shipping_service' => [
                'id' => $freightShippingService->id,
                'shipping_customer_name' => $freightShippingService->shipping?->customer_name ?? null,
                'vendor' => $freightShippingService->vendorUser?->name ?? null,
                'service' => $freightShippingService->serviceDetail?->name ?? null,
                'qty' => $freightShippingService->qty,
                'sale_price' => $freightShippingService->sale_price,
                'cost_price' => $freightShippingService->cost_price,
                'created_at' => $freightShippingService->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
