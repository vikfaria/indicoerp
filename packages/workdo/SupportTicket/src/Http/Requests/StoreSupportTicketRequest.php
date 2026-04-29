<?php

namespace Workdo\SupportTicket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupportTicketRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'account_type' => 'required|string|in:custom,staff,client,vendor',
            'email' => 'required|string|email|max:255',
            'category' => 'required|exists:ticket_categories,id',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => 'required|string|in:In Progress,On Hold,Closed',
            'attachments' => 'sometimes|array',
            'attachments.*' => 'sometimes|string'
        ];

        // Add conditional validation based on account type
        if ($this->input('account_type') === 'custom') {
            $rules['name'] = 'required|string|max:255';
        } else {
            $rules['user_id'] = 'required|exists:users,id';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'account_type.required' => __('Account type is required.'),
            'account_type.in' => __('Invalid account type selected.'),
            'name.required' => __('Name is required.'),
            'user_id.required' => __('Please select a user.'),
            'user_id.exists' => __('Selected user is invalid.'),
            'email.required' => __('Email is required.'),
            'email.email' => __('Please enter a valid email address.'),
            'category.required' => __('Category is required.'),
            'category.exists' => __('Selected category is invalid.'),
            'subject.required' => __('Subject is required.'),
            'description.required' => __('Description is required.'),
            'status.required' => __('Status is required.'),
            'status.in' => __('Invalid status selected.'),
            'attachments.*.string' => __('Invalid attachment format.'),
        ];
    }
}