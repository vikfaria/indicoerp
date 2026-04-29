<?php

namespace Workdo\Webhook\Extractors;

class FreightInvoiceDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->freightInvoice)) return [];

        $freightInvoice = $event->freightInvoice;

        return [
            'freight_invoice' => [
                'id' => $freightInvoice->id,
                'shipping_customer_name' => $freightInvoice->shippingRequest?->customer_name ?? null,
                'amount' => $freightInvoice->amount,
                'customer' => $freightInvoice->customer?->name ?? null,
                'invoice_date' => $freightInvoice->invoice_date,
                'due_date' => $freightInvoice->due_date,
                'created_at' => $freightInvoice->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
