<?php

namespace Workdo\Account\Http\Requests;

use App\Http\Requests\Concerns\BuildsTenantScopedRules;
use App\Models\PurchaseInvoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Workdo\Account\Models\DebitNote;

class StoreVendorPaymentRequest extends FormRequest
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
        $vendorId = (int) $this->input('vendor_id');
        $paymentMethods = ['bank_transfer', 'cash', 'cheque', 'card', 'mobile_money', 'other'];
        $mobileMoneyProviders = ['mpesa', 'emola', 'mkesh'];

        return [
            'payment_date' => 'required|date|before_or_equal:today',
            'vendor_id' => ['required', $this->companyVendorExistsRule()],
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
                Rule::exists('purchase_invoices', 'id')->where(function ($query) use ($vendorId) {
                    $query->where('created_by', creatorId())
                        ->where('vendor_id', $vendorId)
                        ->whereIn('status', ['posted', 'partial'])
                        ->where('balance_amount', '>', 0);
                }),
            ],
            'allocations.*.amount' => 'required|numeric|min:0.01',
            'debit_notes' => 'nullable|array',
            'debit_notes.*.debit_note_id' => [
                'required',
                Rule::exists('debit_notes', 'id')->where(function ($query) use ($vendorId) {
                    $query->where('created_by', creatorId())
                        ->where('vendor_id', $vendorId)
                        ->whereIn('status', ['approved', 'partial'])
                        ->where('balance_amount', '>', 0);
                }),
            ],
            'debit_notes.*.amount' => 'required|numeric|min:0.01',
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
            'debit_notes.*.amount.min' => __('Debit note amount must be greater than 0.')
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $allocations = collect($this->input('allocations', []));
            $debitNotes = collect($this->input('debit_notes', []));

            if ($allocations->isEmpty()) {
                $validator->errors()->add('allocations', __('At least one invoice allocation is required to create a payment.'));
                return;
            }

            $invoiceBalances = PurchaseInvoice::query()
                ->where('created_by', creatorId())
                ->where('vendor_id', $this->input('vendor_id'))
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

            $debitNoteBalances = DebitNote::query()
                ->where('created_by', creatorId())
                ->where('vendor_id', $this->input('vendor_id'))
                ->whereIn('status', ['approved', 'partial'])
                ->where('balance_amount', '>', 0)
                ->whereIn('id', $debitNotes->pluck('debit_note_id')->filter()->all())
                ->get(['id', 'balance_amount'])
                ->keyBy('id');

            foreach ($debitNotes as $index => $debitNote) {
                $note = $debitNoteBalances->get((int) data_get($debitNote, 'debit_note_id'));
                $amount = (float) data_get($debitNote, 'amount', 0);

                if ($note && $amount > (float) $note->balance_amount + 0.0001) {
                    $validator->errors()->add("debit_notes.$index.amount", __('Debit note amount cannot exceed the available balance.'));
                }
            }

            $totalInvoiceAmount = round($allocations->sum(fn ($allocation) => (float) data_get($allocation, 'amount', 0)), 2);
            $totalDebitNoteAmount = round($debitNotes->sum(fn ($debitNote) => (float) data_get($debitNote, 'amount', 0)), 2);

            if ($totalDebitNoteAmount > $totalInvoiceAmount + 0.0001) {
                $validator->errors()->add('debit_notes', __('Debit note amount cannot exceed the total invoice allocation amount.'));
            }

            $expectedPaymentAmount = round(max(0, $totalInvoiceAmount - $totalDebitNoteAmount), 2);
            $paymentAmount = round((float) $this->input('payment_amount', 0), 2);

            if (abs($paymentAmount - $expectedPaymentAmount) > 0.01) {
                $validator->errors()->add('payment_amount', __('Payment amount must match allocations minus applied debit notes.'));
            }
        });
    }
}
