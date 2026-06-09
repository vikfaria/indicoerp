<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\PgcAccountCatalog;
use App\Services\PgcImportService;
use Database\Seeders\PgcNirfSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Helpers\AccountUtility;
use Workdo\Account\Models\ChartOfAccount;
use App\Models\PgcAccountMapping;
use App\Models\User;

class PgcImportValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_pgc_import_page_returns_validation_report_and_catalog(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);

        $this->seedOfficialCatalog();
        AccountUtility::defaultdata($company->id);

        $service = app(PgcImportService::class);
        $service->importForCompany($company->id, 'pgc_nirf');

        $response = $this->actingAs($company)->get(route('sce.fiscal.pgc'));

        $response->assertOk();
        $response->assertInertia(function (Assert $page): void {
            $page->component('Fiscal/PgcImport/Index')
                ->where('framework', 'pgc_nirf')
                ->where('validationReport.valid', true)
                ->where('validationReport.catalog_count', fn (int $value): bool => $value > 500)
                ->where('validationReport.company_pgc_count', fn (int $value): bool => $value > 500)
                ->where('validationReport.missing_classes', [])
                ->where('validationReport.errors', [])
                ->where('validationReport.legacy_active_count', fn (int $value): bool => $value > 0)
                ->has('catalog', 10);
        });
    }

    public function test_pgc_validation_report_flags_missing_class_nine(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);

        $this->seedOfficialCatalog();
        AccountUtility::defaultdata($company->id);

        $service = app(PgcImportService::class);
        $service->importForCompany($company->id, 'pgc_nirf');

        ChartOfAccount::query()
            ->where('created_by', $company->id)
            ->where('pgc_class', 9)
            ->delete();

        $report = $service->buildValidationReport($company->id, 'pgc_nirf');

        $this->assertFalse($report['valid']);
        $this->assertContains(9, $report['missing_classes']);
        $this->assertNotEmpty($report['errors']);
    }

    public function test_pgc_reconcile_route_creates_migration_mappings(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);

        $this->seedOfficialCatalog();
        AccountUtility::defaultdata($company->id);

        $service = app(PgcImportService::class);
        $service->importForCompany($company->id, 'pgc_nirf');

        $response = $this->actingAs($company)->post(route('sce.fiscal.pgc.reconcile'));
        $response->assertSessionHasNoErrors();

        $this->assertGreaterThan(0, PgcAccountMapping::query()->where('company_id', $company->id)->count());
    }

    public function test_pgc_catalog_includes_official_reference_extensions(): void
    {
        $this->seedOfficialCatalog();

        $this->assertTrue(PgcAccountCatalog::query()->where('framework', 'pgc_nirf')->where('account_code', '311')->exists());
        $this->assertTrue(PgcAccountCatalog::query()->where('framework', 'pgc_nirf')->where('account_code', '411')->exists());
        $this->assertTrue(PgcAccountCatalog::query()->where('framework', 'pgc_nirf')->where('account_code', '711')->exists());
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

    private function seedOfficialCatalog(): void
    {
        $seeder = new PgcNirfSeeder();
        $seeder->run();
    }
}
