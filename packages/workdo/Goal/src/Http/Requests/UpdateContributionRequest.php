<?php

namespace Workdo\Goal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'goal_id' => 'required|exists:goals,id',
            'contribution_date' => 'required|date',
            'contribution_amount' => 'required|numeric|min:0.01',
            'contribution_type' => 'required|in:manual,automatic,journal_entry',
            'reference_type' => 'nullable|in:journal_entry,bank_transaction,manual',
            'reference_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}