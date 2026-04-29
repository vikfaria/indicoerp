<?php

namespace Workdo\DoubleEntry\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class YearEndCloseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'financial_year' => 'required|string|max:4',
            'closing_date' => 'required|date'
        ];
    }
}
