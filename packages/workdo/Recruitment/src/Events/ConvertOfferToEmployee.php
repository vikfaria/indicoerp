<?php

namespace Workdo\Recruitment\Events;

use Workdo\Recruitment\Models\Offer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Workdo\Hrm\Models\Employee;

class ConvertOfferToEmployee
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public Offer $offer,
        public Employee $employee
    ) {}
}