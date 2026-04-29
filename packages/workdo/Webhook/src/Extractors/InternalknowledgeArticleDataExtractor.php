<?php

namespace Workdo\Webhook\Extractors;

class InternalknowledgeArticleDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->internalknowledgeArticle)) return [];

        $internalknowledgeArticle = $event->internalknowledgeArticle;

        return [
            'internalknowledge_article' => [
                'id' => $internalknowledgeArticle->id,
                'title' => $internalknowledgeArticle->title,
                'type' => $internalknowledgeArticle->type,
                'internalknowledge_book' => $internalknowledgeArticle->internalknowledge_book?->title ?? null,
                'slug' => $internalknowledgeArticle->slug,
                'created_at' => $internalknowledgeArticle->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
