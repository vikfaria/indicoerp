<?php

namespace Workdo\Webhook\Extractors;

class ChallengeCategoryDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->category)) return [];

        $category = $event->category;

        return [
            'challenge_category' => [
                'id' => $category->id,
                'name' => $category->name,
                'created_at' => $category->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
