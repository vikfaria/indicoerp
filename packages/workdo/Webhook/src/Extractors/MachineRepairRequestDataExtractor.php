<?php

namespace Workdo\Webhook\Extractors;

class MachineRepairRequestDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->machinerepairrequest)) return [];

        $machinerepairrequest = $event->machinerepairrequest;

        return [
            'machine_repair_request' => [
                'id' => $machinerepairrequest->id,
                'machine' => $machinerepairrequest->machine?->machine_name ?? null,
                'customer_name' => $machinerepairrequest->customer_name,
                'customer_email' => $machinerepairrequest->customer_email,
                'date_of_request' => $machinerepairrequest->date_of_request,
                'description_of_issue' => $machinerepairrequest->description_of_issue,
                'staff' => $machinerepairrequest->staff?->name ?? null,
                'status' => $machinerepairrequest->status,
                'created_at' => $machinerepairrequest->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
