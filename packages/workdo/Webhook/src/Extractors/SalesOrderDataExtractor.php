<?php

namespace Workdo\Webhook\Extractors;

class SalesOrderDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->salesOrder)) return [];

        $salesOrder = $event->salesOrder;

        return [
            'sales_order' => [
                'id' => $salesOrder->id,
                'order_number' => $salesOrder->order_number,
                'name' => $salesOrder->name,
                'quote' => $salesOrder->quote?->name ?? null,
                'opportunity' => $salesOrder->opportunity?->name ?? null,
                'account' => $salesOrder->account?->name ?? null,
                'customer' => $salesOrder->customer?->name ?? null,
                'warehouse' => $salesOrder->warehouse?->name ?? null,
                'status' => $salesOrder->status,
                'order_date' => $salesOrder->order_date,
                'billing_address' => $salesOrder->billing_address,
                'shipping_address' => $salesOrder->shipping_address,
                'billing_city' => $salesOrder->billing_city,
                'billing_state' => $salesOrder->billing_state,
                'shipping_city' => $salesOrder->shipping_city,
                'shipping_state' => $salesOrder->shipping_state,
                'billing_country' => $salesOrder->billing_country,
                'billing_postal_code' => $salesOrder->billing_postal_code,
                'shipping_country' => $salesOrder->shipping_country,
                'shipping_postal_code' => $salesOrder->shipping_postal_code,
                'billing_contact' => $salesOrder->billingContact?->name ?? null,
                'shipping_contact' => $salesOrder->shippingContact?->name ?? null,
                'shipping_provider' => $salesOrder->shippingProvider?->name ?? null,
                'assign_user' => $salesOrder->assignUser?->name ?? null,
                'description' => $salesOrder->description,
                'notes' => $salesOrder->notes,
                'subtotal' => $salesOrder->subtotal,
                'tax_amount' => $salesOrder->tax_amount,
                'discount_amount' => $salesOrder->discount_amount,
                'total_amount' => $salesOrder->total_amount,
                'created_at' => $salesOrder->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
