<?php

namespace Workdo\Account\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Account\Models\DebitNote;

class DestroyDebitNote
{
    use Dispatchable;

    public function __construct(
        public DebitNote $debitNote
    ) {}
}
