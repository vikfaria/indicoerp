<?php

namespace Workdo\Webhook\Extractors;

class CourierDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->courier)) return [];

        $courier = $event->courier;

        return [
            'courier' => [
                'id' => $courier->id,
                'sender_name' => $courier->sender_name,
                'sender_mobile' => $courier->sender_mobile,
                'sender_email' => $courier->sender_email,
                'receiver_name' => $courier->receiver_name,
                'receiver_mobile' => $courier->receiver_mobile,
                'receiver_address' => $courier->receiver_address,
                'package_title' => $courier->package_title,
                'tracking_id' => $courier->tracking_id,
                'price' => $courier->price,
                'weight' => $courier->weight,
                'height' => $courier->height,
                'width' => $courier->width,
                'expected_delivery_date' => $courier->expected_delivery_date,
                'package_description' => $courier->package_description,
                'service_type' => $courier->service_type?->name ?? null,
                'source_branch' => $courier->source_branch?->name ?? null,
                'destination_branch' => $courier->destination_branch?->name ?? null,
                'package_category' => $courier->package_category?->name ?? null,
                'tracking_status' => $courier->tracking_status?->name ?? null,
                'created_at' => $courier->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
