<?php

namespace Workdo\Webhook\Extractors;

class SalesInvoiceDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->salesInvoice)) return [];

        $salesInvoice = $event->salesInvoice;

        return [
            'sales_invoice' => [
                'id' => $salesInvoice->id,
                'invoice_date' => $salesInvoice->invoice_date?->format('d-m-Y') ?? null,
                'due_date' => $salesInvoice->due_date?->format('d-m-Y') ?? null,
                'customer' => $salesInvoice->customer?->name ?? null,
                'warehouse' => $salesInvoice->warehouse?->name ?? null,
                'subtotal' => $salesInvoice->subtotal,
                'tax_amount' => $salesInvoice->tax_amount,
                'discount_amount' => $salesInvoice->discount_amount,
                'total_amount' => $salesInvoice->total_amount,
                'balance_amount' => $salesInvoice->balance_amount,
                'invoice_number' => $salesInvoice->invoice_number,
                'created_at' => $salesInvoice->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
