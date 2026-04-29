<?php

namespace Workdo\Webhook\Extractors;

class InsuranceInvoiceDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->insuranceInvoice)) return [];

        $insuranceInvoice = $event->insuranceInvoice;

        return [
            'insurance_invoice' => [
                'id' => $insuranceInvoice->id,
                'policy' => $insuranceInvoice->policy?->name ?? null,
                'client' => $insuranceInvoice->client?->name ?? null,
                'due_date' => $insuranceInvoice->due_date,
                'created_at' => $insuranceInvoice->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
