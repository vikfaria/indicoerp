<?php

namespace App\Providers;

use App\Models\AccountingPeriod;
use App\Models\AddOn;
use App\Models\CompanyFiscalProfile;
use App\Models\FiscalDocumentSeries;
use App\Models\MozInssRate;
use App\Models\MozIrpsBracket;
use App\Models\MozIrpsTable;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\Plan;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceReturn;
use App\Models\User;
use App\Models\UserActiveModule;
use App\Models\StockCostLayer;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Observers\AssistantActivationCacheObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
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

class AssistantActivationCacheServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $observer = AssistantActivationCacheObserver::class;

        foreach ($this->observedModels() as $modelClass) {
            if (! class_exists($modelClass)) {
                continue;
            }

            if (! is_subclass_of($modelClass, Model::class)) {
                continue;
            }

            $modelClass::observe($observer);
        }
    }

    /**
     * @return array<int, class-string<Model>>
     */
    private function observedModels(): array
    {
        return [
            User::class,
            UserActiveModule::class,
            Plan::class,
            AddOn::class,
            CompanyFiscalProfile::class,
            FiscalDocumentSeries::class,
            AccountingPeriod::class,
            MozIrpsTable::class,
            MozIrpsBracket::class,
            MozInssRate::class,
            SalesInvoice::class,
            PurchaseInvoice::class,
            SalesInvoiceReturn::class,
            PurchaseReturn::class,
            Media::class,
            BankAccount::class,
            ChartOfAccount::class,
            CreditNote::class,
            CustomerPayment::class,
            DebitNote::class,
            MozTaxAccountMapping::class,
            VendorPayment::class,
            Branch::class,
            Employee::class,
            Pos::class,
            Warehouse::class,
            StockMovement::class,
            StockCostLayer::class,
        ];
    }
}
