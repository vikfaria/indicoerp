<?php

namespace Workdo\Account\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\PurchaseInvoice;
use App\Models\User;
use App\Support\MozambiqueTaxNumber;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Workdo\Account\Models\Vendor;
use Workdo\Account\Http\Requests\StoreVendorRequest;
use Workdo\Account\Http\Requests\UpdateVendorRequest;
use Workdo\Account\Events\CreateVendor;
use Workdo\Account\Events\UpdateVendor;
use Workdo\Account\Events\DestroyVendor;

class VendorController extends Controller
{
    /**
     * Cache the vendor table column availability to avoid repeated schema lookups.
     *
     * @var array<string, bool>
     */
    private static array $vendorColumnCache = [];

    public function index()
    {
        if(Auth::user()->can('manage-vendors')){
            $vendors = Vendor::query()
                ->with('user:id,name,avatar,is_disable')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-vendors')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-vendors')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('company_name'), fn($q) => $q->where('company_name', 'like', '%' . request('company_name') . '%'))
                ->when(request('vendor_code'), fn($q) => $q->where('vendor_code', 'like', '%' . request('vendor_code') . '%'))
                ->when(request('tax_number'), fn($q) => $q->where('tax_number', 'like', '%' . request('tax_number') . '%'))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            $users = User::where('type', 'vendor')
                ->where('created_by', creatorId())
                ->whereNotIn('id', Vendor::pluck('user_id')->filter())
                ->select('id', 'name', 'email', 'mobile_no')
                ->get();

            return Inertia::render('Account/Vendors/Index', [
                'vendors' => $vendors,
                'users' => $users,
            ]);
        }
        return back()->with('error', __('Permission denied'));
    }



    public function store(StoreVendorRequest $request)
    {
        if(Auth::user()->can('create-vendors')){
            $validated = $request->validated();

            $vendor = new Vendor();
            $this->syncVendorAttributes($vendor, $validated, true);
            $vendor->save();

            CreateVendor::dispatch($request, $vendor);

            return redirect()->route('account.vendors.index')->with('success', __('The vendor has been created successfully.'));
        }
        return redirect()->route('account.vendors.index')->with('error', __('Permission denied'));
    }

    public function update(UpdateVendorRequest $request, Vendor $vendor)
    {
        if(Auth::user()->can('edit-vendors')){
            if ((int) $vendor->created_by !== (int) creatorId()) {
                return back()->with('error', __('Permission denied'));
            }

            $validated = $request->validated();
            $hasFiscalHistory = $this->vendorHasFiscalHistory($vendor);
            $criticalFiscalChange = $this->hasCriticalFiscalChange($vendor, $validated);
            $fiscalOverrideSnapshot = $criticalFiscalChange
                ? $this->buildFiscalAuditSnapshot($vendor, $validated)
                : null;

            $this->syncVendorAttributes($vendor, $validated, false);

            if ($hasFiscalHistory && $vendor->fiscal_identity_locked_at === null) {
                $vendor->fiscal_identity_locked_at = now();
                if (empty($vendor->fiscal_identity_lock_reason)) {
                    $vendor->fiscal_identity_lock_reason = 'fiscal_documents_issued';
                }
            }

            if ($criticalFiscalChange && !empty($validated['fiscal_identity_lock_reason'])) {
                $vendor->fiscal_identity_lock_reason = trim((string) $validated['fiscal_identity_lock_reason']);
                if ($vendor->fiscal_identity_locked_at === null) {
                    $vendor->fiscal_identity_locked_at = now();
                }
            }

            $vendor->save();

            if ($criticalFiscalChange && !empty($validated['fiscal_identity_lock_reason'])) {
                $this->recordFiscalOverrideAudit(
                    $vendor,
                    $fiscalOverrideSnapshot['old'] ?? [],
                    $fiscalOverrideSnapshot['new'] ?? [],
                    trim((string) $validated['fiscal_identity_lock_reason'])
                );
            }

            UpdateVendor::dispatch($request, $vendor);

            return back()->with('success', __('The vendor details are updated successfully.'));
        }
        return back()->with('error', __('Permission denied'));
    }

    public function destroy(Vendor $vendor)
    {
        if(Auth::user()->can('delete-vendors')){
            if ((int) $vendor->created_by !== (int) creatorId()) {
                return back()->with('error', __('Permission denied'));
            }

            DestroyVendor::dispatch($vendor);
            $vendor->delete();
            return back()->with('success', __('The vendor has been deleted.'));
        }
        return back()->with('error', __('Permission denied'));
    }

    private function hasCriticalFiscalChange(Vendor $vendor, array $validated): bool
    {
        return $this->changedCriticalFiscalFields($vendor, $validated) !== [];
    }

    /**
     * @return array<int, string>
     */
    private function changedCriticalFiscalFields(Vendor $vendor, array $validated): array
    {
        $criticalFields = $this->criticalFiscalFields();
        $changedFields = [];

        foreach ($criticalFields as $field) {
            $currentValue = $vendor->getAttribute($field);
            $incomingValue = array_key_exists($field, $validated) ? $validated[$field] : $currentValue;

            if ($this->normalizeCriticalFiscalValue($field, $incomingValue) !== $this->normalizeCriticalFiscalValue($field, $currentValue)) {
                $changedFields[] = $field;
            }
        }

        return $changedFields;
    }

    /**
     * @return array<int, string>
     */
    private function criticalFiscalFields(): array
    {
        return [
            'tax_number',
            'company_name',
            'fiscal_residency_status',
            'vendor_type',
            'fiscal_country',
            'vat_regime',
            'supply_type',
            'payment_currency_code',
            'foreign_tax_number',
            'withholding_tax_applicable',
            'reverse_charge_applicable',
            'adt_eligible',
            'adt_country',
        ];
    }

    private function normalizeCriticalFiscalValue(string $field, mixed $value): string
    {
        return match ($field) {
            'tax_number', 'foreign_tax_number' => MozambiqueTaxNumber::normalize(is_string($value) ? $value : null) ?: '',
            'withholding_tax_applicable', 'reverse_charge_applicable', 'adt_eligible' => filter_var($value, FILTER_VALIDATE_BOOL) ? '1' : '0',
            'payment_currency_code' => strtoupper(trim((string) $value)),
            default => strtolower(trim((string) $value)),
        };
    }

    private function vendorHasFiscalHistory(Vendor $vendor): bool
    {
        if (!Schema::hasTable('purchase_invoices') || $vendor->user_id === null) {
            return false;
        }

        $query = PurchaseInvoice::query()
            ->where('created_by', (int) $vendor->created_by)
            ->where('vendor_id', (int) $vendor->user_id);

        if (Schema::hasColumn('purchase_invoices', 'status')) {
            $query->whereNotIn('status', ['draft']);
        }

        return $query->exists();
    }

    private function buildFiscalAuditSnapshot(Vendor $vendor, array $validated): array
    {
        return [
            'old' => [
                'company_name' => $vendor->company_name,
                'tax_number' => $vendor->tax_number,
                'fiscal_residency_status' => $vendor->fiscal_residency_status,
                'vendor_type' => $vendor->vendor_type,
                'fiscal_country' => $vendor->fiscal_country,
                'vat_regime' => $vendor->vat_regime,
                'supply_type' => $vendor->supply_type,
                'payment_currency_code' => $vendor->payment_currency_code,
                'foreign_tax_number' => $vendor->foreign_tax_number,
                'adt_eligible' => (bool) $vendor->adt_eligible,
                'adt_country' => $vendor->adt_country,
                'reverse_charge_applicable' => (bool) $vendor->reverse_charge_applicable,
            ],
            'new' => [
                'company_name' => $validated['company_name'] ?? $vendor->company_name,
                'tax_number' => MozambiqueTaxNumber::normalize($validated['tax_number'] ?? $vendor->tax_number),
                'fiscal_residency_status' => $validated['fiscal_residency_status'] ?? $vendor->fiscal_residency_status,
                'vendor_type' => $validated['vendor_type'] ?? $vendor->vendor_type,
                'fiscal_country' => $validated['fiscal_country'] ?? $vendor->fiscal_country,
                'vat_regime' => $validated['vat_regime'] ?? $vendor->vat_regime,
                'supply_type' => $validated['supply_type'] ?? $vendor->supply_type,
                'payment_currency_code' => isset($validated['payment_currency_code'])
                    ? strtoupper((string) $validated['payment_currency_code'])
                    : $vendor->payment_currency_code,
                'foreign_tax_number' => $validated['foreign_tax_number'] ?? $vendor->foreign_tax_number,
                'adt_eligible' => (bool) ($validated['adt_eligible'] ?? $vendor->adt_eligible),
                'adt_country' => $validated['adt_country'] ?? $vendor->adt_country,
                'reverse_charge_applicable' => (bool) ($validated['reverse_charge_applicable'] ?? $vendor->reverse_charge_applicable),
            ],
        ];
    }

    private function recordFiscalOverrideAudit(Vendor $vendor, array $oldValues, array $newValues, string $reason): void
    {
        AuditTrail::query()->create([
            'company_id' => (int) $vendor->created_by,
            'user_id' => Auth::id(),
            'event' => 'fiscal_override',
            'auditable_type' => Vendor::class,
            'auditable_id' => (int) $vendor->id,
            'route' => request()?->route()?->getName(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changes' => [
                'fiscal_identity_lock_reason' => $reason,
                'fields' => array_keys(array_diff_assoc($newValues, $oldValues)),
            ],
        ]);
    }

    private function syncVendorAttributes(Vendor $vendor, array $validated, bool $isCreate): void
    {
        $sameAsBilling = (bool) data_get($validated, 'same_as_billing', false);
        $billingAddress = data_get($validated, 'billing_address', []);
        $shippingAddress = $sameAsBilling
            ? $billingAddress
            : (data_get($validated, 'shipping_address') ?: $billingAddress);
        $paymentCurrencyCode = $this->resolveVendorPaymentCurrencyCode($validated, $vendor, $isCreate);

        $this->setVendorAttributeIfColumnExists($vendor, 'user_id', $this->normalizeNullableInteger(data_get($validated, 'user_id')));
        $this->setVendorAttributeIfColumnExists($vendor, 'company_name', data_get($validated, 'company_name'));
        $this->setVendorAttributeIfColumnExists($vendor, 'contact_person_name', data_get($validated, 'contact_person_name'));
        $this->setVendorAttributeIfColumnExists($vendor, 'contact_person_email', data_get($validated, 'contact_person_email'));
        $this->setVendorAttributeIfColumnExists($vendor, 'contact_person_mobile', data_get($validated, 'contact_person_mobile'));
        $this->setVendorAttributeIfColumnExists($vendor, 'tax_number', MozambiqueTaxNumber::normalize(data_get($validated, 'tax_number')));
        $this->setVendorAttributeIfColumnExists(
            $vendor,
            'fiscal_residency_status',
            data_get($validated, 'fiscal_residency_status', $isCreate ? 'resident' : ($vendor->fiscal_residency_status ?? 'resident'))
        );
        $this->setVendorAttributeIfColumnExists($vendor, 'vendor_type', data_get($validated, 'vendor_type', $vendor->vendor_type ?? null));
        $this->setVendorAttributeIfColumnExists($vendor, 'fiscal_country', data_get($validated, 'fiscal_country', $vendor->fiscal_country ?? null));
        $this->setVendorAttributeIfColumnExists($vendor, 'vat_regime', data_get($validated, 'vat_regime', $vendor->vat_regime ?? null));
        $this->setVendorAttributeIfColumnExists($vendor, 'supply_type', data_get($validated, 'supply_type', $vendor->supply_type ?? null));
        $this->setVendorAttributeIfColumnExists($vendor, 'payment_currency_code', $paymentCurrencyCode);
        $this->setVendorAttributeIfColumnExists($vendor, 'foreign_tax_number', data_get($validated, 'foreign_tax_number', $vendor->foreign_tax_number ?? null));
        $this->setVendorAttributeIfColumnExists(
            $vendor,
            'withholding_tax_applicable',
            (bool) data_get($validated, 'withholding_tax_applicable', $vendor->withholding_tax_applicable ?? false)
        );
        $this->setVendorAttributeIfColumnExists(
            $vendor,
            'reverse_charge_applicable',
            (bool) data_get($validated, 'reverse_charge_applicable', $vendor->reverse_charge_applicable ?? false)
        );
        $this->setVendorAttributeIfColumnExists($vendor, 'adt_eligible', (bool) data_get($validated, 'adt_eligible', $vendor->adt_eligible ?? false));
        $this->setVendorAttributeIfColumnExists($vendor, 'adt_country', data_get($validated, 'adt_country', $vendor->adt_country ?? null));
        $this->setVendorAttributeIfColumnExists($vendor, 'compliance_documents', data_get($validated, 'compliance_documents', $vendor->compliance_documents ?? null));
        $this->setVendorAttributeIfColumnExists($vendor, 'payment_terms', data_get($validated, 'payment_terms', $vendor->payment_terms ?? null));
        $this->setVendorAttributeIfColumnExists($vendor, 'billing_address', $billingAddress);
        $this->setVendorAttributeIfColumnExists($vendor, 'shipping_address', $shippingAddress);
        $this->setVendorAttributeIfColumnExists($vendor, 'same_as_billing', $sameAsBilling);
        $this->setVendorAttributeIfColumnExists($vendor, 'notes', data_get($validated, 'notes', $vendor->notes ?? null));

        if ($isCreate) {
            $this->setVendorAttributeIfColumnExists($vendor, 'creator_id', Auth::id());
            $this->setVendorAttributeIfColumnExists($vendor, 'created_by', creatorId());
            $this->setVendorAttributeIfColumnExists($vendor, 'is_active', true);
        }

        $this->setVendorAttributeIfColumnExists($vendor, 'primary_email', data_get($validated, 'contact_person_email', $vendor->contact_person_email ?? null));
        $this->setVendorAttributeIfColumnExists($vendor, 'primary_mobile', data_get($validated, 'contact_person_mobile', $vendor->contact_person_mobile ?? null));
        $this->setVendorAttributeIfColumnExists($vendor, 'currency_code', $paymentCurrencyCode ?? 'MZN');
    }

    private function resolveVendorPaymentCurrencyCode(array $validated, Vendor $vendor, bool $isCreate): ?string
    {
        if (array_key_exists('payment_currency_code', $validated) && $validated['payment_currency_code'] !== null && $validated['payment_currency_code'] !== '') {
            return strtoupper((string) $validated['payment_currency_code']);
        }

        return $isCreate
            ? null
            : ($vendor->payment_currency_code ?? $vendor->currency_code ?? null);
    }

    private function normalizeNullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function setVendorAttributeIfColumnExists(Vendor $vendor, string $column, mixed $value): void
    {
        if ($this->vendorColumnExists($column)) {
            $vendor->setAttribute($column, $value);
        }
    }

    private function vendorColumnExists(string $column): bool
    {
        if (!array_key_exists($column, self::$vendorColumnCache)) {
            self::$vendorColumnCache[$column] = Schema::hasColumn('vendors', $column);
        }

        return self::$vendorColumnCache[$column];
    }
}
