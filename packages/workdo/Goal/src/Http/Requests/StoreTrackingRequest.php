<?php

namespace Workdo\Goal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'goal_id' => 'required|exists:goals,id',
            'tracking_date' => 'required|date',
            'previous_amount' => 'required|numeric|min:0',
            'contribution_amount' => 'required|numeric|min:0',
            'current_amount' => 'required|numeric|min:0',
            'progress_percentage' => 'required|numeric|min:0|max:100',
            'days_remaining' => 'required|integer|min:0',
            'projected_completion_date' => 'nullable|date',
            'on_track_status' => 'required|in:ahead,on_track,behind,critical',
        ];
    }
}