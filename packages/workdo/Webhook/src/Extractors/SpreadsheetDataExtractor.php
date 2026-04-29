<?php

namespace Workdo\Webhook\Extractors;

class SpreadsheetDataExtractor
{
    public static function extract($event)
    {
        if (!isset($event->spreadsheet)) return [];

        $spreadsheet = $event->spreadsheet;

        return [
            'spreadsheet' => [
                'id' => $spreadsheet->id,
                'name' => $spreadsheet->name ?? null,
                'path' => $spreadsheet->path ?? null,
                'size' => $spreadsheet->size ?? null,
                'type' => $spreadsheet->type ?? null,
                'created_at' => $spreadsheet->created_at?->format('d-m-Y H:i') ?? null
            ]
        ];
    }
}
