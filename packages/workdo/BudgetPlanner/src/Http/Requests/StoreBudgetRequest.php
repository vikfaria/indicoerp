<?php

namespace Workdo\BudgetPlanner\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'budget_name' => 'required|string|max:255',
            'period_id' => [
                'required',
                'exists:budget_periods,id',
                function ($attribute, $value, $fail) {
                    $period = \Workdo\BudgetPlanner\Models\BudgetPeriod::find($value);
                    if ($period && $period->status !== 'active') {
                        $fail('Budget can only be created in active periods.');
                    }
                },
            ],
            'budget_type' => 'required|in:operational,capital,cash_flow',

        ];
    }
}
