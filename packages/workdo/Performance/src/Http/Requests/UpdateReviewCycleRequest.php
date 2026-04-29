<?php

namespace Workdo\Performance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'frequency' => 'required|in:monthly,quarterly,semi-annual,annual',
            'description' => 'nullable|string',
            'status' => 'in:active,inactive'
        ];
    }
}