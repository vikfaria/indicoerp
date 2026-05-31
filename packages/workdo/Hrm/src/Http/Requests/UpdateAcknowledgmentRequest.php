<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAcknowledgmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = creatorId();

        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'document_id' => [
                'required',
                'integer',
                Rule::exists('hrm_documents', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'acknowledgment_note' => 'nullable|string'
        ];
    }
}
