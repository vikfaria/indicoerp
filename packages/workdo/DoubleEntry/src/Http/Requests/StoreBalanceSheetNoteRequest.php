<?php

namespace Workdo\DoubleEntry\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBalanceSheetNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note_title' => 'required|string|max:255',
            'note_content' => 'required|string'
        ];
    }
}
