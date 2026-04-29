<?php

namespace Workdo\Account\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workdo\Account\Models\BankAccount;

class DestroyBankAccount
{
    use Dispatchable;

    public function __construct(
        public BankAccount $bankAccount
    ) {}
}