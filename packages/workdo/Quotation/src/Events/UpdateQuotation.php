<?php

namespace Workdo\Quotation\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Workdo\Quotation\Models\SalesQuotation;

class UpdateQuotation
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public SalesQuotation $quotation
    ) {}
}