<?php

namespace Tests\Feature;

use App\Classes\Module;
use App\Http\Middleware\PlanModuleCheck;
use App\Models\AddOn;
use App\Models\User;
use App\Models\UserActiveModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AssistantActivationFeatureGateTest extends TestCase
{
    use RefreshDatabase;

    private int $companySequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        app(Module::class)->moduleCacheForget();
        $this->enableModule('ProductService');
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_stock_route_redirects_with_a_clear_feature_gate_payload_when_warehouse_setup_is_missing(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-stock']);

        $response = $this->from(route('product-service.stock.index'))
            ->actingAs($company)
            ->post(route('product-service.stock.store'), [
                'product_id' => 999,
                'warehouse_id' => 999,
                'quantity' => 3,
            ]);

        $response->assertRedirect(route('product-service.stock.index'));
        $response->assertSessionHas('error');
        $response->assertSessionHas('feature_gate.key', 'inventory.stock.manage');
        $response->assertSessionHas('feature_gate.state', 'locked');
        $response->assertSessionHas('feature_gate.reasons.0', 'config_missing');
        $response->assertSessionHas('feature_gate.config.missing.0', 'warehouses');
    }

    public function test_stock_route_returns_a_json_feature_gate_payload_when_requested_as_json(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-stock']);

        $response = $this->actingAs($company)
            ->postJson(route('product-service.stock.store'), [
                'product_id' => 999,
                'warehouse_id' => 999,
                'quantity' => 3,
            ]);

        $response->assertStatus(403);
        $response->assertJsonPath('feature_gate.key', 'inventory.stock.manage');
        $response->assertJsonPath('feature_gate.state', 'locked');
        $response->assertJsonPath('feature_gate.reasons.0', 'config_missing');
        $response->assertJsonPath('feature_gate.config.missing.0', 'warehouses');
    }

    public function test_warehouse_route_is_available_with_product_service_without_a_fake_warehouse_module(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, [
            'manage-warehouses',
            'manage-any-warehouses',
        ]);

        $response = $this->actingAs($company)
            ->get(route('warehouses.index'));

        $response->assertOk();
        $response->assertSessionMissing('feature_gate');
    }

    private function makeCompany(): User
    {
        $company = User::forceCreate([
            'id' => 96000 + (++$this->companySequence),
            'name' => 'Empresa ' . (96000 + $this->companySequence),
            'email' => 'company' . (96000 + $this->companySequence) . '@example.com',
            'password' => 'password',
            'type' => 'company',
            'created_by' => null,
            'creator_id' => null,
            'email_verified_at' => now(),
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);

        UserActiveModule::updateOrCreate(
            ['user_id' => $company->id, 'module' => 'ProductService'],
            ['module' => 'ProductService']
        );

        return $company;
    }

    private function grantPermissions(User $user, array $permissions): void
    {
        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'add_on' => 'general',
                    'module' => 'tests',
                    'label' => $permissionName,
                ]
            );

            $user->givePermissionTo($permission);
        }
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
}
