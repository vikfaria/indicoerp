<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Models\CreditNote;
use Workdo\Account\Models\DebitNote;

class AccountNotePrintAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_credit_and_debit_note_print_are_denied_across_tenants(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $customerB = $this->makeClient($companyB, 'Cliente Externo');
        $vendorB = $this->makeVendor($companyB, 'Fornecedor Externo');

        $creditNote = CreditNote::create([
            'credit_note_number' => 'NC-2026-04-700',
            'credit_note_date' => now()->toDateString(),
            'customer_id' => $customerB->id,
            'reason' => 'foreign credit note',
            'status' => 'approved',
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'applied_amount' => 0,
            'balance_amount' => 100,
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $debitNote = DebitNote::create([
            'debit_note_number' => 'ND-2026-04-700',
            'debit_note_date' => now()->toDateString(),
            'vendor_id' => $vendorB->id,
            'reason' => 'foreign debit note',
            'status' => 'approved',
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'applied_amount' => 0,
            'balance_amount' => 100,
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $this->grantPermissions($companyA, [
            'view-credit-notes',
            'manage-credit-notes',
            'manage-any-credit-notes',
            'view-debit-notes',
            'manage-debit-notes',
            'manage-any-debit-notes',
        ]);

        $this->actingAs($companyA)
            ->get(route('account.credit-notes.print', $creditNote))
            ->assertRedirect(route('account.credit-notes.index'));

        $this->actingAs($companyA)
            ->get(route('account.debit-notes.print', $debitNote))
            ->assertRedirect(route('account.debit-notes.index'));
    }

    public function test_credit_and_debit_note_print_are_available_for_same_tenant(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeClient($company, 'Cliente Local');
        $vendor = $this->makeVendor($company, 'Fornecedor Local');

        $creditNote = CreditNote::create([
            'credit_note_number' => 'NC-2026-04-050',
            'credit_note_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'reason' => 'local credit note',
            'status' => 'approved',
            'subtotal' => 90,
            'tax_amount' => 10,
            'discount_amount' => 0,
            'total_amount' => 100,
            'applied_amount' => 0,
            'balance_amount' => 100,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $debitNote = DebitNote::create([
            'debit_note_number' => 'ND-2026-04-050',
            'debit_note_date' => now()->toDateString(),
            'vendor_id' => $vendor->id,
            'reason' => 'local debit note',
            'status' => 'approved',
            'subtotal' => 90,
            'tax_amount' => 10,
            'discount_amount' => 0,
            'total_amount' => 100,
            'applied_amount' => 0,
            'balance_amount' => 100,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->grantPermissions($company, [
            'view-credit-notes',
            'manage-credit-notes',
            'manage-own-credit-notes',
            'view-debit-notes',
            'manage-debit-notes',
            'manage-own-debit-notes',
        ]);

        $this->actingAs($company)
            ->get(route('account.credit-notes.print', $creditNote), $this->inertiaHeaders())
            ->assertOk()
            ->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'Account/CreditNotes/Print')
            ->assertJsonPath('props.creditNote.credit_note_number', 'NC-2026-04-050');

        $this->actingAs($company)
            ->get(route('account.debit-notes.print', $debitNote), $this->inertiaHeaders())
            ->assertOk()
            ->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'Account/DebitNotes/Print')
            ->assertJsonPath('props.debitNote.debit_note_number', 'ND-2026-04-050');
    }

    private function inertiaHeaders(): array
    {
        return [
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(Request::create('/')) ?? '',
        ];
    }

    private function makeCompany(): User
    {
        return User::factory()->create([
            'type' => 'company',
            'created_by' => null,
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);
    }

    private function makeClient(User $company, string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'type' => 'client',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);
    }

    private function makeVendor(User $company, string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'type' => 'vendor',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);
    }

    private function grantPermissions(User $user, array $permissions): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'add_on' => 'general',
                    'module' => 'account',
                    'label' => $permissionName,
                ]
            );

            if (!$user->hasPermissionTo($permission)) {
                $user->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->refresh();
    }
}
