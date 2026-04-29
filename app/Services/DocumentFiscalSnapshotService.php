<?php

namespace App\Services;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceReturn;
use App\Models\SalesProposal;
use App\Models\User;
use Workdo\Account\Models\CreditNote;
use Illuminate\Database\Eloquent\Model;
use Workdo\Account\Models\Customer;
use Workdo\Account\Models\DebitNote;
use Workdo\Account\Models\Vendor;

class DocumentFiscalSnapshotService
{
    public function syncSalesInvoice(SalesInvoice $invoice): void
    {
        $this->persistSnapshots(
            $invoice,
            $this->buildIssuerSnapshot($invoice->created_by),
            $this->buildCustomerSnapshot((int) $invoice->customer_id, $invoice->created_by)
        );
    }

    public function syncPurchaseInvoice(PurchaseInvoice $invoice): void
    {
        $this->persistSnapshots(
            $invoice,
            $this->buildIssuerSnapshot($invoice->created_by),
            $this->buildVendorSnapshot((int) $invoice->vendor_id, $invoice->created_by)
        );
    }

    public function syncSalesProposal(SalesProposal $proposal): void
    {
        $this->persistSnapshots(
            $proposal,
            $this->buildIssuerSnapshot($proposal->created_by),
            $this->buildCustomerSnapshot((int) $proposal->customer_id, $proposal->created_by)
        );
    }

    public function syncSalesReturn(SalesInvoiceReturn $salesReturn): void
    {
        $originalInvoice = $salesReturn->relationLoaded('originalInvoice')
            ? $salesReturn->originalInvoice
            : $salesReturn->originalInvoice()->first();

        $companyId = $originalInvoice?->created_by ?? $salesReturn->created_by;

        $this->persistSnapshots(
            $salesReturn,
            $this->normaliseSnapshot($originalInvoice?->issuer_snapshot) ?? $this->buildIssuerSnapshot($companyId),
            $this->normaliseSnapshot($originalInvoice?->counterparty_snapshot) ?? $this->buildCustomerSnapshot((int) $salesReturn->customer_id, $companyId)
        );
    }

    public function syncPurchaseReturn(PurchaseReturn $purchaseReturn): void
    {
        $originalInvoice = $purchaseReturn->relationLoaded('originalInvoice')
            ? $purchaseReturn->originalInvoice
            : $purchaseReturn->originalInvoice()->first();

        $companyId = $originalInvoice?->created_by ?? $purchaseReturn->created_by;

        $this->persistSnapshots(
            $purchaseReturn,
            $this->normaliseSnapshot($originalInvoice?->issuer_snapshot) ?? $this->buildIssuerSnapshot($companyId),
            $this->normaliseSnapshot($originalInvoice?->counterparty_snapshot) ?? $this->buildVendorSnapshot((int) $purchaseReturn->vendor_id, $companyId)
        );
    }

    public function syncCreditNote(CreditNote $creditNote): void
    {
        $salesReturn = $creditNote->relationLoaded('salesReturn')
            ? $creditNote->salesReturn
            : $creditNote->salesReturn()->with('originalInvoice')->first();
        $invoice = $creditNote->relationLoaded('invoice')
            ? $creditNote->invoice
            : $creditNote->invoice()->first();
        $originalInvoice = $salesReturn?->relationLoaded('originalInvoice')
            ? $salesReturn?->originalInvoice
            : $salesReturn?->originalInvoice()->first();

        $companyId = $salesReturn?->created_by ?? $originalInvoice?->created_by ?? $invoice?->created_by ?? $creditNote->created_by;

        $this->persistSnapshots(
            $creditNote,
            $this->normaliseSnapshot($salesReturn?->issuer_snapshot)
                ?? $this->normaliseSnapshot($originalInvoice?->issuer_snapshot)
                ?? $this->normaliseSnapshot($invoice?->issuer_snapshot)
                ?? $this->buildIssuerSnapshot($companyId),
            $this->normaliseSnapshot($salesReturn?->counterparty_snapshot)
                ?? $this->normaliseSnapshot($originalInvoice?->counterparty_snapshot)
                ?? $this->normaliseSnapshot($invoice?->counterparty_snapshot)
                ?? $this->buildCustomerSnapshot((int) $creditNote->customer_id, $companyId)
        );
    }

    public function syncDebitNote(DebitNote $debitNote): void
    {
        $purchaseReturn = $debitNote->relationLoaded('purchaseReturn')
            ? $debitNote->purchaseReturn
            : $debitNote->purchaseReturn()->with('originalInvoice')->first();
        $invoice = $debitNote->relationLoaded('invoice')
            ? $debitNote->invoice
            : $debitNote->invoice()->first();
        $originalInvoice = $purchaseReturn?->relationLoaded('originalInvoice')
            ? $purchaseReturn?->originalInvoice
            : $purchaseReturn?->originalInvoice()->first();

        $companyId = $purchaseReturn?->created_by ?? $originalInvoice?->created_by ?? $invoice?->created_by ?? $debitNote->created_by;

        $this->persistSnapshots(
            $debitNote,
            $this->normaliseSnapshot($purchaseReturn?->issuer_snapshot)
                ?? $this->normaliseSnapshot($originalInvoice?->issuer_snapshot)
                ?? $this->normaliseSnapshot($invoice?->issuer_snapshot)
                ?? $this->buildIssuerSnapshot($companyId),
            $this->normaliseSnapshot($purchaseReturn?->counterparty_snapshot)
                ?? $this->normaliseSnapshot($originalInvoice?->counterparty_snapshot)
                ?? $this->normaliseSnapshot($invoice?->counterparty_snapshot)
                ?? $this->buildVendorSnapshot((int) $debitNote->vendor_id, $companyId)
        );
    }

    private function persistSnapshots(Model $document, array $issuerSnapshot, array $counterpartySnapshot): void
    {
        $document->forceFill([
            'issuer_snapshot' => $issuerSnapshot,
            'counterparty_snapshot' => $counterpartySnapshot,
        ])->saveQuietly();
    }

    private function buildIssuerSnapshot(?int $companyId): array
    {
        $company = $companyId ? User::find($companyId) : null;
        $settings = $companyId ? getCompanyAllSetting($companyId) : [];
        $taxLabel = $this->resolveTaxLabel($settings);

        return [
            'company_id' => $companyId,
            'company_name' => $this->stringOrNull($settings['company_name'] ?? $company?->name),
            'company_address' => $this->stringOrNull($settings['company_address'] ?? null),
            'company_city' => $this->stringOrNull($settings['company_city'] ?? null),
            'company_state' => $this->stringOrNull($settings['company_state'] ?? null),
            'company_zipcode' => $this->stringOrNull($settings['company_zipcode'] ?? null),
            'company_country' => $this->stringOrNull($settings['company_country'] ?? null),
            'company_telephone' => $this->stringOrNull($settings['company_telephone'] ?? $company?->mobile_no),
            'company_email' => $this->stringOrNull($settings['company_email'] ?? $company?->email),
            'registration_number' => $this->stringOrNull($settings['registration_number'] ?? null),
            'tax_type' => $this->stringOrNull($settings['tax_type'] ?? null),
            'tax_label' => $taxLabel,
            'tax_number' => $this->stringOrNull($settings['company_tax_number'] ?? $settings['vat_number'] ?? null),
            'captured_at' => now()->toISOString(),
        ];
    }

    private function buildCustomerSnapshot(int $customerId, ?int $companyId): array
    {
        $customer = User::find($customerId);
        $customerDetails = Customer::where('user_id', $customerId)->first();

        return $this->buildCounterpartySnapshot('customer', $customer, $customerDetails, $companyId);
    }

    private function buildVendorSnapshot(int $vendorId, ?int $companyId): array
    {
        $vendor = User::find($vendorId);
        $vendorDetails = Vendor::where('user_id', $vendorId)->first();

        return $this->buildCounterpartySnapshot('vendor', $vendor, $vendorDetails, $companyId);
    }

    private function buildCounterpartySnapshot(string $type, ?User $user, ?Model $details, ?int $companyId): array
    {
        $settings = $companyId ? getCompanyAllSetting($companyId) : [];

        return [
            'user_id' => $user?->id,
            'type' => $type,
            'name' => $this->stringOrNull($user?->name),
            'email' => $this->stringOrNull($user?->email),
            'company_name' => $this->stringOrNull(data_get($details, 'company_name')),
            'contact_person_name' => $this->stringOrNull(data_get($details, 'contact_person_name')),
            'contact_person_email' => $this->stringOrNull(data_get($details, 'contact_person_email')),
            'contact_person_mobile' => $this->stringOrNull(data_get($details, 'contact_person_mobile')),
            'primary_email' => $this->stringOrNull(data_get($details, 'primary_email')),
            'primary_mobile' => $this->stringOrNull(data_get($details, 'primary_mobile')),
            'tax_label' => $this->resolveTaxLabel($settings),
            'tax_number' => $this->stringOrNull(data_get($details, 'tax_number')),
            'payment_terms' => $this->stringOrNull(data_get($details, 'payment_terms')),
            'billing_address' => $this->normaliseAddress(data_get($details, 'billing_address')),
            'shipping_address' => $this->normaliseAddress(data_get($details, 'shipping_address')),
            'same_as_billing' => data_get($details, 'same_as_billing'),
            'captured_at' => now()->toISOString(),
        ];
    }

    private function resolveTaxLabel(array $settings): string
    {
        $taxType = strtoupper((string) ($settings['tax_type'] ?? ''));
        $country = strtolower((string) ($settings['company_country'] ?? ''));

        if ($taxType === 'NUIT') {
            return 'NUIT';
        }

        if ($taxType === 'VAT') {
            return 'VAT';
        }

        if ($taxType === 'GST') {
            return 'GST';
        }

        if (str_contains($country, 'mozambique') || str_contains($country, 'moçambique')) {
            return 'NUIT';
        }

        return 'Tax Number';
    }

    private function normaliseSnapshot(mixed $snapshot): ?array
    {
        $normalised = $this->normaliseArray($snapshot);

        if ($normalised === null) {
            return null;
        }

        foreach ($normalised as $value) {
            if (is_array($value) && $value !== []) {
                return $normalised;
            }

            if ($value !== null && $value !== '') {
                return $normalised;
            }
        }

        return null;
    }

    private function normaliseAddress(mixed $address): ?array
    {
        $normalised = $this->normaliseArray($address);

        return $normalised === [] ? null : $normalised;
    }

    private function normaliseArray(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
