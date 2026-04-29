<?php

namespace Workdo\Webhook\Extractors;

class ContractTemplateDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->template)) return [];

        $template = $event->template;

        return [
            'contract_template' => [
                'id' => $template->id,
                'subject' => $template->subject,
                'user' => $template->user?->name ?? null,
                'value' => $template->value,
                'type' => $template->contractType?->name ?? null,
                'start_date' => $template->start_date,
                'end_date' => $template->end_date,
                'description' => $template->description,
                'status' => $template->status,
                'source_type' => $template->source_type,
                'template_number' => $template->template_number,
                'created_at' => $template->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
