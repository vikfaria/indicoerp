<?php

namespace Workdo\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChecklistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'checklist_id' => 'required|exists:onboarding_checklists,id',
            'task_name' => 'required|max:255',
            'description' => 'nullable',
            'category' => 'required|string|max:100',
            'assigned_to_role' => 'nullable|max:100',
            'due_day' => 'required|integer|min:1',
            'is_required' => 'boolean',
            'status' => 'required|boolean',
        ];
    }
}