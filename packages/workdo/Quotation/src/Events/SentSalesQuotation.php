<?php

namespace Workdo\Quotation\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workdo\Quotation\Models\SalesQuotation;

class SentSalesQuotation
{
    use Dispatchable;

    public function __construct(
        public SalesQuotation $quotation
    ) {}
}