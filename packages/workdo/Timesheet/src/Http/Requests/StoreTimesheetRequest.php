<?php

namespace Workdo\Timesheet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreTimesheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->can('create-timesheet');
    }

    public function rules(): array
    {
        $rules = [
            'date' => 'required|date',
            'hours' => 'required|integer|min:0|max:12',
            'minutes' => 'required|integer|min:1|max:60',
            'notes' => 'nullable|string|max:1000',
            'type' => 'required|in:clock_in_out,project,manual',
        ];



        // User field only for managers
        if(Auth::user()->can('manage-any-timesheet')) {
            $rules['user_id'] = 'required|exists:users,id';
        } else {
            // Staff can only create for themselves
            $this->merge(['user_id' => Auth::id()]);
        }

        // Project and task fields for project type
        if($this->type === 'project') {
            $rules['project_id'] = 'required|integer';
            $rules['task_id'] = 'nullable|integer';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'minutes.min' => _('Minutes must be at least 1.'),
            'minutes.max' => __('Minutes cannot exceed 60.'),
            'hours.max' => __('Hours cannot exceed 12.'),
        ];
    }
}