<?php

namespace Workdo\Taskly\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectBugRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'priority' => 'required|in:Low,Medium,High',
            'assigned_to' => 'required|array|min:1',
            'stage_id' => 'required|exists:bug_stages,id',
            'description' => 'required|string',
        ];
    }
}
