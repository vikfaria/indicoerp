<?php

namespace Workdo\Webhook\Extractors;

class CourseDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->course)) return [];

        $course = $event->course;

        return [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'price' => $course->price,
                'category' => $course->category?->name ?? null,
                'level' => $course->level,
                'language' => $course->language,
                'duration' => $course->duration,
                'discount' => $course->discount,
                'preview_video' => $course->preview_video,
                'sort_order' => $course->sort_order,
                'slug' => $course->slug,
                'created_at' => $course->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
