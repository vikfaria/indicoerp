<?php

namespace Workdo\BudgetPlanner\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'budget_id' => 'required|exists:budgets,id',
            'account_id' => 'required|exists:chart_of_accounts,id',
            'allocated_amount' => 'required|numeric|min:0',
        ];
    }
}