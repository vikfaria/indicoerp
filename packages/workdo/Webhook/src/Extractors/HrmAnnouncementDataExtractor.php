<?php

namespace Workdo\Webhook\Extractors;

class HrmAnnouncementDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->announcement)) return [];

        $announcement = $event->announcement;

        return [
            'announcement' => [
                'id' => $announcement->id,
                'title' => $announcement->title ?? null,
                'description' => $announcement->description ?? null,
                'start_date' => $announcement->start_date ?? null,
                'end_date' => $announcement->end_date ?? null,
                'priority' => $announcement->priority ?? null,
                'status' => $announcement->status ?? null,
                'announcement_category' => $announcement->announcementCategory ? $announcement->announcementCategory->announcement_category : null,
                'created_at' => $announcement->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
