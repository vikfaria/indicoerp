<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'holiday_type_id' => 'required|integer|exists:holiday_types,id',
            'description' => 'required|string',
            'is_paid' => 'boolean',
            'is_sync_google_calendar' => 'boolean',
            'is_sync_outlook_calendar' => 'boolean'
        ];
    }
}