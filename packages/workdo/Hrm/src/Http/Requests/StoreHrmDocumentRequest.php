<?php

namespace Workdo\Hrm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHrmDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = creatorId();

        return [
            'title' => 'required|string|max:255',
            'document_category_id' => [
                'required',
                'integer',
                Rule::exists('document_categories', 'id')->where(static function ($query) use ($companyId): void {
                    $query->where('created_by', $companyId);
                }),
            ],
            'description' => 'nullable|string',
            'document' => 'required|string',
        ];
    }
}
