<?php

namespace Workdo\Taskly\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => 'required|exists:projects,id',
            'milestone_id' => 'required|exists:project_milestones,id',
            'title' => 'required|string|max:255',
            'priority' => 'nullable|in:High,Medium,Low',
            'assigned_to' => 'required|array',
            'assigned_to.*' => 'exists:users,id',
            'duration' => 'required|string|max:255',
            'description' => 'required|string',
            'stage_id' => 'nullable|integer|exists:task_stages,id',
        ];
    }
}
