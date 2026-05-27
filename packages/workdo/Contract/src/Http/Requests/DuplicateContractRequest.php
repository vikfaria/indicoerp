<?php

namespace Workdo\Contract\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Workdo\Contract\Models\Contract;

class DuplicateContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => 'required|max:255',
            'user_id' => 'required|exists:users,id',
            'value' => 'required',
            'type_id' => 'required|exists:contract_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'description' => 'nullable|string',
            'status' => 'required|string',
            'is_labour_contract' => 'sometimes|boolean',
            'legal_contract_type' => ['nullable', 'string', Rule::in(Contract::LEGAL_CONTRACT_TYPES)],
            'fixed_term_justification' => 'nullable|string|max:5000',
            'probation_category' => ['nullable', 'string', Rule::in(Contract::PROBATION_CATEGORIES)],
            'legal_notes' => 'nullable|string|max:5000',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $isLabourContract = filter_var($this->input('is_labour_contract', false), FILTER_VALIDATE_BOOLEAN);

            if (!$isLabourContract) {
                return;
            }

            $legalType = (string) $this->input('legal_contract_type', '');
            if ($legalType === '') {
                $validator->errors()->add('legal_contract_type', __('Legal labour contract type is required when labour contract is enabled.'));
            }

            if (
                in_array($legalType, Contract::FIXED_TERM_TYPES, true)
                && trim((string) $this->input('fixed_term_justification', '')) === ''
            ) {
                $validator->errors()->add('fixed_term_justification', __('A legal/economic/technical justification is required for fixed-term labour contracts.'));
            }
        });
    }
}
