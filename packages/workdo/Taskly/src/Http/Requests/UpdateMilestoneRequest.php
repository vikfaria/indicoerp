<?php

namespace Workdo\Taskly\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'milestone_id' => 'required',
            'title' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:Incomplete,Complete',
            'progress' => 'required|integer|min:0|max:100',
            'summary' => 'nullable|string',
        ];
    }
}
