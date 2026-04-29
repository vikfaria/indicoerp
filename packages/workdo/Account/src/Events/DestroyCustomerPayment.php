<?php

namespace Workdo\Account\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Account\Models\CustomerPayment;

class DestroyCustomerPayment
{
    use Dispatchable;

    public function __construct(
        public CustomerPayment $customerPayment
    ) {}
}
