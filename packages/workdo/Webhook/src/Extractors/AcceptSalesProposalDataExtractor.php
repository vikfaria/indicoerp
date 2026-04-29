<?php

namespace Workdo\Webhook\Extractors;

class AcceptSalesProposalDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->salesProposal)) return [];

        $salesProposal = $event->salesProposal;

        return [
            'sales_proposal_status' => [
                'id' => $salesProposal->id,
                'proposal_number' => $salesProposal->proposal_number,
                'proposal_date' => $salesProposal->proposal_date,
                'due_date' => $salesProposal->due_date,
                'customer' => $salesProposal->customer?->name ?? null,
                'warehouse' => $salesProposal->warehouse?->name ?? null,
                'subtotal' => $salesProposal->subtotal,
                'tax_amount' => $salesProposal->tax_amount,
                'discount_amount' => $salesProposal->discount_amount,
                'total_amount' => $salesProposal->total_amount,
                'status' => $salesProposal->status,
                'created_at' => $salesProposal->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
