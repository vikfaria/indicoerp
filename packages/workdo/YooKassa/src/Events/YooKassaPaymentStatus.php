<?php

namespace Workdo\YooKassa\Events;

use Illuminate\Foundation\Events\Dispatchable;
use App\Models\Plan;
use App\Models\Order;

class YooKassaPaymentStatus
{
    use Dispatchable;

    public function __construct(
        public Plan $plan,
        public string $type,
        public Order $Order
    ) {}
}