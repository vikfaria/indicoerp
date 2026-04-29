<?php

namespace Workdo\Webhook\Extractors;

class FixEquipmentStatusDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->fixequipmentstatus)) return [];

        $fixequipmentstatus = $event->fixequipmentstatus;

        return [
            'fixequipment_status' => [
                'id' => $fixequipmentstatus->id,
                'title' => $fixequipmentstatus->title,
                'color' => $fixequipmentstatus->color,
                'created_at' => $fixequipmentstatus->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
