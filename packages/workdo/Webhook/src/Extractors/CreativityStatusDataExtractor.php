<?php

namespace Workdo\Webhook\Extractors;

class CreativityStatusDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->status)) return [];

        $status = $event->status;

        return [
            'creativity_status' => [
                'id' => $status->id,
                'name' => $status->name,
                'created_at' => $status->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
