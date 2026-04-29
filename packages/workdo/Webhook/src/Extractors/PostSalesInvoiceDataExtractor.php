<?php

namespace Workdo\Webhook\Extractors;

class PostSalesInvoiceDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->salesInvoice)) return [];

        $salesInvoice = $event->salesInvoice;

        return [
            'post_sales_invoice' => [
                'id' => $salesInvoice->id,
                'invoice_number' => $salesInvoice->invoice_number,
                'invoice_date' => $salesInvoice->invoice_date,
                'due_date' => $salesInvoice->due_date,
                'customer' => $salesInvoice->customer?->name ?? null,
                'warehouse' => $salesInvoice->warehouse?->name ?? null,
                'subtotal' => $salesInvoice->subtotal,
                'tax_amount' => $salesInvoice->tax_amount,
                'discount_amount' => $salesInvoice->discount_amount,
                'total_amount' => $salesInvoice->total_amount,
                'paid_amount' => $salesInvoice->paid_amount,
                'balance_amount' => $salesInvoice->balance_amount,
                'status' => $salesInvoice->status,
                'created_at' => $salesInvoice->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
