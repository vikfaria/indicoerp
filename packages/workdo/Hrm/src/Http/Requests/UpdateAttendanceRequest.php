<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'clock_in' => 'required|date_format:Y-m-d H:i',
            'clock_out' => 'nullable|date_format:Y-m-d H:i',
            'notes' => 'nullable|string'
        ];
    }
}