<?php

namespace Workdo\Webhook\Extractors;

class DocumentTypeDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->documentType)) return [];

        $documentType = $event->documentType;

        return [
            'document_type' => [
                'id' => $documentType->id,
                'name' => $documentType->name,
                'created_at' => $documentType->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
