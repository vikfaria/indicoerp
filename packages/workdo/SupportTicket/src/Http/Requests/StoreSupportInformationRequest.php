<?php

namespace Workdo\SupportTicket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupportInformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'response_time' => 'required|string',
            'opening_hours' => 'required|string',
            'closing_hours' => 'required|string',
            'phone_support' => 'required|string|max:255',
            'business_hours' => 'required|array',
            'business_hours.*.day' => 'required|string',
            'business_hours.*.is_open' => 'required|boolean',
        ];
    }
}