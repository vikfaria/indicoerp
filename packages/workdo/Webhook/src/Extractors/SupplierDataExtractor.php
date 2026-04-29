<?php

namespace Workdo\Webhook\Extractors;

class SupplierDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->supplier)) return [];

        $supplier = $event->supplier;

        return [
            'supplier' => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'contact' => $supplier->contact,
                'email' => $supplier->email,
                'address' => $supplier->address,
                'location' => $supplier->location?->name ?? null,
                'created_at' => $supplier->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
