<?php

namespace Workdo\Webhook\Extractors;

class RepairProductPartDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->repairPart)) return [];

        $repairPart = $event->repairPart;

        return [
            'repair_product_part' => [
                'id' => $repairPart->id,
                'repair_id' => $repairPart->repairOrderRequest?->product_name ?? null,
                'product_id' => $repairPart->product?->name ?? null,
                'quantity' => $repairPart->quantity,
                'tax' => $repairPart->tax,
                'discount' => $repairPart->discount,
                'price' => $repairPart->price,
                'description' => $repairPart->description,
                'created_at' => $repairPart->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
