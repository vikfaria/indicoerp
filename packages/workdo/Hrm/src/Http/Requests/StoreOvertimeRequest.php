<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOvertimeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'title' => 'required|string|max:255',
            'total_days' => 'required|integer|min:1',
            'hours' => 'required|numeric|min:0',
            'rate' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,expired',
        ];
    }
}