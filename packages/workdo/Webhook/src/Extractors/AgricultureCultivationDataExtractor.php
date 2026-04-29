<?php

namespace Workdo\Webhook\Extractors;

class AgricultureCultivationDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->agriculturecultivation)) return [];

        $agriculturecultivation = $event->agriculturecultivation;

        return [
            'agriculture_cultivation' => [
                'id' => $agriculturecultivation->id,
                'name' => $agriculturecultivation->name ?? null,
                'farmer' => $agriculturecultivation->farmer ? $agriculturecultivation->farmer->name : null,
                'agriculture_cycle' => $agriculturecultivation->agriculture_cycle ? $agriculturecultivation->agriculture_cycle->name : null,
                'agriculture_department' => $agriculturecultivation->agriculture_department ? $agriculturecultivation->agriculture_department->name : null,
                'agriculture_office' => $agriculturecultivation->agriculture_office ? $agriculturecultivation->agriculture_office->name : null,
                'area' => $agriculturecultivation->area ?? null,
                'created_at' => $agriculturecultivation->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
