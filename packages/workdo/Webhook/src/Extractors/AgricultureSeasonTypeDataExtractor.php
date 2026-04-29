<?php

namespace Workdo\Webhook\Extractors;

class AgricultureSeasonTypeDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->agricultureseasontype)) return [];

        $agricultureseasontype = $event->agricultureseasontype;

        return [
            'agriculture_season_type' => [
                'id' => $agricultureseasontype->id,
                'name' => $agricultureseasontype->name ?? null,
                'created_at' => $agricultureseasontype->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
