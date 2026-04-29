<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use Workdo\Hrm\Models\Department;

class UpdateDepartment
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Request $request,
        public Department $department
    ) {

    }
}