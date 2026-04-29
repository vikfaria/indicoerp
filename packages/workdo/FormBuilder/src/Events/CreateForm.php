<?php

namespace Workdo\FormBuilder\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Workdo\FormBuilder\Models\Form;

class CreateForm
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public Form $form
    ) {}
}