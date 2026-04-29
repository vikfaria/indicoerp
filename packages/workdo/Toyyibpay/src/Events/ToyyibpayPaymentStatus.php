<?php

namespace Workdo\Toyyibpay\Events;

use App\Models\Plan;
use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

class ToyyibpayPaymentStatus
{
    use Dispatchable;

    public function __construct(
        public Plan $plan,
        public string $type,
        public Order $Order
    ) {}
}