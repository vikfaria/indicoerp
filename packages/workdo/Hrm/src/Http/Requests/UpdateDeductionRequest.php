<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeductionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $companyId = creatorId();

        return [
            'deduction_type_id' => [
                'required',
                'integer',
                Rule::exists('deduction_types', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'type' => 'required|in:fixed,percentage',
            'amount' => 'required|numeric|min:0',
        ];
    }
}
