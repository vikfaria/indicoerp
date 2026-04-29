<?php

namespace Workdo\Webhook\Extractors;

class MachineDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->machine)) return [];

        $machine = $event->machine;

        return [
            'machine' => [
                'id' => $machine->id,
                'machine_name' => $machine->machine_name,
                'manufacturer' => $machine->manufacturer,
                'model' => $machine->model,
                'installation_date' => $machine->installation_date,
                'description' => $machine->description,
                'created_at' => $machine->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
