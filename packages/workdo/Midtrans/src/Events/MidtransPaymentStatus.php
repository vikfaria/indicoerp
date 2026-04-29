<?php

namespace Workdo\Midtrans\Events;

use App\Models\Order;
use App\Models\Plan;
use Illuminate\Foundation\Events\Dispatchable;

class MidtransPaymentStatus
{
    use Dispatchable;

    public function __construct(
        public Plan $plan,
        public string $type,
        public Order $Order
    ) {}
}