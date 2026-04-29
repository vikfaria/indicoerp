<?php

namespace Workdo\Webhook\Extractors;

class DocumentDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->document)) return [];

        $document = $event->document;

        return [
            'document' => [
                'id' => $document->id,
                'subject' => $document->subject,
                'document_type' => $document->documentType?->name ?? null,
                'document_notes' => $document->document_notes,
                'status' => $document->email,
                'document_number' => $document->document_number,
                'source_type' => $document->source_type,
                'created_at' => $document->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
