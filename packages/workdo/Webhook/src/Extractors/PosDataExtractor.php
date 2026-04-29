<?php

namespace Workdo\Webhook\Extractors;

class PosDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->pos)) return [];

        $pos = $event->pos;

        return [
            'pos' => [
                'id' => $pos->id,
                'pos_date' => $pos->pos_date?->format('d-m-Y') ?? null,
                'due_date' => $pos->due_date?->format('d-m-Y') ?? null,
                'supplier' => $pos->supplier?->name ?? null,
                'location' => $pos->location?->name ?? null,
                'user' => $pos->user?->name ?? null,
                'notes' => $pos->notes,
                'subtotal' => $pos->subtotal,
                'tax_amount' => $pos->tax_amount,
                'discount_amount' => $pos->discount_amount,
                'total_amount' => $pos->total_amount,
                'balance_amount' => $pos->balance_amount,
                'pos_number' => $pos->pos_number,
                'created_at' => $pos->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
