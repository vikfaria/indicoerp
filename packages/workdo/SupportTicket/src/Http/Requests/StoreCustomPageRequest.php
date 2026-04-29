<?php

namespace Workdo\SupportTicket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomPageRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:support_ticket_custom_pages,slug',
            'contents' => 'required|string',
            'description' => 'nullable|string|max:500',
            'enable_page_footer' => 'nullable|in:on,off',
        ];
    }
}