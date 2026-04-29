<?php

namespace Workdo\Account\Providers;

use App\Events\ApprovePurchaseReturn;
use App\Events\ApproveSalesReturn;
use App\Events\CreateTransfer;
use App\Events\DefaultData;
use App\Events\DestroyTransfer;
use App\Events\GivePermissionToRole;
use App\Events\PostPurchaseInvoice;
use App\Events\PostSalesInvoice;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Workdo\Account\Listeners\BankAccountFieldUpdate;
use Workdo\Account\Listeners\CreateDebitNoteFromReturn;
use Workdo\Account\Listeners\CreateCreditNoteFromReturn;
use Workdo\Account\Listeners\UpdateMobileServicePaymentStatusLis;
use Workdo\Account\Listeners\DataDefault;
use Workdo\Account\Listeners\PostPurchaseInvoiceListener;
use Workdo\Account\Listeners\CreateTransferListener;
use Workdo\Account\Listeners\DestroyTransferListener;
use Workdo\Account\Listeners\GiveRoleToPermission;
use Workdo\Account\Listeners\PostSalesInvoiceListener;
use Workdo\Account\Listeners\UpdateRetainerPaymentStatusListener;
use Workdo\Retainer\Events\UpdateRetainerPaymentStatus;
use Workdo\Account\Listeners\UpdateCommissionPaymentStatusListener;
use Workdo\Commission\Events\UpdateCommissionPaymentStatus;
use Workdo\Account\Listeners\PaySalaryListener;
use Workdo\Hrm\Events\PaySalary;
use Workdo\Account\Listeners\CreatePosListener;
use Workdo\Fleet\Events\MarkFleetBookingPaymentPaid;
use Workdo\MobileServiceManagement\Events\UpdateMobileServicePaymentStatus;
use Workdo\Pos\Events\CreatePos;
use Workdo\Account\Listeners\MarkFleetBookingPaymentPaidListener;
use Workdo\Fleet\Events\CraeteFleetBookingPayment;
use Workdo\MobileServiceManagement\Events\CreateMobileServicePayment;
use Workdo\Account\Listeners\BeautyBookingPaymentListener;
use Workdo\DairyCattleManagement\Events\CreateDairyCattlePayment;
use Workdo\DairyCattleManagement\Events\UpdateDairyCattlePaymentStatus;
use Workdo\Paypal\Events\BeautyBookingPaymentPaypal;
use Workdo\Stripe\Events\BeautyBookingPaymentStripe;
use Workdo\Account\Listeners\UpdateDairyCattlePaymentStatusListener;
use Workdo\CateringManagement\Events\CreateCateringOrderPayment;
use Workdo\CateringManagement\Events\UpdateCateringOrderPaymentStatus;
use Workdo\Account\Listeners\UpdateCateringOrderPaymentStatusListener;
use Workdo\Account\Listeners\UpdatePropertyPaymentStatusListener;
use Workdo\Account\Listeners\UpdateSalesAgentCommissionPaymentStatusLis;
use Workdo\Account\Listeners\ApproveSalesAgentCommissionAdjustmentLis;
use Workdo\Account\Listeners\ConvertSalesRetainerListener;
use Workdo\Commission\Events\CreateCommissionPayment;
use Workdo\PropertyManagement\Events\CreatePropertyPayment;
use Workdo\PropertyManagement\Events\UpdatePropertyPaymentStatus;
use Workdo\Hrm\Events\CreatePayroll;
use Workdo\Hrm\Events\UpdatePayroll;
use Workdo\Retainer\Events\ConvertSalesRetainer;
use Workdo\SalesAgent\Events\CreateSalesAgentCommissionPayment;
use Workdo\SalesAgent\Events\UpdateSalesAgentCommissionPaymentStatus;
use Workdo\SalesAgent\Events\ApproveSalesAgentCommissionAdjustment;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Add your event listeners here
        DefaultData::class => [
            DataDefault::class,
        ],
        GivePermissionToRole::class => [
            GiveRoleToPermission::class,
        ],
        PostPurchaseInvoice::class => [
            PostPurchaseInvoiceListener::class,
        ],
        PostSalesInvoice::class => [
            PostSalesInvoiceListener::class,
        ],
        CreateTransfer::class => [
            CreateTransferListener::class,
        ],
        DestroyTransfer::class => [
            DestroyTransferListener::class,
        ],
        ApprovePurchaseReturn::class => [
            CreateDebitNoteFromReturn::class,
        ],
        ApproveSalesReturn::class => [
            CreateCreditNoteFromReturn::class,
        ],
        UpdateRetainerPaymentStatus::class => [
            UpdateRetainerPaymentStatusListener::class,
        ],
        ConvertSalesRetainer::class => [
            ConvertSalesRetainerListener::class,
        ],
        CreateCommissionPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateCommissionPaymentStatus::class => [
            UpdateCommissionPaymentStatusListener::class,
        ],
        PaySalary::class => [
            PaySalaryListener::class,
        ],
        CreatePos::class => [
            BankAccountFieldUpdate::class,
            CreatePosListener::class,
        ],
        CreateMobileServicePayment::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateMobileServicePaymentStatus::class => [
            UpdateMobileServicePaymentStatusLis::class,
        ],
        CraeteFleetBookingPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        MarkFleetBookingPaymentPaid::class => [
            MarkFleetBookingPaymentPaidListener::class,
        ],
        BeautyBookingPaymentStripe::class => [
            BeautyBookingPaymentListener::class,
        ],
        BeautyBookingPaymentPaypal::class => [
            BeautyBookingPaymentListener::class,
        ],
        CreateDairyCattlePayment::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateDairyCattlePaymentStatus::class => [
            UpdateDairyCattlePaymentStatusListener::class,
        ],
        CreateCateringOrderPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateCateringOrderPaymentStatus::class => [
            UpdateCateringOrderPaymentStatusListener::class,
        ],
        CreatePropertyPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreatePayroll::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdatePayroll::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateSalesAgentCommissionPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateSalesAgentCommissionPaymentStatus::class => [
            UpdateSalesAgentCommissionPaymentStatusLis::class,
        ],
        ApproveSalesAgentCommissionAdjustment::class => [
            ApproveSalesAgentCommissionAdjustmentLis::class,
        ],

    ];
}
