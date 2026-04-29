<?php

namespace Workdo\Webhook\Extractors;

class WorkOrderDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->workOrder)) return [];

        $workOrder = $event->workOrder;
        
        // Map priority values to readable text
        $priorityMap = [
            '0' => 'High Priority',
            '1' => 'Medium',
            '2' => 'Low'
        ];
        $priority = $priorityMap[$workOrder->priority] ?? $workOrder->priority;

        return [
            'work_order' => [
                'id' => $workOrder->id,
                'workorder_name' => $workOrder->workorder_name,
                'priority' => $priority,
                'due_date' => $workOrder->due_date?->format('d-m-Y') ?? null,
                'time' => $workOrder->time,
                'instructions' => $workOrder->instructions,
                'location' => $workOrder->location?->name ?? null,
                'component' => $workOrder->component?->name ?? null,
                'work_status' => $workOrder->work_status,
                'created_at' => $workOrder->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
