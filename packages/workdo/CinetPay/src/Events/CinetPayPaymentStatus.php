<?php

namespace Workdo\CinetPay\Events;

use App\Models\Plan;
use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

class CinetPayPaymentStatus
{
    use Dispatchable;

    public function __construct(
        public Plan $plan,
        public string $type,
        public Order $order
    ) {}
}