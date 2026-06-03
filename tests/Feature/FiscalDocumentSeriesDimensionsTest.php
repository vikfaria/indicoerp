<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Observers\FiscalDocumentObserver;
use App\Models\FiscalDocumentSeries;
use App\Models\FiscalDocumentType;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FiscalDocumentSeriesDimensionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_series_store_accepts_user_terminal_and_regime_dimensions(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);

        $assignedUser = User::factory()->create([
            'type' => 'staff',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);

        $docType = FiscalDocumentType::query()->create([
            'code' => 'FT',
            'name' => 'Factura',
            'saft_document_type' => 'FT',
            'category' => 'sales',
            'requires_hash' => true,
            'requires_series' => true,
            'is_credit_document' => false,
            'is_active' => true,
        ]);

        $this->actingAs($company)
            ->post(route('sce.fiscal.series.store'), [
                'fiscal_document_type_id' => $docType->id,
                'series_code' => 'USR1',
                'fiscal_year' => (int) date('Y'),
                'assigned_user_id' => $assignedUser->id,
                'terminal_code' => 'pos-01',
                'fiscal_regime_code' => 'normal',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('fiscal_document_series', [
            'company_id' => $company->id,
            'fiscal_document_type_id' => $docType->id,
            'series_code' => 'USR1',
            'assigned_user_id' => $assignedUser->id,
            'terminal_code' => 'POS-01',
            'fiscal_regime_code' => 'NORMAL',
        ]);
    }

    public function test_series_resolution_prefers_user_assigned_series_when_available(): void
    {
        $company = $this->makeCompany();

        $docType = FiscalDocumentType::query()->create([
            'code' => 'FT',
            'name' => 'Factura',
            'saft_document_type' => 'FT',
            'category' => 'sales',
            'requires_hash' => true,
            'requires_series' => true,
            'is_credit_document' => false,
            'is_active' => true,
        ]);

        $year = (int) date('Y');
        $generalSeries = FiscalDocumentSeries::query()->create([
            'company_id' => $company->id,
            'fiscal_document_type_id' => $docType->id,
            'series_code' => 'GEN',
            'fiscal_year' => $year,
            'assigned_user_id' => null,
            'terminal_code' => null,
            'fiscal_regime_code' => null,
            'last_sequence' => 0,
            'is_active' => true,
            'valid_from' => "{$year}-01-01",
            'valid_to' => "{$year}-12-31",
            'created_by' => $company->id,
        ]);

        $userSeries = FiscalDocumentSeries::query()->create([
            'company_id' => $company->id,
            'fiscal_document_type_id' => $docType->id,
            'series_code' => 'USR',
            'fiscal_year' => $year,
            'assigned_user_id' => $company->id,
            'terminal_code' => null,
            'fiscal_regime_code' => null,
            'last_sequence' => 0,
            'is_active' => true,
            'valid_from' => "{$year}-01-01",
            'valid_to' => "{$year}-12-31",
            'created_by' => $company->id,
        ]);

        $invoice = new SalesInvoice([
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $observer = app(FiscalDocumentObserver::class);
        $method = new \ReflectionMethod(FiscalDocumentObserver::class, 'resolveOrCreateSeries');
        $method->setAccessible(true);

        $resolvedSeries = $method->invoke($observer, $invoice, $company->id, 'FT', now()->toDateString());

        $this->assertInstanceOf(FiscalDocumentSeries::class, $resolvedSeries);
        $this->assertSame($userSeries->id, (int) $resolvedSeries->id);
        $this->assertSame(0, (int) $generalSeries->fresh()->last_sequence);
        $this->assertSame(0, (int) $userSeries->fresh()->last_sequence);
    }

    private function makeCompany(): User
    {
        return User::factory()->create([
            'type' => 'company',
            'created_by' => null,
            'creator_id' => null,
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
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
