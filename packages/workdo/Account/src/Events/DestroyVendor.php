<?php

namespace Workdo\Account\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workdo\Account\Models\Vendor;

class DestroyVendor
{
    use Dispatchable;

    public function __construct(
        public Vendor $vendor
    ) {}
}