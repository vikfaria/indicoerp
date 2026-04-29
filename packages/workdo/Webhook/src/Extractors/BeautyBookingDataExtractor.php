<?php

namespace Workdo\Webhook\Extractors;

class BeautyBookingDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->beautybooking)) return [];

        $beautybooking = $event->beautybooking;

        return [
            'beauty_booking' => [
                'id' => $beautybooking->id,
                'name' => $beautybooking->name ?? null,
                'email' => $beautybooking->email ?? null,
                'service' => $beautybooking->beautyService ? $beautybooking->beautyService->name : null,
                'date' => $beautybooking->date ?? null,
                'start_time' => $beautybooking->start_time ?? null,
                'end_time' => $beautybooking->end_time ?? null,
                'person' => $beautybooking->person ?? null,
                'price' => $beautybooking->price ?? null,
                'phone_number' => $beautybooking->phone_number ?? null,
                'gender' => $beautybooking->gender ?? null,
                'reference' => $beautybooking->reference ?? null,
                'notes' => $beautybooking->notes ?? null,
                'season' => $beautybooking->season ? $beautybooking->season->name : null,
                'created_at' => $beautybooking->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
