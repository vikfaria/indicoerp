<?php

namespace App\Models\Concerns;

use Illuminate\Support\Carbon;

trait BuildsCompanyDocumentNumber
{
    protected static function generateCompanyDocumentNumber(
        string $column,
        string $prefixSettingKey,
        string $defaultPrefix,
        ?int $createdBy = null,
        mixed $documentDate = null,
        ?string $seriesSettingKey = null,
        ?int $establishmentId = null
    ): string {
        $resolvedCreatedBy = $createdBy ?: (auth()->check() ? creatorId() : null);
        $prefix = static::resolveCompanyDocumentPrefix($prefixSettingKey, $defaultPrefix, $resolvedCreatedBy);
        $series = static::resolveCompanyDocumentSeries($seriesSettingKey, $resolvedCreatedBy, $establishmentId);
        $date = $documentDate ? Carbon::parse($documentDate) : now();

        $pattern = $series !== null
            ? sprintf('%s-%s-%s-%s-%%', $prefix, $series, $date->format('Y'), $date->format('m'))
            : sprintf('%s-%s-%s-%%', $prefix, $date->format('Y'), $date->format('m'));

        $query = static::query()->where($column, 'like', $pattern);

        if ($resolvedCreatedBy) {
            $query->where('created_by', $resolvedCreatedBy);
        }

        $lastDocument = $query->orderBy($column, 'desc')->first();
        $nextNumber = $lastDocument ? ((int) substr((string) $lastDocument->{$column}, -3)) + 1 : 1;

        if ($series !== null) {
            return sprintf('%s-%s-%s-%s-%03d', $prefix, $series, $date->format('Y'), $date->format('m'), $nextNumber);
        }

        return sprintf('%s-%s-%s-%03d', $prefix, $date->format('Y'), $date->format('m'), $nextNumber);
    }

    protected static function resolveCompanyDocumentPrefix(string $settingKey, string $defaultPrefix, ?int $createdBy = null): string
    {
        $configuredPrefix = $createdBy ? company_setting($settingKey, $createdBy) : null;
        $prefix = strtoupper(trim((string) ($configuredPrefix ?: $defaultPrefix)));
        $prefix = preg_replace('/\s+/', '-', $prefix);
        $prefix = preg_replace('/[^A-Z0-9\/-]/', '', $prefix);
        $prefix = trim((string) $prefix, "-/");

        return $prefix !== '' ? $prefix : strtoupper($defaultPrefix);
    }

    protected static function resolveCompanyDocumentSeries(?string $settingKey, ?int $createdBy = null, ?int $establishmentId = null): ?string
    {
        if ($settingKey === null || $createdBy === null) {
            return null;
        }

        $candidateKeys = [];

        if ($establishmentId !== null) {
            $candidateKeys[] = sprintf('%s_warehouse_%d', $settingKey, $establishmentId);
        }

        $candidateKeys[] = $settingKey;

        foreach ($candidateKeys as $candidateKey) {
            $configuredSeries = company_setting($candidateKey, $createdBy);
            $series = strtoupper(trim((string) $configuredSeries));
            $series = preg_replace('/\s+/', '-', $series);
            $series = preg_replace('/[^A-Z0-9\/-]/', '', $series);
            $series = trim((string) $series, "-/");

            if ($series !== '') {
                return $series;
            }
        }

        return null;
    }

    protected static function extractDocumentSequenceFromNumber(?string $number): ?int
    {
        if ($number === null || $number === '') {
            return null;
        }

        if (!preg_match('/(\d{1,})$/', $number, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }
}
