<?php

namespace Workdo\Retainer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use App\Models\SalesInvoice;
use Workdo\Retainer\Models\Retainer;

class ConvertSalesRetainer
{
    use Dispatchable;

    public function __construct(
        public SalesInvoice $invoice,
        public Retainer $retainer
    ) {}
}
