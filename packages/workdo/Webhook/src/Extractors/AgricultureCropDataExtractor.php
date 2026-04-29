<?php

namespace Workdo\Webhook\Extractors;

class AgricultureCropDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->agriculturecrop)) return [];

        $agriculturecrop = $event->agriculturecrop;

        return [
            'agriculture_crop' => [
                'id' => $agriculturecrop->id,
                'name' => $agriculturecrop->name ?? null,
                'quantity' => $agriculturecrop->quantity ?? null,
                'price' => $agriculturecrop->price ?? null,
                'unit' => $agriculturecrop->unit ?? null,
                'agriculture_date' => $agriculturecrop->agriculture_date ?? null,
                'harvest_date' => $agriculturecrop->harvest_date ?? null,
                'season' => $agriculturecrop->season ? $agriculturecrop->season->name : null,
                'cycle' => $agriculturecrop->cycle ? $agriculturecrop->cycle->name : null,
                'created_at' => $agriculturecrop->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
