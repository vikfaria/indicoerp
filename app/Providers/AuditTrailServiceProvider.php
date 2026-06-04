<?php

namespace App\Providers;

use App\Models\AccountingPeriod;
use App\Models\FiscalExportHistory;
use App\Models\MonthlyClosingChecklist;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceReturn;
use App\Models\SalesProposal;
use App\Models\Transfer;
use App\Models\WithholdingTaxTreatyRate;
use App\Observers\ModelAuditTrailObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Workdo\Account\Models\OpeningBalance;

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
            FiscalExportHistory::class,
            WithholdingTaxTreatyRate::class,
            'Workdo\\Account\\Models\\Customer',
            'Workdo\\Account\\Models\\Vendor',
            'Workdo\\Account\\Models\\CustomerPayment',
            'Workdo\\Account\\Models\\VendorPayment',
            'Workdo\\Account\\Models\\BankAccount',
            'Workdo\\Account\\Models\\ExchangeControlDossier',
            'Workdo\\Account\\Models\\MozFiscalClosing',
            'Workdo\\Account\\Models\\MozCashClosing',
            'Workdo\\Account\\Models\\MozTaxAccountMapping',
            AccountingPeriod::class,
            MonthlyClosingChecklist::class,
            OpeningBalance::class,
            'Workdo\\Pos\\Models\\Pos',
            'Workdo\\Hrm\\Models\\Payroll',
            'Workdo\\Hrm\\Models\\PayrollEntry',
            'Workdo\\Hrm\\Models\\Employee',
            'Workdo\\Hrm\\Models\\EmployeeSocialSecurityProfile',
            'Workdo\\Hrm\\Models\\EmployeeForeignWorkerProfile',
            'Workdo\\Hrm\\Models\\EmployeeProbationProfile',
            'Workdo\\Hrm\\Models\\EmployeeDependent',
            'Workdo\\Hrm\\Models\\EmployeeDocument',
            'Workdo\\Hrm\\Models\\Attendance',
            'Workdo\\Hrm\\Models\\LeaveApplication',
            'Workdo\\Hrm\\Models\\AnnualLeavePlan',
            'Workdo\\Hrm\\Models\\Allowance',
            'Workdo\\Hrm\\Models\\Deduction',
            'Workdo\\Hrm\\Models\\Loan',
            'Workdo\\Hrm\\Models\\Overtime',
            'Workdo\\Hrm\\Models\\Resignation',
            'Workdo\\Hrm\\Models\\Warning',
            'Workdo\\Hrm\\Models\\Complaint',
            'Workdo\\Hrm\\Models\\Termination',
            'Workdo\\Hrm\\Models\\Acknowledgment',
        ];
    }
}
