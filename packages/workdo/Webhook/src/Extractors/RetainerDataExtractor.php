<?php

namespace Workdo\Webhook\Extractors;

class RetainerDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->retainer)) return [];

        $retainer = $event->retainer;

        return [
            'retainer' => [
                'id' => $retainer->id,
                'retainer_date' => $retainer->retainer_date,
                'due_date' => $retainer->due_date,
                'customer' => $retainer->customer?->name ?? null,
                'warehouse' => $retainer->warehouse?->name ?? null,
                'subtotal' => $retainer->subtotal,
                'tax_amount' => $retainer->tax_amount,
                'discount_amount' => $retainer->discount_amount,
                'total_amount' => $retainer->total_amount,
                'balance_amount' => $retainer->balance_amount,
                'retainer_number' => $retainer->retainer_number,
                'created_at' => $retainer->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
