<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use Workdo\Hrm\Models\Employee;

class UpdateEmployee
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Request $request,
        public Employee $employee
    ) {

    }
}