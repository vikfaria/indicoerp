<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Pos\Models\Pos;

class PosFiscalComplianceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_pos_fiscal_status_and_cancellation_rules_are_enforced(): void
    {
        if (
            !Schema::hasTable('pos')
            || !Schema::hasColumn('pos', 'fiscal_submission_status')
            || !Schema::hasColumn('pos', 'is_cancelled')
        ) {
            $this->markTestSkipped('POS fiscal columns are not available in this test environment.');
        }

        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-pos-orders']);

        $sale = $this->createPosSale($company, [
            'status' => 'completed',
            'fiscal_submission_status' => 'pending',
        ]);

        $this->actingAs($company)
            ->post(route('pos.fiscal-status', $sale), [
                'status' => 'submitted',
            ])
            ->assertSessionHasErrors('reference');

        $this->actingAs($company)
            ->post(route('pos.fiscal-status', $sale), [
                'status' => 'submitted',
                'reference' => 'EDC-POS-001',
            ])
            ->assertSessionHasNoErrors();

        $sale->refresh();
        $this->assertSame('submitted', $sale->fiscal_submission_status);
        $this->assertSame('EDC-POS-001', $sale->fiscal_submission_reference);
        $this->assertNotNull($sale->fiscal_submitted_at);

        $this->actingAs($company)
            ->post(route('pos.fiscal-status', $sale), [
                'status' => 'validated',
                'reference' => 'AT-POS-VAL-001',
            ])
            ->assertSessionHasNoErrors();

        $sale->refresh();
        $this->assertSame('validated', $sale->fiscal_submission_status);
        $this->assertSame('AT-POS-VAL-001', $sale->fiscal_submission_reference);
        $this->assertNotNull($sale->fiscal_validated_at);

        $this->actingAs($company)
            ->post(route('pos.cancel-fiscal', $sale), [
                'reason' => 'Anulacao sem referencia de retificacao',
            ])
            ->assertSessionHasErrors('rectification_reference');

        $this->actingAs($company)
            ->post(route('pos.cancel-fiscal', $sale), [
                'reason' => 'Anulacao fiscal por erro de emissao',
                'cancellation_reference' => 'ANU-POS-2026-01',
                'rectification_reference' => 'NC-POS-2026-01',
            ])
            ->assertSessionHasNoErrors();

        $sale->refresh();
        $this->assertTrue((bool) $sale->is_cancelled);
        $this->assertSame('cancelled', $sale->status);
        $this->assertSame('rejected', $sale->fiscal_submission_status);
        $this->assertSame('ANU-POS-2026-01', $sale->cancellation_reference);
        $this->assertSame('NC-POS-2026-01', $sale->rectification_reference);
        $this->assertNotNull($sale->cancelled_at);
    }

    public function test_pos_fiscal_update_is_denied_across_tenants(): void
    {
        if (!Schema::hasTable('pos') || !Schema::hasColumn('pos', 'fiscal_submission_status')) {
            $this->markTestSkipped('POS fiscal columns are not available in this test environment.');
        }

        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $this->grantPermissions($companyA, ['manage-pos-orders']);

        $sale = $this->createPosSale($companyB, [
            'status' => 'completed',
            'fiscal_submission_status' => 'pending',
        ]);

        $this->actingAs($companyA)
            ->post(route('pos.fiscal-status', $sale), [
                'status' => 'submitted',
                'reference' => 'EDC-BLOCK-001',
            ])
            ->assertRedirect(route('pos.orders'));

        $sale->refresh();
        $this->assertSame('pending', $sale->fiscal_submission_status);
    }

    private function makeCompany(): User
    {
        return User::factory()->create([
            'type' => 'company',
            'created_by' => null,
            'creator_id' => null,
            'email_verified_at' => now(),
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);
    }

    private function createPosSale(User $company, array $overrides = []): Pos
    {
        $payload = array_merge([
            'sale_number' => 'POS-' . uniqid(),
            'customer_id' => null,
            'warehouse_id' => null,
            'pos_date' => now()->toDateString(),
            'status' => 'completed',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ], $overrides);

        if (Schema::hasColumn('pos', 'is_cancelled') && !array_key_exists('is_cancelled', $payload)) {
            $payload['is_cancelled'] = false;
        }

        return Pos::create($payload);
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

            if (!$user->hasPermissionTo($permission)) {
                $user->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->refresh();
    }
}
