<?php

namespace App\Observers;

use App\Services\AuditTrailService;
use Illuminate\Database\Eloquent\Model;

class ModelAuditTrailObserver
{
    public function __construct(private readonly AuditTrailService $auditTrailService)
    {
    }

    public function created(Model $model): void
    {
        $this->auditTrailService->record('created', $model);
    }

    public function updated(Model $model): void
    {
        $this->auditTrailService->record('updated', $model);
    }

    public function deleted(Model $model): void
    {
        $this->auditTrailService->record('deleted', $model);
    }
}
