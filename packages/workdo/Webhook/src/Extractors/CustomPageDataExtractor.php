<?php

namespace Workdo\Webhook\Extractors;

class CustomPageDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->customPage)) return [];

        $customPage = $event->customPage;
        $status = $customPage->status == 'true' ? 'Active' : 'Inactive';

        return [
            'custom_page' => [
                'id' => $customPage->id,
                'title' => $customPage->title,
                'slug' => $customPage->slug,
                'meta_title' => $customPage->meta_title,
                'meta_description' => $customPage->meta_description,
                'status' => $status,
                'sort_order' => $customPage->sort_order,
                'created_at' => $customPage->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
