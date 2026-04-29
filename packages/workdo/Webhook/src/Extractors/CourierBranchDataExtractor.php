<?php

namespace Workdo\Webhook\Extractors;

class CourierBranchDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->branch)) return [];

        $branch = $event->branch;

        return [
            'courier_branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'location' => $branch->location,
                'city' => $branch->city,
                'state' => $branch->state,
                'country' => $branch->country,
                'created_at' => $branch->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
