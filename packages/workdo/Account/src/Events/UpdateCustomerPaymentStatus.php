<?php

namespace Workdo\Account\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use Workdo\Account\Models\CustomerPayment;

class UpdateCustomerPaymentStatus
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public CustomerPayment $customerPayment
    ) {}
}
