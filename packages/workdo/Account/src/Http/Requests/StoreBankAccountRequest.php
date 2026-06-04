<?php

namespace Workdo\Account\Http\Requests;

use App\Models\CompanyFiscalProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Workdo\Hrm\Models\Branch;

class StoreBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isElectronicMoneyAccount = $this->boolean('is_electronic_money_account');
        $hasEnterpriseExemption = $this->boolean('electronic_money_limit_exempt_for_enterprise');

        return [
            'account_number' => 'required|string|max:255',
            'account_name' => 'required|string|max:100',
            'bank_name' => 'required|string|max:100',
            'branch_id' => [
                'required',
                Rule::exists('branches', 'id')->where(function ($query) {
                    $query->where('created_by', creatorId());
                }),
            ],
            'branch_name' => 'nullable|string|max:100',
            'account_type' => 'required',
//            'payment_gateway' => 'nullable|string|max:100',
            'opening_balance' => 'required|numeric',
            'current_balance' => 'required|numeric',
            'iban' => 'nullable|string|max:34',
            'swift_code' => 'nullable|string|max:11',
            'routing_number' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'is_electronic_money_account' => 'boolean',
            'electronic_money_entity' => [
                Rule::requiredIf($isElectronicMoneyAccount),
                'nullable',
                'string',
                'max:120',
            ],
            'electronic_money_level' => [
                Rule::requiredIf($isElectronicMoneyAccount),
                'nullable',
                'string',
                Rule::in(['I', 'II', 'III', 'IV']),
            ],
            'electronic_money_daily_limit_mzn' => [
                Rule::requiredIf($isElectronicMoneyAccount && !$hasEnterpriseExemption),
                'nullable',
                'numeric',
                'min:0',
            ],
            'electronic_money_monthly_limit_mzn' => [
                Rule::requiredIf($isElectronicMoneyAccount && !$hasEnterpriseExemption),
                'nullable',
                'numeric',
                'min:0',
            ],
            'electronic_money_limit_exempt_for_enterprise' => 'boolean',
            'electronic_money_account_purpose' => [
                Rule::requiredIf($isElectronicMoneyAccount && $hasEnterpriseExemption),
                'nullable',
                'string',
                'max:255',
            ],
            'gl_account_id' => 'required|exists:chart_of_accounts,id',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_electronic_money_account' => $this->boolean('is_electronic_money_account'),
            'electronic_money_limit_exempt_for_enterprise' => $this->boolean('electronic_money_limit_exempt_for_enterprise'),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $branchId = (int) $this->input('branch_id');
            if ($branchId > 0) {
                $branch = Branch::query()
                    ->where('id', $branchId)
                    ->where('created_by', creatorId())
                    ->first();

                if (!$branch) {
                    $validator->errors()->add(
                        'branch_id',
                        __('The selected branch does not belong to the active company.')
                    );
                }
            }

            if (!$this->boolean('is_electronic_money_account')) {
                return;
            }

            $dailyLimit = $this->input('electronic_money_daily_limit_mzn');
            $monthlyLimit = $this->input('electronic_money_monthly_limit_mzn');
            $hasEnterpriseExemption = $this->boolean('electronic_money_limit_exempt_for_enterprise');

            if (
                is_numeric($dailyLimit)
                && is_numeric($monthlyLimit)
                && (float) $dailyLimit > (float) $monthlyLimit
            ) {
                $validator->errors()->add(
                    'electronic_money_daily_limit_mzn',
                    __('The daily electronic money limit cannot be greater than the monthly limit.')
                );
            }

            if ($hasEnterpriseExemption && !$this->companyCanUseElectronicMoneyExemption()) {
                $validator->errors()->add(
                    'electronic_money_limit_exempt_for_enterprise',
                    __('Electronic money limit exemptions are only allowed for medium or large enterprises with an active fiscal profile.')
                );
            }

            if ($hasEnterpriseExemption && trim((string) $this->input('electronic_money_account_purpose', '')) === '') {
                $validator->errors()->add(
                    'electronic_money_account_purpose',
                    __('Provide the electronic money account purpose when a limit exemption is enabled.')
                );
            }
        });
    }

    private function companyCanUseElectronicMoneyExemption(): bool
    {
        $classification = CompanyFiscalProfile::query()
            ->where('company_id', creatorId())
            ->where('is_active', true)
            ->value('entity_classification');

        return in_array(strtolower((string) $classification), ['medium', 'large'], true);
    }
}
