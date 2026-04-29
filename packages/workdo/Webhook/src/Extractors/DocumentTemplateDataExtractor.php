<?php

namespace Workdo\Webhook\Extractors;

class DocumentTemplateDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->template)) return [];

        $template = $event->template;

        return [
            'document_template' => [
                'id' => $template->id,
                'subject' => $template->subject,
                'document_type' => $template->documentType?->name ?? null,
                'document_notes' => $template->document_notes,
                'status' => $template->status,
                'template_number' => $template->template_number,
                'source_type' => $template->source_type,
                'created_at' => $template->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
