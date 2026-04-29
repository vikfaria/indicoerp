<?php

namespace Workdo\Goal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'goal_name' => 'required|max:255',
            'goal_description' => 'nullable',
            'category_id' => 'required|exists:goal_categories,id',
            'goal_type' => 'required|in:savings,debt_reduction,expense_reduction',
            'target_amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'target_date' => 'required|date|after:start_date',
            'priority' => 'required|in:low,medium,high,critical',
            'status' => 'required|in:draft,active,paused,completed,cancelled',
            'account_id' => 'nullable|exists:chart_of_accounts,id',
        ];
    }
}
