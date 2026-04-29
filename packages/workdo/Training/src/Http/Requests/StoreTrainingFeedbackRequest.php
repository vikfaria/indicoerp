<?php

namespace Workdo\Training\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrainingFeedbackRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'rating' => 'required|integer|min:1|max:5',
            'comments' => 'nullable|string',
        ];
    }
}