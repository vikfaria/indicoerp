<?php

namespace Workdo\SupportTicket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConversionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'description' => 'required|string|max:10000',
            'files.*' => 'nullable|file|max:10240',
            'attachments' => 'nullable',
            'status' => 'nullable|in:open,In Progress,Closed,On Hold'
        ];
    }
}