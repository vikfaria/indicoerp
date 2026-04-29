<?php

namespace Workdo\Quotation\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workdo\Quotation\Models\SalesQuotation;

class DestroyQuotation
{
    use Dispatchable;

    public function __construct(
        public SalesQuotation $quotation
    ) {}
}