<?php

namespace Workdo\Webhook\Extractors;

class AgricultureFarmerDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->agriculturefarmer)) return [];

        $agriculturefarmer = $event->agriculturefarmer;

        return [
            'agriculture_farmer' => [
                'id' => $agriculturefarmer->id,
                'total_area' => $agriculturefarmer->total_area ?? null,
                'cultivate_area' => $agriculturefarmer->cultivate_area ?? null,
                'user' => $agriculturefarmer->user ? $agriculturefarmer->user->name : null,
                'agriculture_department' => $agriculturefarmer->agriculture_department ? $agriculturefarmer->agriculture_department->name : null,
                'agriculture_office' => $agriculturefarmer->agriculture_office ? $agriculturefarmer->agriculture_office->name : null,
                'farmer_code' => $agriculturefarmer->farmer_code ?? null,
                'created_at' => $agriculturefarmer->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
