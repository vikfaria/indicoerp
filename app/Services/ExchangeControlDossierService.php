<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Workdo\Account\Services\AccountCacheService;
use Workdo\Account\Models\Customer as CustomerProfile;
use Workdo\Account\Models\CustomerPayment;
use Workdo\Account\Models\ExchangeControlDossier;

class ExchangeControlDossierService
{
    public function syncInboundCustomerPayment(CustomerPayment $payment): void
    {
        if (
            !Schema::hasTable('exchange_control_dossiers')
            || !((bool) $payment->is_export_receipt || strtoupper((string) $payment->currency_code) !== 'MZN')
        ) {
            return;
        }

        $payment->loadMissing('customer');
        $customerProfile = CustomerProfile::query()
            ->where('created_by', creatorId())
            ->where('user_id', (int) $payment->customer_id)
            ->first();

        $dossier = ExchangeControlDossier::query()->firstOrNew([
            'company_id' => creatorId(),
            'direction' => 'inbound',
            'payment_type' => 'customer_payment',
            'payment_id' => $payment->id,
        ]);

        $documents = is_array($dossier->documents) ? $dossier->documents : [];
        $incomingDocuments = [
            'contract_reference' => trim((string) ($documents['contract_reference'] ?? '')),
            'invoice_reference' => trim((string) ($payment->export_reference ?: $payment->reference_number ?: $payment->payment_number ?: $payment->id)),
            'export_reference' => trim((string) ($payment->export_reference ?? '')),
            'transport_document_reference' => trim((string) ($documents['transport_document_reference'] ?? '')),
            'customs_declaration_reference' => trim((string) ($documents['customs_declaration_reference'] ?? '')),
            'bank_settlement_reference' => trim((string) ($payment->fx_compliance_reference ?: $payment->reference_number ?: $payment->payment_number ?: $payment->id)),
            'intermediary_bank' => trim((string) ($payment->intermediary_bank ?? '')),
            'withholding_receipt_reference' => trim((string) ($documents['withholding_receipt_reference'] ?? '')),
            'fx_authorization_reference' => trim((string) ($documents['fx_authorization_reference'] ?? '')),
            'correspondence_reference' => trim((string) ($documents['correspondence_reference'] ?? '')),
        ];

        foreach ($incomingDocuments as $key => $value) {
            if ($value !== '' || !array_key_exists($key, $documents)) {
                $documents[$key] = $value;
            }
        }

        $required = $this->resolveInboundRequirements($payment);
        $missing = array_values(array_filter($required, static function (string $field) use ($documents): bool {
            return trim((string) ($documents[$field] ?? '')) === '';
        }));
        $isComplete = $missing === [];

        $dossier->payment_reference = (string) ($payment->payment_number ?: $payment->reference_number ?: $payment->id);
        $dossier->operation_date = $payment->payment_date;
        $dossier->counterparty_name = optional($payment->customer)->name;
        $dossier->counterparty_country = (string) ($payment->receipt_origin_country ?: ($customerProfile?->fiscal_country ?? ''));
        $dossier->currency_code = (string) ($payment->currency_code ?? 'MZN');
        $dossier->amount_mzn = round((float) ($payment->amount_mzn ?? $payment->payment_amount ?? 0), 2);
        $dossier->documents = $documents;
        $dossier->required_documents = $required;
        $dossier->missing_documents = $missing;
        $dossier->is_complete = $isComplete;
        $dossier->completed_at = $isComplete ? now() : null;
        $dossier->completed_by = $isComplete ? Auth::id() : null;
        if (!$dossier->exists) {
            $dossier->created_by = creatorId();
        }
        $dossier->updated_by = Auth::id();
        $dossier->save();

        AccountCacheService::bumpForCompany((int) creatorId());
    }

    /**
     * @return array<int, string>
     */
    private function resolveInboundRequirements(CustomerPayment $payment): array
    {
        $required = [
            'contract_reference',
            'invoice_reference',
            'bank_settlement_reference',
        ];

        $isInternational = (bool) $payment->is_export_receipt
            || strtoupper((string) $payment->currency_code) !== 'MZN';

        if ($isInternational && (bool) $payment->is_export_receipt) {
            $required[] = 'transport_document_reference';
            $required[] = 'customs_declaration_reference';
            $required[] = 'intermediary_bank';
        }

        return array_values(array_unique($required));
    }
}
