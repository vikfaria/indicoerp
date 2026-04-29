<?php

namespace Workdo\SupportTicket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFrontendTicketRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'category' => 'required|exists:ticket_categories,id',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => 'required|string|in:In Progress,On Hold,Closed',
            'account_type' => 'required|string|in:custom',
            'attachments' => 'sometimes|array',
            'attachments.*' => 'sometimes|file|max:10240'
        ];
    }
}