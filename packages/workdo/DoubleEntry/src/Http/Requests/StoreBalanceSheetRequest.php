<?php

namespace Workdo\DoubleEntry\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBalanceSheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'balance_sheet_date' => 'required|date',
            'financial_year' => 'required|string|max:4'
        ];
    }
}
