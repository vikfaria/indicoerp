<?php

namespace App\Providers;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceReturn;
use App\Models\SalesProposal;
use App\Models\Transfer;
use App\Observers\ModelAuditTrailObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AuditTrailServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach ($this->auditedModels() as $modelClass) {
            if (! class_exists($modelClass)) {
                continue;
            }

            if (! is_subclass_of($modelClass, Model::class)) {
                continue;
            }

            $modelClass::observe(ModelAuditTrailObserver::class);
        }
    }

    /**
     * @return array<int, class-string<Model>>
     */
    private function auditedModels(): array
    {
        return [
            PurchaseInvoice::class,
            PurchaseReturn::class,
            SalesInvoice::class,
            SalesInvoiceReturn::class,
            SalesProposal::class,
            Transfer::class,
            'Workdo\\Pos\\Models\\Pos',
            'Workdo\\Hrm\\Models\\Payroll',
        ];
    }
}
