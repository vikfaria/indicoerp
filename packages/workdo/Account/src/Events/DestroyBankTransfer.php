<?php

namespace Workdo\Account\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workdo\Account\Models\BankTransfer;

class DestroyBankTransfer
{
    use Dispatchable;

    public function __construct(
        public BankTransfer $bankTransfer
    ) {}
}