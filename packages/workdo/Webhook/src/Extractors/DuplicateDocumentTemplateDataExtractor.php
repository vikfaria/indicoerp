<?php

namespace Workdo\Webhook\Extractors;

class DuplicateDocumentTemplateDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->newTemplate)) return [];

        $newTemplate = $event->newTemplate;

        return [
            'new_template' => [
                'id' => $newTemplate->id,
                'template_number' => $newTemplate->template_number,
                'source_type' => $newTemplate->source_type,
                'subject' => $newTemplate->subject,
                'document_type' => $newTemplate->documentType?->name ?? null,
                'document_notes' => $newTemplate->document_notes,
                'status' => $newTemplate->status,
                'created_at' => $newTemplate->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
