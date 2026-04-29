<?php

namespace Workdo\Webhook\Extractors;

class CourierPackageCategoryDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->packageCategory)) return [];

        $packageCategory = $event->packageCategory;

        return [
            'courier_package_category' => [
                'id' => $packageCategory->id,
                'name' => $packageCategory->name,
                'created_at' => $packageCategory->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
