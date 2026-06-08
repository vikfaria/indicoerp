<?php

namespace Tests\Feature;

use App\Classes\Module;
use App\Http\Middleware\PlanModuleCheck;
use App\Models\AddOn;
use App\Models\AuditTrail;
use App\Models\User;
use App\Models\UserActiveModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Helpers\AccountUtility;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\CustomerPayment;
use Workdo\Account\Models\VendorPayment;
use Workdo\Hrm\Models\Branch;

class AccountingCriticalAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    private int $companySequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        app(Module::class)->moduleCacheForget();
        $this->enableModule('Account');
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_bank_account_create_update_and_delete_routes_are_audited(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-bank-accounts', 'edit-bank-accounts', 'delete-bank-accounts', 'manage-bank-accounts']);

        $branch = Branch::query()
            ->where('created_by', $company->id)
            ->where('branch_name', 'Main Office')
            ->firstOrFail();

        $glAccount = ChartOfAccount::query()
            ->where('created_by', $company->id)
            ->where('account_code', '1030')
            ->firstOrFail();

        $createResponse = $this->actingAs($company)->post(route('account.bank-accounts.store'), $this->bankAccountPayload($branch, $glAccount, [
            'account_number' => '9876543210987654',
            'account_name' => 'Conta Auditoria',
        ]));

        $createResponse->assertSessionHasNoErrors();
        $createResponse->assertRedirect(route('account.bank-accounts.index'));

        $bankAccount = BankAccount::query()
            ->where('created_by', $company->id)
            ->where('account_number', '9876543210987654')
            ->firstOrFail();

        $updateResponse = $this->actingAs($company)->put(route('account.bank-accounts.update', $bankAccount->id), $this->bankAccountPayload($branch, $glAccount, [
            'account_number' => '9876543210987654',
            'account_name' => 'Conta Auditoria Atualizada',
            'current_balance' => 2500,
        ]));

        $updateResponse->assertSessionHasNoErrors();

        $deleteResponse = $this->actingAs($company)->delete(route('account.bank-accounts.destroy', $bankAccount->id));

        $deleteResponse->assertSessionHasNoErrors();

        $entries = AuditTrail::query()
            ->where('auditable_type', BankAccount::class)
            ->where('auditable_id', $bankAccount->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $entries);
        $this->assertSame(['created', 'updated', 'deleted'], $entries->pluck('event')->all());
        $this->assertSame('account.bank-accounts.store', $entries[0]->route);
        $this->assertSame('account.bank-accounts.update', $entries[1]->route);
        $this->assertSame('account.bank-accounts.destroy', $entries[2]->route);
        $this->assertSame('************7654', $entries[0]->new_values['account_number'] ?? null);
        $this->assertSame('Conta Auditoria Atualizada', $entries[1]->new_values['account_name'] ?? null);
        $this->assertSame(2500, (int) ($entries[1]->new_values['current_balance'] ?? 0));
        $this->assertSame('Conta Auditoria Atualizada', $entries[2]->old_values['account_name'] ?? null);
    }

    public function test_customer_payment_approval_and_clearing_routes_are_audited(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['approve-customer-payments', 'cleared-customer-payments']);
        $this->actingAs($company);

        $bankAccount = $this->makeBankAccount($company);
        $customer = $this->makeCounterpartyUser($company, 'client', 'Customer Audit');

        $payment = CustomerPayment::query()->create([
            'payment_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'bank_account_id' => $bankAccount->id,
            'branch_id' => $bankAccount->branch_id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'CP-AUD-001',
            'payment_amount' => 1250,
            'currency_code' => 'MZN',
            'amount_mzn' => 1250,
            'approval_required' => true,
            'approval_status' => 'pending',
            'approval_requested_at' => now(),
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $approveResponse = $this->actingAs($company)->patch(route('account.customer-payments.approve', $payment->id), [
            'approval_reference' => 'CP-APR-001',
        ]);

        $approveResponse->assertSessionHasNoErrors();

        $clearResponse = $this->actingAs($company)->patch(route('account.customer-payments.update-status', $payment->id), [
            'status' => 'cleared',
        ]);

        $clearResponse->assertSessionHasNoErrors();

        $payment->refresh();
        $this->assertSame('cleared', (string) $payment->status);
        $this->assertSame('approved', (string) $payment->approval_status);

        $entries = AuditTrail::query()
            ->where('auditable_type', CustomerPayment::class)
            ->where('auditable_id', $payment->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $entries);
        $this->assertSame('created', $entries[0]->event);
        $this->assertNull($entries[0]->route);
        $this->assertSame('account.customer-payments.approve', $entries[1]->route);
        $this->assertSame('approved', (string) data_get($entries[1]->changes, 'approval_status'));
        $this->assertSame('account.customer-payments.update-status', $entries[2]->route);
        $this->assertSame('cleared', (string) data_get($entries[2]->changes, 'status'));
    }

    public function test_vendor_payment_approval_and_clearing_routes_are_audited(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['approve-vendor-payments', 'cleared-vendor-payments']);
        $this->actingAs($company);

        $bankAccount = $this->makeBankAccount($company);
        $vendor = $this->makeCounterpartyUser($company, 'vendor', 'Vendor Audit');

        $payment = VendorPayment::query()->create([
            'payment_date' => now()->toDateString(),
            'vendor_id' => $vendor->id,
            'bank_account_id' => $bankAccount->id,
            'branch_id' => $bankAccount->branch_id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'VP-AUD-001',
            'payment_amount' => 1800,
            'currency_code' => 'MZN',
            'amount_mzn' => 1800,
            'financial_approval_reference' => 'FIN-VP-001',
            'approval_required' => true,
            'approval_status' => 'pending',
            'approval_requested_at' => now(),
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $approveResponse = $this->actingAs($company)->post(route('account.vendor-payments.approve', $payment->id), [
            'approval_reference' => 'VP-APR-001',
        ]);

        $approveResponse->assertSessionHasNoErrors();

        $clearResponse = $this->actingAs($company)->post(route('account.vendor-payments.update-status', $payment->id), [
            'status' => 'cleared',
        ]);

        $clearResponse->assertSessionHasNoErrors();

        $payment->refresh();
        $this->assertSame('cleared', (string) $payment->status);
        $this->assertSame('approved', (string) $payment->approval_status);
        $this->assertSame('VP-APR-001', (string) $payment->approval_reference);
        $this->assertSame('FIN-VP-001', (string) $payment->financial_approval_reference);

        $entries = AuditTrail::query()
            ->where('auditable_type', VendorPayment::class)
            ->where('auditable_id', $payment->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $entries);
        $this->assertSame('created', $entries[0]->event);
        $this->assertNull($entries[0]->route);
        $this->assertSame('account.vendor-payments.approve', $entries[1]->route);
        $this->assertSame('approved', (string) data_get($entries[1]->changes, 'approval_status'));
        $this->assertSame('account.vendor-payments.update-status', $entries[2]->route);
        $this->assertSame('cleared', (string) data_get($entries[2]->changes, 'status'));
    }

    private function makeCompany(): User
    {
        $company = User::forceCreate([
            'id' => 94000 + (++$this->companySequence),
            'name' => 'Empresa ' . (94000 + $this->companySequence),
            'email' => 'company' . (94000 + $this->companySequence) . '@example.com',
            'password' => 'password',
            'type' => 'company',
            'created_by' => null,
            'creator_id' => null,
            'email_verified_at' => now(),
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);

        AccountUtility::defaultdata($company->id);
        UserActiveModule::updateOrCreate(
            ['user_id' => $company->id, 'module' => 'Account'],
            ['module' => 'Account']
        );

        Branch::query()->firstOrCreate(
            [
                'branch_name' => 'Main Office',
                'created_by' => $company->id,
            ],
            [
                'creator_id' => $company->id,
            ]
        );

        return $company;
    }

    private function enableModule(string $module): void
    {
        AddOn::updateOrCreate(
            ['module' => $module],
            [
                'name' => $module,
                'monthly_price' => 0,
                'yearly_price' => 0,
                'is_enable' => true,
                'for_admin' => false,
                'package_name' => $module,
                'priority' => 10,
            ]
        );

        app(Module::class)->moduleCacheForget($module);
    }

    private function makeCounterpartyUser(User $company, string $type, string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'type' => $type,
            'created_by' => $company->id,
            'creator_id' => $company->id,
            'email_verified_at' => now(),
        ]);
    }

    private function makeBankAccount(User $company): BankAccount
    {
        $branch = Branch::query()
            ->where('created_by', $company->id)
            ->where('branch_name', 'Main Office')
            ->firstOrFail();

        $glAccount = ChartOfAccount::query()
            ->where('created_by', $company->id)
            ->where('account_code', '1030')
            ->firstOrFail();

        return BankAccount::query()->create([
            'account_number' => 'AUD-BANK-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'account_name' => 'Conta Operacional',
            'bank_name' => 'Banco Auditoria',
            'branch_name' => $branch->branch_name,
            'branch_id' => $branch->id,
            'account_type' => 'current',
            'opening_balance' => 0,
            'current_balance' => 0,
            'iban' => 'MZ59000100000000000000999',
            'swift_code' => 'AUDTMZMA',
            'is_active' => true,
            'gl_account_id' => $glAccount->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function bankAccountPayload(Branch $branch, ChartOfAccount $glAccount, array $overrides = []): array
    {
        return array_merge([
            'account_number' => '9876543210987654',
            'account_name' => 'Conta Auditoria',
            'bank_name' => 'Banco Auditoria',
            'branch_id' => $branch->id,
            'branch_name' => $branch->branch_name,
            'account_type' => 'current',
            'opening_balance' => 1000,
            'current_balance' => 1000,
            'iban' => 'MZ59000100000000000000123',
            'swift_code' => 'AUDTMZMA',
            'routing_number' => null,
            'is_active' => true,
            'is_electronic_money_account' => false,
            'electronic_money_limit_exempt_for_enterprise' => false,
            'gl_account_id' => $glAccount->id,
        ], $overrides);
    }

    private function grantPermissions(User $user, array $permissions): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'add_on' => 'general',
                    'module' => 'tests',
                    'label' => $permissionName,
                ]
            );

            if (! $user->hasPermissionTo($permission)) {
                $user->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->refresh();
    }
}
