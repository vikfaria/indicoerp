<?php

namespace Workdo\Webhook\Extractors;

class ConvertDocumentToTemplateDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->template) && !isset($event->document)) return [];

        $template = $event->template;
        $document = $event->document;

        return [
            'convert_document_to_template' => [
                'document_id' => $document->id,
                'template_id' => $template->id,
                'document_number' => $document->document_number,
                'template_number' => $template->template_number,
                'source_type' => $document->source_type,
                'subject' => $document->subject,
                'document_type' => $document->documentType?->name ?? null,
                'document_notes' => $document->document_notes,
                'status' => $document->status,
                'created_at' => $template->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
