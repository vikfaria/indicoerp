<?php

namespace Workdo\Webhook\Extractors;

class WarehouseDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->warehouse)) return [];

        $warehouse = $event->warehouse;
        $status = $warehouse->is_active == 'true' ? 'Active' : 'Inactive';

        return [
            'warehouse' => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'address' => $warehouse->address,
                'city' => $warehouse->city,
                'zip_code' => $warehouse->zip_code,
                'phone' => $warehouse->phone,
                'email' => $warehouse->email,
                'status' => $status,
                'created_at' => $warehouse->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
