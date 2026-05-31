<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = creatorId();

        return [
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'holiday_type_id' => [
                'required',
                'integer',
                Rule::exists('holiday_types', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'description' => 'required|string',
            'is_paid' => 'boolean',
            'is_sync_google_calendar' => 'boolean',
            'is_sync_outlook_calendar' => 'boolean'
        ];
    }
}
