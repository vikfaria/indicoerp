<?php

namespace Workdo\Webhook\Extractors;

class FreightShippingOrderDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->freightShippingOrder)) return [];

        $freightShippingOrder = $event->freightShippingOrder;

        return [
            'freight_shipping_order' => [
                'id' => $freightShippingOrder->id,
                'shipping_customer_name' => $freightShippingOrder->shipping?->customer_name ?? null,
                'container' => $freightShippingOrder->container?->name ?? null,
                'pricing' => $freightShippingOrder->pricing?->name ?? null,
                'description' => $freightShippingOrder->description,
                'bill_on' => $freightShippingOrder->bill_on,
                'weight' => $freightShippingOrder->weight,
                'volume' => $freightShippingOrder->volume,
                'price' => $freightShippingOrder->price,
                'sale_price' => $freightShippingOrder->sale_price,
                'created_at' => $freightShippingOrder->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
