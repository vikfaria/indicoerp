<?php

namespace Workdo\Account\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workdo\Account\Models\Customer;

class DestroyCustomer
{
    use Dispatchable;

    public function __construct(
        public Customer $customer
    ) {}
}