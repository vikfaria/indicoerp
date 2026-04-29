<?php

namespace Workdo\BudgetPlanner\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBudgetPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period_name' => 'required|max:100',
            'financial_year' => 'required|max:4',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date'
        ];
    }
}