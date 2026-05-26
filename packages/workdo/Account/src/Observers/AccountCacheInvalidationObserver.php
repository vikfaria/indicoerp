<?php

namespace Workdo\Account\Observers;

use Illuminate\Database\Eloquent\Model;
use Workdo\Account\Services\AccountCacheService;

class AccountCacheInvalidationObserver
{
    public function saved(Model $model): void
    {
        AccountCacheService::bumpForModel($model);
    }

    public function deleted(Model $model): void
    {
        AccountCacheService::bumpForModel($model);
    }

    public function restored(Model $model): void
    {
        AccountCacheService::bumpForModel($model);
    }
}

