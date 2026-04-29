<?php

namespace Workdo\Webhook\Extractors;

class FixEquipmentAuditDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->fixEquipmentAudit)) return [];

        $fixEquipmentAudit = $event->fixEquipmentAudit;

        return [
            'fixequipment_audit' => [
                'id' => $fixEquipmentAudit->id,
                'title' => $fixEquipmentAudit->title,
                'audit_date' => $fixEquipmentAudit->audit_date,
                'created_at' => $fixEquipmentAudit->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
