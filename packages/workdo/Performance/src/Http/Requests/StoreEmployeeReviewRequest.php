<?php

namespace Workdo\Performance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'reviewer_id' => 'required|exists:users,id',
            'review_cycle_id' => 'required|exists:performance_review_cycles,id',
            'review_date' => 'required|date',
            'status' => 'required|in:pending,in_progress,completed,cancelled'
        ];
    }
}