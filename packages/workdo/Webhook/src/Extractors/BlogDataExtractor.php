<?php

namespace Workdo\Webhook\Extractors;

class BlogDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->blog)) return [];

        $blog = $event->blog;

        return [
            'blog' => [
                'id' => $blog->id,
                'title' => $blog->title,
                'slug' => $blog->slug,
                'excerpt' => $blog->excerpt,
                'meta_title' => $blog->meta_title,
                'meta_description' => $blog->meta_description,
                'published_at' => $blog->published_at,
                'sort_order' => $blog->sort_order,
                'created_at' => $blog->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
