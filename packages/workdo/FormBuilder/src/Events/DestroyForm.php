<?php

namespace Workdo\FormBuilder\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workdo\FormBuilder\Models\Form;

class DestroyForm
{
    use Dispatchable;

    public function __construct(
        public Form $form
    ) {}
}