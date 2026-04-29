<?php

namespace Workdo\Webhook\Extractors;

class SalesMeetingDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->meeting)) return [];

        $meeting = $event->meeting;

        return [
            'sales_meeting' => [
                'id' => $meeting->id,
                'name' => $meeting->name,
                'status' => $meeting->status,
                'meeting_type' => $meeting->meeting_type,
                'start_date' => $meeting->start_date,
                'end_date' => $meeting->end_date,
                'parent_type' => $meeting->parent_type,
                'account' => $meeting->account?->name ?? null,
                'assigned_user' => $meeting->assignedUser?->name ?? null,
                'description' => $meeting->description,
                'created_at' => $meeting->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
