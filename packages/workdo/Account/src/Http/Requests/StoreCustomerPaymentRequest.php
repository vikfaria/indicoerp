<?php

namespace Workdo\Account\Http\Requests;

use App\Http\Requests\Concerns\BuildsTenantScopedRules;
use App\Models\SalesInvoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Workdo\Account\Models\CreditNote;

class StoreCustomerPaymentRequest extends FormRequest
{
    use BuildsTenantScopedRules;

    protected function prepareForValidation(): void
    {
        if (!$this->filled('payment_method')) {
            $this->merge([
                'payment_method' => 'bank_transfer',
            ]);
        }
    }

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $customerId = (int) $this->input('customer_id');
        $paymentMethods = ['bank_transfer', 'cash', 'cheque', 'card', 'mobile_money', 'other'];
        $mobileMoneyProviders = ['mpesa', 'emola', 'mkesh'];

        return [
            'payment_date' => 'required|date|before_or_equal:today',
            'customer_id' => ['required', $this->companyClientExistsRule()],
            'bank_account_id' => ['required', $this->tenantOwnedExistsRule('bank_accounts', 'id', ['is_active' => true])],
            'payment_method' => ['required', Rule::in($paymentMethods)],
            'mobile_money_provider' => ['nullable', 'required_if:payment_method,mobile_money', Rule::in($mobileMoneyProviders)],
            'mobile_money_number' => 'nullable|required_if:payment_method,mobile_money|string|max:30|regex:/^\+?[0-9]{8,15}$/',
            'reference_number' => 'nullable|string|max:100',
            'payment_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'allocations' => 'nullable|array',
            'allocations.*.invoice_id' => [
                'required',
                Rule::exists('sales_invoices', 'id')->where(function ($query) use ($customerId) {
                    $query->where('created_by', creatorId())
                        ->where('customer_id', $customerId)
                        ->whereIn('status', ['posted', 'partial'])
                        ->where('balance_amount', '>', 0);
                }),
            ],
            'allocations.*.amount' => 'required|numeric|min:0.01',
            'credit_notes' => 'nullable|array',
            'credit_notes.*.credit_note_id' => [
                'required',
                Rule::exists('credit_notes', 'id')->where(function ($query) use ($customerId) {
                    $query->where('created_by', creatorId())
                        ->where('customer_id', $customerId)
                        ->whereIn('status', ['approved', 'partial'])
                        ->where('balance_amount', '>', 0);
                }),
            ],
            'credit_notes.*.amount' => 'required|numeric|min:0.01',
        ];
    }

    public function messages()
    {
        return [
            'payment_date.before_or_equal' => __('Payment date cannot be in the future.'),
            'payment_method.required' => __('Payment method is required.'),
            'mobile_money_provider.required_if' => __('Mobile money provider is required when payment method is mobile money.'),
            'mobile_money_number.required_if' => __('Mobile money number is required when payment method is mobile money.'),
            'mobile_money_number.regex' => __('Mobile money number format is invalid.'),
            'allocations.*.amount.min' => __('Allocation amount must be greater than 0.'),
            'credit_notes.*.amount.min' => __('Credit note amount must be greater than 0.')
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $allocations = collect($this->input('allocations', []));
            $creditNotes = collect($this->input('credit_notes', []));

            if ($allocations->isEmpty()) {
                $validator->errors()->add('allocations', __('At least one invoice allocation is required to create a payment.'));
                return;
            }

            $invoiceBalances = SalesInvoice::query()
                ->where('created_by', creatorId())
                ->where('customer_id', $this->input('customer_id'))
                ->whereIn('status', ['posted', 'partial'])
                ->where('balance_amount', '>', 0)
                ->whereIn('id', $allocations->pluck('invoice_id')->filter()->all())
                ->get(['id', 'balance_amount'])
                ->keyBy('id');

            foreach ($allocations as $index => $allocation) {
                $invoice = $invoiceBalances->get((int) data_get($allocation, 'invoice_id'));
                $amount = (float) data_get($allocation, 'amount', 0);

                if ($invoice && $amount > (float) $invoice->balance_amount + 0.0001) {
                    $validator->errors()->add("allocations.$index.amount", __('Allocation amount cannot exceed the invoice balance.'));
                }
            }

            $creditNoteBalances = CreditNote::query()
                ->where('created_by', creatorId())
                ->where('customer_id', $this->input('customer_id'))
                ->whereIn('status', ['approved', 'partial'])
                ->where('balance_amount', '>', 0)
                ->whereIn('id', $creditNotes->pluck('credit_note_id')->filter()->all())
                ->get(['id', 'balance_amount'])
                ->keyBy('id');

            foreach ($creditNotes as $index => $creditNote) {
                $note = $creditNoteBalances->get((int) data_get($creditNote, 'credit_note_id'));
                $amount = (float) data_get($creditNote, 'amount', 0);

                if ($note && $amount > (float) $note->balance_amount + 0.0001) {
                    $validator->errors()->add("credit_notes.$index.amount", __('Credit note amount cannot exceed the available balance.'));
                }
            }

            $totalInvoiceAmount = round($allocations->sum(fn ($allocation) => (float) data_get($allocation, 'amount', 0)), 2);
            $totalCreditNoteAmount = round($creditNotes->sum(fn ($creditNote) => (float) data_get($creditNote, 'amount', 0)), 2);

            if ($totalCreditNoteAmount > $totalInvoiceAmount + 0.0001) {
                $validator->errors()->add('credit_notes', __('Credit note amount cannot exceed the total invoice allocation amount.'));
            }

            $expectedPaymentAmount = round(max(0, $totalInvoiceAmount - $totalCreditNoteAmount), 2);
            $paymentAmount = round((float) $this->input('payment_amount', 0), 2);

            if (abs($paymentAmount - $expectedPaymentAmount) > 0.01) {
                $validator->errors()->add('payment_amount', __('Payment amount must match allocations minus applied credit notes.'));
            }
        });
    }
}
