<?php

namespace App\Observers;

use App\Models\AccountingPeriod;
use App\Models\AddOn;
use App\Models\CompanyFiscalProfile;
use App\Models\FiscalDocumentSeries;
use App\Models\MozInssRate;
use App\Models\MozIrpsBracket;
use App\Models\MozIrpsTable;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserActiveModule;
use App\Models\StockCostLayer;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\AssistantActivation\AssistantActivationCacheService;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\CreditNote;
use Workdo\Account\Models\CustomerPayment;
use Workdo\Account\Models\DebitNote;
use Workdo\Account\Models\MozTaxAccountMapping;
use Workdo\Account\Models\VendorPayment;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Employee;
use Workdo\Pos\Models\Pos;

class AssistantActivationCacheObserver
{
    public function __construct(
        private readonly AssistantActivationCacheService $cacheService
    ) {
    }

    public function saved(Model $model): void
    {
        $this->touchForModel($model);
    }

    public function deleted(Model $model): void
    {
        if ($model instanceof User) {
            $this->cacheService->touchUserCompanyVersion($model);

            return;
        }

        $this->touchForModel($model);
    }

    private function touchForModel(Model $model): void
    {
        if ($model instanceof Plan) {
            if ($model->id) {
                $this->cacheService->touchPlanVersion((int) $model->id);
            }

            return;
        }

        if ($model instanceof AddOn) {
            $this->cacheService->touchModuleVersion();

            return;
        }

        if ($model instanceof User) {
            if ($this->userCacheAffectingChange($model)) {
                $this->cacheService->touchUserCompanyVersion($model);
            }

            return;
        }

        $companyId = $this->resolveCompanyId($model);
        if ($companyId === null) {
            return;
        }

        $this->cacheService->touchCompanyVersion($companyId);
    }

    private function resolveCompanyId(Model $model): ?int
    {
        if ($model instanceof UserActiveModule) {
            return (int) $model->user_id;
        }

        if ($model instanceof User) {
            if ($model->type === 'company' || $model->isSuperAdminUser()) {
                return (int) $model->id;
            }

            return (int) ($model->created_by ?: $model->id);
        }

        if ($model instanceof CompanyFiscalProfile) {
            return (int) $model->company_id;
        }

        if ($model instanceof FiscalDocumentSeries) {
            return (int) $model->company_id;
        }

        if ($model instanceof AccountingPeriod) {
            return (int) $model->company_id;
        }

        if ($model instanceof MozIrpsTable || $model instanceof MozInssRate) {
            return (int) ($model->created_by ?: 0);
        }

        if ($model instanceof MozIrpsBracket) {
            $table = MozIrpsTable::query()->find($model->irps_table_id);

            return (int) ($table?->created_by ?: 0);
        }

        if ($model instanceof Media) {
            return (int) ($model->created_by ?: 0);
        }

        if ($model instanceof Branch
            || $model instanceof Employee
            || $model instanceof Pos
            || $model instanceof Warehouse
            || $model instanceof StockMovement
            || $model instanceof StockCostLayer
            || $model instanceof BankAccount
            || $model instanceof ChartOfAccount
            || $model instanceof MozTaxAccountMapping
            || $model instanceof CreditNote
            || $model instanceof DebitNote
            || $model instanceof CustomerPayment
            || $model instanceof VendorPayment
        ) {
            return (int) ($model->created_by ?: 0);
        }

        foreach (['company_id', 'created_by', 'user_id'] as $field) {
            if (isset($model->{$field}) && (int) $model->{$field} > 0) {
                return (int) $model->{$field};
            }
        }

        return null;
    }

    private function userCacheAffectingChange(User $user): bool
    {
        if ($user->wasRecentlyCreated) {
            return true;
        }

        $relevantFields = [
            'active_plan',
            'plan_expire_date',
            'trial_expire_date',
            'is_disable',
            'is_enable_login',
            'created_by',
            'type',
        ];

        $changedFields = array_keys($user->getChanges());

        return count(array_intersect($relevantFields, $changedFields)) > 0;
    }
}
