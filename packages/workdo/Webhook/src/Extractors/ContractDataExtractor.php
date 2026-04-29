<?php

namespace Workdo\Webhook\Extractors;

class ContractDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->contract)) return [];

        $contract = $event->contract;

        return [
            'contract' => [
                'id' => $contract->id,
                'subject' => $contract->subject,
                'user' => $contract->user?->name ?? null,
                'value' => $contract->value,
                'contract_type' => $contract->contractType?->name ?? null,
                'start_date' => $contract->start_date,
                'end_date' => $contract->end_date,
                'description' => $contract->description,
                'status' => $contract->status,
                'source_type' => $contract->source_type,
                'contract_number' => $contract->contract_number,
                'created_at' => $contract->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
