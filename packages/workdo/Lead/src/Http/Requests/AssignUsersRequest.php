<?php

namespace Workdo\Lead\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignUsersRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|integer|exists:users,id'
        ];
    }

    public function messages()
    {
        return [
            'user_ids.required' => __('Please select at least one user.'),
            'user_ids.array' => __('Invalid user selection format.'),
            'user_ids.min' => __('Please select at least one user.'),
            'user_ids.*.exists' => __('Selected user does not exist.'),
        ];
    }
}