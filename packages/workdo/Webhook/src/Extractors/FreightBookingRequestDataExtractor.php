<?php

namespace Workdo\Webhook\Extractors;

class FreightBookingRequestDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->freightBookingRequest)) return [];

        $freightBookingRequest = $event->freightBookingRequest;

        return [
            'freight_booking_request' => [
                'id' => $freightBookingRequest->id,
                'user' => $freightBookingRequest->user?->name ?? null,
                'customer_name' => $freightBookingRequest->customer_name,
                'customer_email' => $freightBookingRequest->customer_email,
                'loading_port' => $freightBookingRequest->loading_port,
                'discharge_port' => $freightBookingRequest->discharge_port,
                'vessel' => $freightBookingRequest->vessel,
                'date' => $freightBookingRequest->date,
                'barcode' => $freightBookingRequest->barcode,
                'tracking_no' => $freightBookingRequest->tracking_no,
                'created_at' => $freightBookingRequest->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
