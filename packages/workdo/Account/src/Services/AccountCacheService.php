<?php

namespace Workdo\Account\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AccountCacheService
{
    private const VERSION_PREFIX = 'account:cache:version:company:';

    public static function currentVersion(?int $companyId): int
    {
        if (empty($companyId) || $companyId <= 0) {
            return 1;
        }

        return max(1, (int) Cache::get(self::versionKey($companyId), 1));
    }

    public static function bumpForCompany(?int $companyId): void
    {
        if (empty($companyId) || $companyId <= 0) {
            return;
        }

        $key = self::versionKey($companyId);
        $nextVersion = self::currentVersion($companyId) + 1;
        Cache::forever($key, $nextVersion);
    }

    public static function bumpForModel(Model $model): void
    {
        self::bumpForCompany(self::resolveCompanyIdFromModel($model));
    }

    public static function resolveCompanyIdFromModel(Model $model): ?int
    {
        $candidates = [
            $model->getAttribute('created_by'),
            $model->getOriginal('created_by'),
            $model->getAttribute('company_id'),
            $model->getOriginal('company_id'),
            $model->getAttribute('creator_id'),
            $model->getOriginal('creator_id'),
        ];

        foreach ($candidates as $candidate) {
            $value = (int) $candidate;
            if ($value > 0) {
                return $value;
            }
        }

        return null;
    }

    private static function versionKey(int $companyId): string
    {
        return self::VERSION_PREFIX . $companyId;
    }
}

