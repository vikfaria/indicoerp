<?php

namespace Workdo\Webhook\Extractors;

class VisitorDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->visitor)) return [];

        $visitor = $event->visitor;

        return [
            'visitor' => [
                'id' => $visitor->id,
                'name' => $visitor->name,
                'email' => $visitor->email,
                'phone' => $visitor->phone,
                'created_at' => $visitor->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
