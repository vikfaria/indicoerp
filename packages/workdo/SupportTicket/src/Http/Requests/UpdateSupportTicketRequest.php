<?php

namespace Workdo\SupportTicket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'subject' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'category' => 'sometimes|exists:ticket_categories,id',
            'status' => 'sometimes|in:open,In Progress,Closed,On Hold',
            'account_type' => 'sometimes|in:custom,staff,client,vendor',
            'user_id' => 'nullable|exists:users,id',
            'note' => 'nullable|string',
            'attachments' => 'nullable|array'
        ];
    }
}