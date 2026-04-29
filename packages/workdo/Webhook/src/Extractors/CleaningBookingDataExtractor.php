<?php

namespace Workdo\Webhook\Extractors;

class CleaningBookingDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->cleaningBooking)) return [];

        $cleaningBooking = $event->cleaningBooking;

        return [
            'cleaning_booking' => [
                'id' => $cleaningBooking->id,
                'type' => $cleaningBooking->type,
                'user' => $cleaningBooking->user?->name ?? null,
                'customer_name' => $cleaningBooking->customer_name ?? null,
                'phone' => $cleaningBooking->phone ?? null,
                'building_type' => $cleaningBooking->building_type,
                'building_unit' => $cleaningBooking->building_unit,
                'item_type' => $cleaningBooking->item_type,
                'address' => $cleaningBooking->address,
                'booking_date' => $cleaningBooking->booking_date,
                'cleaning_date' => $cleaningBooking->cleaning_date,
                'booking_request' => $cleaningBooking->booking_request,
                'description' => $cleaningBooking->description,
                'created_at' => $cleaningBooking->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
