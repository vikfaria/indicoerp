<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\FiscalExportHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MozambiqueFiscalCalendarExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_fiscal_calendar_export_generates_csv_and_history_record(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);

        $this->artisan('sce:setup', [
            '--company' => $company->id,
            '--year' => 2026,
            '--skip-catalog' => true,
            '--skip-import' => true,
            '--force' => true,
        ])->assertExitCode(0);

        $response = $this->actingAs($company)->get(route('sce.fiscal.calendar.export', [
            'year' => 2026,
        ]));

        $response->assertOk();
        $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment; filename="mozambique-fiscal-calendar-2026.csv"', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('code,title,obligation_type,due_date,reference_period,status,completed_date,notes', $response->getContent());
        $this->assertStringContainsString('IVA-2026-1', $response->getContent());
        $this->assertStringContainsString('Declaração Periódica IVA — 01/2026', $response->getContent());
        $this->assertStringContainsString('2026-02-20', $response->getContent());

        $history = FiscalExportHistory::query()
            ->where('company_id', $company->id)
            ->where('export_type', 'fiscal_calendar_csv')
            ->latest('id')
            ->first();

        $this->assertNotNull($history);
        $this->assertSame('mozambique-fiscal-calendar-2026.csv', (string) $history->file_name);
        $this->assertSame('generated', (string) $history->status);
        $this->assertNotEmpty($history->file_hash);
        $this->assertNotEmpty($history->file_path);
        $this->assertTrue(Storage::disk('local')->exists($history->file_path));

        if ($history->file_path) {
            Storage::disk('local')->delete($history->file_path);
        }
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
}
