<?php

namespace Workdo\Webhook\Extractors;

class RepairInvoiceDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->repairInvoice)) return [];

        $repairInvoice = $event->repairInvoice;

        return [
            'repair_invoice' => [
                'id' => $repairInvoice->id,
                'invoice_id' => $repairInvoice->invoice_id,
                'repair_product_name' => $repairInvoice->repair_order?->product_name ?? null,
                'repair_charge' => $repairInvoice->repair_charge,
                'total_amount' => $repairInvoice->total_amount,
                'created_at' => $repairInvoice->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
