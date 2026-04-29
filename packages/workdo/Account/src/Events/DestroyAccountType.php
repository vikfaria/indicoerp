<?php

namespace Workdo\Account\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Account\Models\AccountType;

class DestroyAccountType
{
    use Dispatchable;

    public function __construct(
        public AccountType $accounttype
    ) {}
}
