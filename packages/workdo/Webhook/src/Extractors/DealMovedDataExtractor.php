<?php

namespace Workdo\Webhook\Extractors;

class DealMovedDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->deal)) return [];

        $deal = $event->deal;

        return [
            'deal_moved' => [
                'id' => $deal->id,
                'name' => $deal->name,
                'price' => $deal->price,
                'pipeline' => $deal->pipeline?->name ?? null,
                'stage' => $deal->stage?->name ?? null,
                'status' => $deal->status,
                'phone' => $deal->phone,
                'created_at' => $deal->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
