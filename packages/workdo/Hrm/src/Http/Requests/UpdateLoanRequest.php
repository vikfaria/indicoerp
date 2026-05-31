<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLoanRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $companyId = creatorId();

        return [
            'title' => 'required|string|max:255',
            'loan_type_id' => [
                'required',
                'integer',
                Rule::exists('loan_types', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'type' => 'required|in:fixed,percentage',
            'amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'reason' => 'nullable|string',
        ];
    }
}
