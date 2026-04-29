<?php

namespace Workdo\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDashboardWelcomeCardRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'card_title' => 'required|string|max:255',
            'card_description' => 'required|string',
            'button_text' => 'required|string|max:100',
            'button_icon' => 'required|string|max:100',
        ];
    }

    public function messages()
    {
        return [
            'card_title.required' => __('Card title is required.'),
            'card_description.required' => __('Card description is required.'),
            'button_text.required' => __('Button text is required.'),
            'button_icon.required' => __('Button icon is required.'),
        ];
    }
}