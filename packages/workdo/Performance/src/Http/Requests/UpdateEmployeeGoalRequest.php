<?php

namespace Workdo\Performance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:users,id',
            'goal_type_id' => 'required|exists:performance_goal_types,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'target' => 'required|string|max:50',
            'progress' => 'required|numeric|min:0',
            'status' => 'required|in:not_started,in_progress,completed,overdue',
        ];
    }
}