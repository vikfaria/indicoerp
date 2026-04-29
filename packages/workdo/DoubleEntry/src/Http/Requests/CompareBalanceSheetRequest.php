<?php

namespace Workdo\DoubleEntry\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompareBalanceSheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_period_id' => 'required|exists:balance_sheets,id',
            'previous_period_id' => 'required|exists:balance_sheets,id'
        ];
    }
}
