<?php

namespace Workdo\FormBuilder\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workdo\FormBuilder\Models\Form;
use Workdo\FormBuilder\Models\FormResponse;

class ViewForm
{
    use Dispatchable;

    public function __construct(
        public Form $form,
        public FormResponse $response
    ) {}
}