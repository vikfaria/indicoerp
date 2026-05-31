<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOvertimeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $companyId = creatorId();

        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'title' => 'required|string|max:255',
            'total_days' => 'required|integer|min:1',
            'hours' => 'required|numeric|min:0',
            'rate' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            // Backward compatible: legacy clients may still send active/expired.
            'status' => 'nullable|in:active,expired,pending,approved,rejected',
        ];
    }
}
