<?php

namespace Workdo\SupportTicket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTitleSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'create_ticket.title' => 'required|string|max:255',
            'create_ticket.description' => 'required|string',
            'search_ticket.title' => 'required|string|max:255',
            'search_ticket.description' => 'required|string',
            'knowledge_base.title' => 'required|string|max:255',
            'knowledge_base.description' => 'required|string',
            'faq.title' => 'required|string|max:255',
            'faq.description' => 'required|string',
            'contact.title' => 'required|string|max:255',
            'contact.description' => 'required|string',
        ];
    }
}