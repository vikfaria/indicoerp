<?php

namespace Workdo\Coingate\Events;

use App\Models\Order;
use App\Models\Plan;
use Illuminate\Foundation\Events\Dispatchable;

class CoingatePaymentStatus
{
    use Dispatchable;

    public function __construct(
        public Plan $plan,
        public string $type,
        public Order $order
    ) {}
}