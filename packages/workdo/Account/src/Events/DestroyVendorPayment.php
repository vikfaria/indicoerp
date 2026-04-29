<?php

namespace Workdo\Account\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workdo\Account\Models\VendorPayment;

class DestroyVendorPayment
{
    use Dispatchable;

    public function __construct(
        public VendorPayment $vendorPayment
    ) {}
}