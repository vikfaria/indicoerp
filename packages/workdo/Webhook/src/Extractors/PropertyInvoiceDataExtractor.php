<?php

namespace Workdo\Webhook\Extractors;

class PropertyInvoiceDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->invoice)) return [];

        $invoice = $event->invoice;

        return [
            'agriculture_invoice' => [
                'id' => $invoice->id,
                'property' => $invoice->property ? $invoice->property->name : null,
                'unit' => $invoice->unit ? $invoice->unit->unit_name : null,
                'issue_date' => $invoice->issue_date ?? null,
                'status' => $invoice->status ?? null,
                'total_amount' => $invoice->total_amount ?? null,
                'created_at' => $invoice->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
