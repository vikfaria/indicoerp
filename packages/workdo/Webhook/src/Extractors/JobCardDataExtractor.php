<?php

namespace Workdo\Webhook\Extractors;

class JobCardDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->jobCard)) return [];

        $jobCard = $event->jobCard;

        return [
            'job_card' => [
                'id' => $jobCard->id,
                'job_card_number' => $jobCard->job_card_number,
                'status' => $jobCard->status,
                'inspection_notes' => $jobCard->inspection_notes,
                'service' => $jobCard->service?->title ?? null,
                'vehicle_type' => $jobCard->vehicleType?->name ?? null,
                'repair_category' => $jobCard->repairCategory?->name ?? null,
                'assign_to' => $jobCard->assignTo?->name ?? null,
                'created_at' => $jobCard->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
