<?php

namespace Workdo\Webhook\Extractors;

class SalesQuoteDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->quote)) return [];

        $quote = $event->quote;

        return [
            'sales_quote' => [
                'id' => $quote->id,
                'quote_number' => $quote->quote_number,
                'name' => $quote->name,
                'opportunity' => $quote->opportunity?->name ?? null,
                'account' => $quote->account?->name ?? null,
                'warehouse' => $quote->warehouse?->name ?? null,
                'status' => $quote->status,
                'date_quoted' => $quote->date_quoted,
                'expiry_date' => $quote->expiry_date,
                'billing_address' => $quote->billing_address,
                'shipping_address' => $quote->shipping_address,
                'billing_city' => $quote->billing_city,
                'billing_state' => $quote->billing_state,
                'shipping_city' => $quote->shipping_city,
                'shipping_state' => $quote->shipping_state,
                'billing_country' => $quote->billing_country,
                'billing_postal_code' => $quote->billing_postal_code,
                'shipping_country' => $quote->shipping_country,
                'shipping_postal_code' => $quote->shipping_postal_code,
                'billing_contact' => $quote->billingContact?->name ?? null,
                'shipping_contact' => $quote->shippingContact?->name ?? null,
                'shipping_provider' => $quote->shippingProvider?->name ?? null,
                'assign_user' => $quote->assignUser?->name ?? null,
                'description' => $quote->description,
                'notes' => $quote->notes,
                'subtotal' => $quote->subtotal,
                'tax_amount' => $quote->tax_amount,
                'discount_amount' => $quote->discount_amount,
                'total_amount' => $quote->total_amount,
                'created_at' => $quote->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
