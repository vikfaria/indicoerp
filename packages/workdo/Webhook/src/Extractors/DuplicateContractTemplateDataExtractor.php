<?php

namespace Workdo\Webhook\Extractors;

class DuplicateContractTemplateDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->newTemplate)) return [];

        $newTemplate = $event->newTemplate;

        return [
            'duplicate_contract_template' => [
                'id' => $newTemplate->id,
                'subject' => $newTemplate->subject,
                'user' => $newTemplate->user?->name ?? null,
                'value' => $newTemplate->value,
                'type' => $newTemplate->contractType?->name ?? null,
                'start_date' => $newTemplate->start_date,
                'end_date' => $newTemplate->end_date,
                'description' => $newTemplate->description,
                'status' => $newTemplate->status,
                'source_type' => $newTemplate->source_type,
                'template_number' => $newTemplate->template_number,
                'created_at' => $newTemplate->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
