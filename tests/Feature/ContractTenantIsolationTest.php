<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Contract\Models\Contract;
use Workdo\Contract\Models\ContractType;

class ContractTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_store_rejects_foreign_assignee_and_contract_type(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['create-contracts']);

        $staffA = $this->makeStaff($companyA, 'A Staff');
        $typeA = $this->makeContractType($companyA, 'A Type');

        $staffB = $this->makeStaff($companyB, 'B Staff');
        $typeB = $this->makeContractType($companyB, 'B Type');

        $response = $this->actingAs($companyA)->post(route('contract.store'), [
            ...$this->baseContractPayload(),
            'subject' => 'Contrato Cross Tenant',
            'user_id' => $staffB->id,
            'type_id' => $typeB->id,
        ]);

        $response->assertSessionHasErrors(['user_id', 'type_id']);
        $this->assertDatabaseCount('contracts', 0);

        $okResponse = $this->actingAs($companyA)->post(route('contract.store'), [
            ...$this->baseContractPayload(),
            'subject' => 'Contrato Tenant A',
            'user_id' => $staffA->id,
            'type_id' => $typeA->id,
        ]);

        $okResponse->assertRedirect(route('contract.index'));
        $this->assertDatabaseHas('contracts', [
            'subject' => 'Contrato Tenant A',
            'created_by' => $companyA->id,
        ]);
    }

    public function test_cannot_update_foreign_company_contract_even_with_manage_any_permission(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['edit-contracts', 'manage-any-contracts']);

        $staffA = $this->makeStaff($companyA, 'A Staff');
        $typeA = $this->makeContractType($companyA, 'A Type');

        $staffB = $this->makeStaff($companyB, 'B Staff');
        $typeB = $this->makeContractType($companyB, 'B Type');
        $foreignContract = $this->makeContract($companyB, $staffB, $companyB, $typeB, 'Contrato Empresa B');

        $response = $this->actingAs($companyA)->put(route('contract.update', $foreignContract->id), [
            ...$this->baseContractPayload(),
            'subject' => 'Tentativa Atualizacao Indevida',
            'user_id' => $staffA->id,
            'type_id' => $typeA->id,
        ]);

        $response->assertRedirect(route('contract.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('contracts', [
            'id' => $foreignContract->id,
            'subject' => 'Contrato Empresa B',
            'created_by' => $companyB->id,
        ]);
    }

    public function test_manage_own_contracts_cannot_edit_unowned_contract_in_same_company(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-contracts', 'manage-own-contracts']);

        $staffOwner = $this->makeStaff($company, 'Owner Staff');
        $staffOther = $this->makeStaff($company, 'Other Staff');
        $type = $this->makeContractType($company, 'Main Type');

        $unownedContract = $this->makeContract($company, $staffOther, $staffOther, $type, 'Contrato Staff Other');

        $response = $this->actingAs($company)->put(route('contract.update', $unownedContract->id), [
            ...$this->baseContractPayload(),
            'subject' => 'Tentativa de Editar Contrato Nao Proprio',
            'user_id' => $staffOwner->id,
            'type_id' => $type->id,
        ]);

        $response->assertRedirect(route('contract.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('contracts', [
            'id' => $unownedContract->id,
            'subject' => 'Contrato Staff Other',
        ]);
    }

    public function test_cannot_destroy_or_duplicate_foreign_company_contract(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, [
            'delete-contracts',
            'duplicate-contracts',
            'manage-any-contracts',
        ]);

        $staffA = $this->makeStaff($companyA, 'A Staff');
        $typeA = $this->makeContractType($companyA, 'A Type');

        $staffB = $this->makeStaff($companyB, 'B Staff');
        $typeB = $this->makeContractType($companyB, 'B Type');
        $foreignContract = $this->makeContract($companyB, $staffB, $companyB, $typeB, 'Contrato B Isolado');

        $deleteResponse = $this->actingAs($companyA)->delete(route('contract.destroy', $foreignContract->id));
        $deleteResponse->assertRedirect(route('contract.index'));
        $deleteResponse->assertSessionHas('error');
        $this->assertDatabaseHas('contracts', [
            'id' => $foreignContract->id,
            'subject' => 'Contrato B Isolado',
        ]);

        $duplicateResponse = $this->actingAs($companyA)->post(route('contract.duplicate', $foreignContract->id), [
            ...$this->baseContractPayload(),
            'subject' => 'Copia Indevida',
            'user_id' => $staffA->id,
            'type_id' => $typeA->id,
        ]);

        $duplicateResponse->assertRedirect(route('contract.index'));
        $duplicateResponse->assertSessionHas('error');
        $this->assertSame(1, Contract::query()->count());
    }

    private function baseContractPayload(): array
    {
        return [
            'subject' => 'Contrato Teste',
            'value' => 10000,
            'start_date' => '2026-06-01',
            'end_date' => '2026-12-31',
            'description' => 'Teste',
            'status' => 'pending',
            'is_labour_contract' => false,
            'legal_contract_type' => null,
            'fixed_term_justification' => null,
            'probation_category' => null,
            'legal_notes' => null,
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

    private function makeStaff(User $company, string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'type' => 'staff',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);
    }

    private function makeContractType(User $company, string $name): ContractType
    {
        return ContractType::query()->create([
            'name' => $name,
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeContract(User $company, User $assignee, User $creator, ContractType $type, string $subject): Contract
    {
        return Contract::query()->create([
            'subject' => $subject,
            'value' => 9000,
            'type_id' => $type->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-10-01',
            'description' => 'Contrato para teste de isolamento',
            'status' => 'pending',
            'source_type' => 'contract',
            'user_id' => $assignee->id,
            'creator_id' => $creator->id,
            'created_by' => $company->id,
        ]);
    }

    private function grantPermissions(User $user, array $permissions): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'add_on' => 'contract',
                    'module' => 'contract',
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

