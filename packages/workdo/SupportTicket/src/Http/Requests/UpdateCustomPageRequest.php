<?php

namespace Workdo\SupportTicket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'contents' => 'required|string',
            'description' => 'nullable|string',
            'enable_page_footer' => 'nullable|string|in:on,off',
        ];
    }
}