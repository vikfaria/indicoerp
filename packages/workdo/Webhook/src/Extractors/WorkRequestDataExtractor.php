<?php

namespace Workdo\Webhook\Extractors;

class WorkRequestDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->workOrder)) return [];

        $workRequest = $event->workOrder;

        return [
            'work_request' => [
                'id' => $workRequest->id,
                'workorder_name' => $workRequest->workorder_name,
                'priority' => $workRequest->priority,
                'location' => $workRequest->location?->name ?? null,
                'component' => $workOrder->component?->name ?? null,
                'due_date' => $workRequest->pos_date?->format('d-m-Y') ?? null,
                'time' => $workRequest->time,
                'instructions' => $workRequest->instructions,
                'work_status' => $workRequest->work_status,
                'created_at' => $workRequest->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}


