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

class ContractLabourComplianceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_fixed_term_labour_contract_requires_justification(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-contracts']);
        [$assignee, $contractType] = $this->makeContractDependencies($company);

        $response = $this->actingAs($company)->post(route('contract.store'), [
            'subject' => 'Contrato de Trabalho a Prazo',
            'user_id' => $assignee->id,
            'value' => 10000,
            'type_id' => $contractType->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-12-31',
            'description' => 'Teste legal',
            'status' => 'pending',
            'is_labour_contract' => true,
            'legal_contract_type' => 'fixed_term',
            'fixed_term_justification' => '',
        ]);

        $response->assertSessionHasErrors('fixed_term_justification');
        $this->assertDatabaseCount('contracts', 0);
    }

    public function test_can_store_labour_contract_metadata_and_resolve_presumption_flag(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-contracts']);
        [$assignee, $contractType] = $this->makeContractDependencies($company);

        $response = $this->actingAs($company)->post(route('contract.store'), [
            'subject' => 'Contrato de Trabalho Legal',
            'user_id' => $assignee->id,
            'value' => 12500,
            'type_id' => $contractType->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-12-31',
            'description' => 'Contrato laboral',
            'status' => 'pending',
            'is_labour_contract' => true,
            'legal_contract_type' => 'fixed_term',
            'fixed_term_justification' => 'Reforço sazonal da equipa de operações durante pico de procura.',
            'probation_category' => 'general',
            'legal_notes' => 'Aplicar revisão legal trimestral.',
        ]);

        $response->assertRedirect(route('contract.index'));
        $this->assertDatabaseHas('contracts', [
            'subject' => 'Contrato de Trabalho Legal',
            'is_labour_contract' => true,
            'legal_contract_type' => 'fixed_term',
            'probation_category' => 'general',
            'created_by' => $company->id,
        ]);

        $contract = Contract::query()->firstOrFail();
        $this->assertFalse($contract->presumed_indefinite_risk);

        $irregularContract = Contract::query()->create([
            'subject' => 'Contrato Irregular',
            'user_id' => $assignee->id,
            'value' => 9000,
            'type_id' => $contractType->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-09-01',
            'description' => 'Sem justificativa',
            'status' => 'pending',
            'source_type' => 'contract',
            'is_labour_contract' => true,
            'legal_contract_type' => 'fixed_term',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->assertTrue($irregularContract->fresh()->presumed_indefinite_risk);
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

    private function makeContractDependencies(User $company): array
    {
        $assignee = User::factory()->create([
            'type' => 'staff',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);

        $contractType = ContractType::query()->create([
            'name' => 'Labour Contract',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        return [$assignee, $contractType];
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
