<?php

namespace Workdo\SupportTicket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKnowledgeBaseRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'category' => 'required|string',
            'description' => 'required|string|max:500',
            'content' => 'nullable|string',
        ];
    }
}