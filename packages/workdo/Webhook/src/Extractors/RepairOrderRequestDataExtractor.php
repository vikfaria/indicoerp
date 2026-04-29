<?php

namespace Workdo\Webhook\Extractors;

class RepairOrderRequestDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->repairOrderRequest)) return [];

        $repairOrderRequest = $event->repairOrderRequest;

        return [
            'repair_order_request' => [
                'id' => $repairOrderRequest->id,
                'product_name' => $repairOrderRequest->product_name,
                'product_quantity' => $repairOrderRequest->product_quantity,
                'customer_name' => $repairOrderRequest->customer_name,
                'customer_email' => $repairOrderRequest->customer_email,
                'customer_mobile_no' => $repairOrderRequest->customer_mobile_no,
                'date' => $repairOrderRequest->date,
                'expiry_date' => $repairOrderRequest->expiry_date,
                'repair_technician' => $repairOrderRequest->repairTechnician?->name ?? null,
                'location' => $repairOrderRequest->location,
                'created_at' => $repairOrderRequest->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
