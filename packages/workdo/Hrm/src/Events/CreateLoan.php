<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use Workdo\Hrm\Models\Loan;

class CreateLoan
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Request $request,
        public Loan $loan
    ) {}
}