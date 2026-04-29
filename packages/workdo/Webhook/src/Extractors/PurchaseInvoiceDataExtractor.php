<?php

namespace Workdo\Webhook\Extractors;

class PurchaseInvoiceDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->purchaseInvoice)) return [];

        $purchaseInvoice = $event->purchaseInvoice;

        return [
            'purchase_invoice' => [
                'id' => $purchaseInvoice->id,
                'invoice_date' => $purchaseInvoice->invoice_date,
                'due_date' => $purchaseInvoice->due_date,
                'vendor' => $purchaseInvoice->vendor?->name ?? null,
                'warehouse' => $purchaseInvoice->warehouse?->name ?? null,
                'subtotal' => $purchaseInvoice->subtotal,
                'tax_amount' => $purchaseInvoice->tax_amount,
                'discount_amount' => $purchaseInvoice->discount_amount,
                'total_amount' => $purchaseInvoice->total_amount,
                'balance_amount' => $purchaseInvoice->balance_amount,
                'invoice_number' => $purchaseInvoice->invoice_number,
                'created_at' => $purchaseInvoice->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
