<?php

namespace Workdo\Webhook\Extractors;

class DealDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->deal)) return [];

        $deal = $event->deal;

        return [
            'deal' => [
                'id' => $deal->id,
                'name' => $deal->name,
                'price' => $deal->price,
                'pipeline' => $deal->pipeline?->name ?? null,
                'stage' => $deal->stage?->name ?? null,
                'phone' => $deal->phone,
                'status' => $deal->status,
                'created_at' => $deal->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
