<?php

namespace Workdo\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'candidate_id' => 'required|exists:candidates,id',
            'job_id' => 'nullable|exists:job_postings,id',
            'offer_date' => 'required',
            'position' => 'required',
            'department_id' => 'required|exists:departments,id',
            'salary' => 'required|min:0',
            'bonus' => 'nullable|min:0',
            'equity' => 'nullable',
            'benefits' => 'nullable',
            'start_date' => 'required',
            'expiration_date' => 'required',
            'offer_letter_path' => 'nullable',
            'status' => 'required',
            'response_date' => 'nullable',
            'decline_reason' => 'nullable',
            'approved_by' => 'nullable|exists:users,id'
        ];
    }
}