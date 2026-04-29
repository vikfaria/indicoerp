<?php

namespace Workdo\ProductService\Providers;

use App\Events\PostPurchaseInvoice;
use App\Events\ApprovePurchaseReturn;
use App\Events\CompleteSalesReturn;
use App\Events\PostSalesInvoice;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Workdo\Pos\Events\CreatePos;
use Workdo\ProductService\Listeners\PostPurchaseInvoiceListener;
use Workdo\ProductService\Listeners\ApprovePurchaseReturnListener;
use Workdo\ProductService\Listeners\CompleteSalesReturnListener;
use Workdo\ProductService\Listeners\PosCreateListener;
use Workdo\ProductService\Listeners\PostSalesInvoiceListener;
use Workdo\Retainer\Events\ConvertSalesRetainer;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PostPurchaseInvoice::class => [
            PostPurchaseInvoiceListener::class,
        ],
        PostSalesInvoice::class => [
            PostSalesInvoiceListener::class,
        ],
        ApprovePurchaseReturn::class => [
            ApprovePurchaseReturnListener::class,
        ],
        CompleteSalesReturn::class => [
            CompleteSalesReturnListener::class,
        ],
        CreatePos::class => [
            PosCreateListener::class,
        ],
        ConvertSalesRetainer::class => [
            CompleteSalesReturnListener::class,
        ],
    ];
}
