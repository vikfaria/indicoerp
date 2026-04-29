<?php

namespace Workdo\Webhook\Extractors;

class SalesProposalDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->proposal)) return [];

        $proposal = $event->proposal;

        return [
            'proposal' => [
                'id' => $proposal->id,
                'proposal_date' => $proposal->proposal_date,
                'due_date' => $proposal->due_date,
                'customer' => $proposal->customer?->name ?? null,
                'warehouse' => $proposal->warehouse?->name ?? null,
                'subtotal' => $proposal->subtotal,
                'tax_amount' => $proposal->tax_amount,
                'discount_amount' => $proposal->discount_amount,
                'total_amount' => $proposal->total_amount,
                'proposal_number' => $proposal->proposal_number,
                'created_at' => $proposal->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
