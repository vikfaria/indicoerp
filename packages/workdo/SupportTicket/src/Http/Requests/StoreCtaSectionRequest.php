<?php

namespace Workdo\SupportTicket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCtaSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'knowledge_base.title' => 'required|string|max:255',
            'knowledge_base.description' => 'required|string',
            'faq.title' => 'required|string|max:255',
            'faq.description' => 'required|string',
        ];
    }
}