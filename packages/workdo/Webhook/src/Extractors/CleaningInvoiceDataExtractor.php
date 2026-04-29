<?php

namespace Workdo\Webhook\Extractors;

class CleaningInvoiceDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->cleaningInvoice)) return [];

        $cleaningInvoice = $event->cleaningInvoice;

        // Safe access to nested relationships
        $inspection = $cleaningInvoice->inspection ?? null;
        $booking = $inspection?->booking ?? null;
        $cleaningTeam = $booking?->cleaning_team ?? null;

        return [
            'cleaning_invoice' => [
                'id' => $cleaningInvoice->id,
                'customer_name' => $booking?->customer_name ?? null,
                'team_name' => $cleaningTeam?->name ?? null,
                'cleaning_date' => $booking?->cleaning_date?->format('d-m-Y') ?? null,
                'start_time' => $booking?->start_time ?? null,
                'end_time' => $booking?->end_time ?? null,
                'amount' => $cleaningInvoice->amount,
                'created_at' => $cleaningInvoice->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
