<?php

namespace Workdo\FormBuilder\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Workdo\FormBuilder\Models\Form;
use Workdo\FormBuilder\Models\FormConversion;

class FormConverted
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public Form $form,
        public FormConversion $conversion
    ) {}
}