<?php

namespace Workdo\Webhook\Extractors;

class WoocommerceCategoryDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->wooCategory)) return [];

        $wooCategory = $event->wooCategory;

        return [
            'woocommerce_category' => [
                'id' => $wooCategory['id'] ?? null,
                'name' => $wooCategory['name'] ?? null,
                'slug' => $wooCategory['slug'] ?? null,
                'description' => $wooCategory['description'] ?? null,
            ]
        ];
    }
}
