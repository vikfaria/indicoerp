<?php

namespace Workdo\Webhook\Extractors;

class WoocommerceProductDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->wooProduct)) return [];

        $wooProduct = $event->wooProduct;

        // Extract category names from categories array
        $categoryNames = [];
        if (isset($wooProduct['categories']) && is_array($wooProduct['categories'])) {
            foreach ($wooProduct['categories'] as $category) {
                if (isset($category['name'])) {
                    $categoryNames[] = $category['name'];
                }
            }
        }
        $categories = !empty($categoryNames) ? implode(', ', $categoryNames) : null;

        return [
            'woocommerce_product' => [
                'id' => $wooProduct['id'] ?? null,
                'name' => $wooProduct['name'] ?? null,
                'sku' => $wooProduct['sku'] ?? null,
                'price' => $wooProduct['price'] ?? null,
                'regular_price' => $wooProduct['regular_price'] ?? null,
                'product_category' => $categories,
                'stock_quantity' => $wooProduct['stock_quantity'] ?? null,
                'date_created' => $wooProduct['date_created'] ?? null,
            ]
        ];
    }
}
