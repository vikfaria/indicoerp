<?php

namespace Workdo\Webhook\Extractors;

class AgricultureServiceProductDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->agricultureserviceproduct)) return [];

        $agricultureserviceproduct = $event->agricultureserviceproduct;

        return [
            'agriculture_service_product' => [
                'id' => $agricultureserviceproduct->id,
                'name' => $agricultureserviceproduct->name ?? null,
                'sales_price' => $agricultureserviceproduct->sales_price ?? null,
                'purchase_price' => $agricultureserviceproduct->purchase_price ?? null,
                'created_at' => $agricultureserviceproduct->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
