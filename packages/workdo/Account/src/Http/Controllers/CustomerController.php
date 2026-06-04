<?php

namespace Workdo\Account\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Support\MozambiqueTaxNumber;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Workdo\Account\Models\Customer;
use Workdo\Account\Http\Requests\StoreCustomerRequest;
use Workdo\Account\Http\Requests\UpdateCustomerRequest;
use Workdo\Account\Events\CreateCustomer;
use Workdo\Account\Events\UpdateCustomer;
use Workdo\Account\Events\DestroyCustomer;

class CustomerController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-customers')){
            $customers = Customer::query()
                ->with('user:id,name,avatar,is_disable')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-customers')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-customers')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('company_name'), fn($q) => $q->where('company_name', 'like', '%' . request('company_name') . '%'))
                ->when(request('customer_code'), fn($q) => $q->where('customer_code', 'like', '%' . request('customer_code') . '%'))
                ->when(request('tax_number'), fn($q) => $q->where('tax_number', 'like', '%' . request('tax_number') . '%'))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            $users = User::where('type', 'client')
                ->where('created_by', creatorId())
                ->whereNotIn('id', Customer::pluck('user_id')->filter())
                ->select('id', 'name', 'email', 'mobile_no')
                ->get();

            return Inertia::render('Account/Customers/Index', [
                'customers' => $customers,
                'users' => $users,
            ]);
        }
        return back()->with('error', __('Permission denied'));
    }

    public function store(StoreCustomerRequest $request)
    {
        if(Auth::user()->can('create-customers')){
            $validated = $request->validated();

            $customer = new Customer();
            $customer->user_id = $validated['user_id'] ?? null;
            $customer->company_name = $validated['company_name'];
            $customer->contact_person_name = $validated['contact_person_name'];
            $customer->contact_person_email = $validated['contact_person_email'] ?? null;
            $customer->contact_person_mobile = $validated['contact_person_mobile'] ?? null;
            $customer->tax_number = MozambiqueTaxNumber::normalize($validated['tax_number'] ?? null);
            $customer->fiscal_residency_status = $validated['fiscal_residency_status'] ?? 'resident';
            $customer->customer_type = $validated['customer_type'] ?? null;
            $customer->fiscal_country = $validated['fiscal_country'] ?? null;
            $customer->vat_regime = $validated['vat_regime'] ?? null;
            $customer->operation_type = $validated['operation_type'] ?? null;
            $customer->billing_currency_code = isset($validated['billing_currency_code'])
                ? strtoupper((string) $validated['billing_currency_code'])
                : null;
            $customer->accounting_account_code = $validated['accounting_account_code'] ?? null;
            $customer->payment_terms = $validated['payment_terms'] ?? null;
            $customer->billing_address = $validated['billing_address'];
            $customer->shipping_address = $validated['same_as_billing'] ? $validated['billing_address'] : $validated['shipping_address'];
            $customer->same_as_billing = $validated['same_as_billing'] ?? false;
            $customer->notes = $validated['notes'] ?? null;
            $customer->creator_id = Auth::id();
            $customer->created_by = creatorId();
            $customer->save();

            CreateCustomer::dispatch($request, $customer);

            return redirect()->route('account.customers.index')->with('success', __('The customer has been created successfully.'));
        }
        return redirect()->route('account.customers.index')->with('error', __('Permission denied'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        if(Auth::user()->can('edit-customers')){
            if ((int) $customer->created_by !== (int) creatorId()) {
                return back()->with('error', __('Permission denied'));
            }

            $validated = $request->validated();
            $hasFiscalHistory = $this->customerHasFiscalHistory($customer);
            $criticalFiscalChange = $this->hasCriticalFiscalChange($customer, $validated);
            $fiscalOverrideSnapshot = $criticalFiscalChange
                ? $this->buildFiscalAuditSnapshot($customer, $validated)
                : null;

            $customer->company_name = $validated['company_name'];
            $customer->contact_person_name = $validated['contact_person_name'];
            $customer->contact_person_email = $validated['contact_person_email'] ?? null;
            $customer->contact_person_mobile = $validated['contact_person_mobile'] ?? null;
            $customer->tax_number = MozambiqueTaxNumber::normalize($validated['tax_number'] ?? null);
            $customer->fiscal_residency_status = $validated['fiscal_residency_status'] ?? $customer->fiscal_residency_status ?? 'resident';
            $customer->customer_type = $validated['customer_type'] ?? $customer->customer_type;
            $customer->fiscal_country = $validated['fiscal_country'] ?? $customer->fiscal_country;
            $customer->vat_regime = $validated['vat_regime'] ?? $customer->vat_regime;
            $customer->operation_type = $validated['operation_type'] ?? $customer->operation_type;
            $customer->billing_currency_code = isset($validated['billing_currency_code'])
                ? strtoupper((string) $validated['billing_currency_code'])
                : $customer->billing_currency_code;
            $customer->accounting_account_code = $validated['accounting_account_code'] ?? $customer->accounting_account_code;
            $customer->payment_terms = $validated['payment_terms'] ?? null;
            $customer->billing_address = $validated['billing_address'];
            $customer->shipping_address = $validated['same_as_billing'] ? $validated['billing_address'] : $validated['shipping_address'];
            $customer->same_as_billing = $validated['same_as_billing'] ?? false;
            $customer->notes = $validated['notes'] ?? null;

            if ($hasFiscalHistory && $customer->fiscal_identity_locked_at === null) {
                $customer->fiscal_identity_locked_at = now();
                if (empty($customer->fiscal_identity_lock_reason)) {
                    $customer->fiscal_identity_lock_reason = 'fiscal_documents_issued';
                }
            }

            if ($criticalFiscalChange && !empty($validated['fiscal_identity_lock_reason'])) {
                $customer->fiscal_identity_lock_reason = trim((string) $validated['fiscal_identity_lock_reason']);
                if ($customer->fiscal_identity_locked_at === null) {
                    $customer->fiscal_identity_locked_at = now();
                }
            }

            $customer->save();

            if ($criticalFiscalChange && !empty($validated['fiscal_identity_lock_reason'])) {
                $this->recordFiscalOverrideAudit(
                    $customer,
                    $fiscalOverrideSnapshot['old'] ?? [],
                    $fiscalOverrideSnapshot['new'] ?? [],
                    trim((string) $validated['fiscal_identity_lock_reason'])
                );
            }

            UpdateCustomer::dispatch($request, $customer);

            return back()->with('success', __('The customer details are updated successfully.'));
        }
        return back()->with('error', __('Permission denied'));
    }

    public function destroy(Customer $customer)
    {
        if(Auth::user()->can('delete-customers')){
            if ((int) $customer->created_by !== (int) creatorId()) {
                return back()->with('error', __('Permission denied'));
            }

            DestroyCustomer::dispatch($customer);
            $customer->delete();
            return back()->with('success', __('The customer has been deleted.'));
        }
        return back()->with('error', __('Permission denied'));
    }

    private function hasCriticalFiscalChange(Customer $customer, array $validated): bool
    {
        return $this->changedCriticalFiscalFields($customer, $validated) !== [];
    }

    /**
     * @return array<int, string>
     */
    private function changedCriticalFiscalFields(Customer $customer, array $validated): array
    {
        $criticalFields = $this->criticalFiscalFields();
        $changedFields = [];

        foreach ($criticalFields as $field) {
            $currentValue = $customer->getAttribute($field);
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
            'customer_type',
            'fiscal_country',
            'vat_regime',
            'operation_type',
            'billing_currency_code',
        ];
    }

    private function normalizeCriticalFiscalValue(string $field, mixed $value): string
    {
        return match ($field) {
            'tax_number' => MozambiqueTaxNumber::normalize(is_string($value) ? $value : null) ?: '',
            'billing_currency_code' => strtoupper(trim((string) $value)),
            default => strtolower(trim((string) $value)),
        };
    }

    private function customerHasFiscalHistory(Customer $customer): bool
    {
        if (!Schema::hasTable('sales_invoices') || $customer->user_id === null) {
            return false;
        }

        $query = SalesInvoice::query()
            ->where('created_by', (int) $customer->created_by)
            ->where('customer_id', (int) $customer->user_id);

        if (Schema::hasColumn('sales_invoices', 'status')) {
            $query->whereNotIn('status', ['draft']);
        }

        return $query->exists();
    }

    private function buildFiscalAuditSnapshot(Customer $customer, array $validated): array
    {
        return [
            'old' => [
                'company_name' => $customer->company_name,
                'tax_number' => $customer->tax_number,
                'fiscal_residency_status' => $customer->fiscal_residency_status,
                'customer_type' => $customer->customer_type,
                'fiscal_country' => $customer->fiscal_country,
                'vat_regime' => $customer->vat_regime,
                'operation_type' => $customer->operation_type,
                'billing_currency_code' => $customer->billing_currency_code,
            ],
            'new' => [
                'company_name' => $validated['company_name'] ?? $customer->company_name,
                'tax_number' => MozambiqueTaxNumber::normalize($validated['tax_number'] ?? $customer->tax_number),
                'fiscal_residency_status' => $validated['fiscal_residency_status'] ?? $customer->fiscal_residency_status,
                'customer_type' => $validated['customer_type'] ?? $customer->customer_type,
                'fiscal_country' => $validated['fiscal_country'] ?? $customer->fiscal_country,
                'vat_regime' => $validated['vat_regime'] ?? $customer->vat_regime,
                'operation_type' => $validated['operation_type'] ?? $customer->operation_type,
                'billing_currency_code' => isset($validated['billing_currency_code'])
                    ? strtoupper((string) $validated['billing_currency_code'])
                    : $customer->billing_currency_code,
            ],
        ];
    }

    private function recordFiscalOverrideAudit(Customer $customer, array $oldValues, array $newValues, string $reason): void
    {
        AuditTrail::query()->create([
            'company_id' => (int) $customer->created_by,
            'user_id' => Auth::id(),
            'event' => 'fiscal_override',
            'auditable_type' => Customer::class,
            'auditable_id' => (int) $customer->id,
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
}
