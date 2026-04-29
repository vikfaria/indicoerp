<?php

namespace Workdo\Webhook\Extractors;

class WoocommerceTaxDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->wooTax)) return [];

        $wooTax = $event->wooTax;

        return [
            'woocommerce_tax' => [
                'id' => $wooTax['id'] ?? null,
                'country' => $wooTax['country'] ?? null,
                'state' => $wooTax['state'] ?? null,
                'postcode' => $wooTax['postcode'] ?? null,
                'city' => $wooTax['city'] ?? null,
                'rate' => $wooTax['rate'] ?? null,
                'name' => $wooTax['name'] ?? null,
                'priority' => $wooTax['priority'] ?? null,
            ]
        ];
    }
}
