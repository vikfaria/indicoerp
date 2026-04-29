<?php

namespace Workdo\Quotation\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workdo\Quotation\Models\SalesQuotation;
use Illuminate\Http\Request;

class CreateQuotation
{

    use Dispatchable;

    public function __construct(
        public Request $request,
        public SalesQuotation $quotation
    ) {}
}