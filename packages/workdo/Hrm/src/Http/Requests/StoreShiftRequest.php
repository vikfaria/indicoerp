<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shift_name' => 'required|max:100',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'break_start_time' => 'required|date_format:H:i',
            'break_end_time' => 'required|date_format:H:i',
            'is_night_shift' => 'boolean',
        ];
    }
}