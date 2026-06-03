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

class MozambiqueFiscalExportsHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_exports_history_lists_and_confirms_submission(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary', 'manage-account-reports']);
        $this->actingAs($company);

        $history = FiscalExportHistory::query()->create([
            'company_id' => $company->id,
            'export_type' => 'saft_xml',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'generated_by' => $company->id,
            'file_name' => 'mozambique-saft-2026-06.xml',
            'file_hash' => hash('sha256', 'demo-export'),
            'file_path' => 'fiscal-exports/' . $company->id . '/saft_xml/demo-mozambique-saft-2026-06.xml',
            'status' => 'generated',
            'metadata' => [
                'fiscal_year' => now()->year,
                'validation' => ['well_formed' => true],
            ],
        ]);
        Storage::disk('local')->put($history->file_path, 'demo-export');

        $response = $this->actingAs($company)->getJson(route('account.reports.mozambique-fiscal-exports-history', [
            'from_date' => now()->startOfYear()->toDateString(),
            'to_date' => now()->endOfYear()->toDateString(),
            'export_type' => 'saft_xml',
        ]));

        $response->assertOk();
        $response->assertJsonPath('rows.0.id', $history->id);
        $response->assertJsonPath('rows.0.export_type', 'saft_xml');
        $response->assertJsonPath('rows.0.file_path', $history->file_path);
        $response->assertJsonPath('summary_by_status.generated', 1);
        $response->assertJsonPath('summary_by_type.saft_xml', 1);

        $submitResponse = $this->actingAs($company)->postJson(
            route('account.reports.mozambique-fiscal-exports-history.submit', $history),
            [
                'submission_channel' => 'manual_upload',
                'submission_reference' => 'AT-2026-0001',
                'status' => 'validated',
                'submitted_at' => now()->toDateTimeString(),
                'notes' => 'Manual AT submission.',
            ]
        );

        $submitResponse->assertOk();
        $submitResponse->assertJsonPath('data.id', $history->id);
        $submitResponse->assertJsonPath('data.status', 'validated');
        $submitResponse->assertJsonPath('data.submission_channel', 'manual_upload');
        $submitResponse->assertJsonPath('data.submission_reference', 'AT-2026-0001');

        $history->refresh();
        $this->assertSame('validated', $history->status);
        $this->assertSame('manual_upload', $history->submission_channel);
        $this->assertSame('AT-2026-0001', $history->submission_reference);
        $this->assertNotNull($history->submitted_at);
        $this->assertSame('Manual AT submission.', $history->metadata['submission_notes'] ?? null);
        $this->assertArrayHasKey('submission_updated_at', $history->metadata ?? []);

        Storage::disk('local')->delete($history->file_path);
    }

    public function test_exports_history_downloads_saved_artifact(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-tax-summary']);
        $this->actingAs($company);

        $filePath = 'fiscal-exports/' . $company->id . '/saft_xml/demo-download.xml';
        Storage::disk('local')->put($filePath, 'download-me');

        $history = FiscalExportHistory::query()->create([
            'company_id' => $company->id,
            'export_type' => 'saft_xml',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'generated_by' => $company->id,
            'file_name' => 'demo-download.xml',
            'file_hash' => hash('sha256', 'download-me'),
            'file_path' => $filePath,
            'status' => 'generated',
        ]);

        $downloadResponse = $this->actingAs($company)->get(route('account.reports.mozambique-fiscal-exports-history.download', $history));

        $downloadResponse->assertOk();
        $this->assertStringContainsString('demo-download.xml', (string) $downloadResponse->headers->get('Content-Disposition'));
        $this->assertTrue(Storage::disk('local')->exists($filePath));
        $this->assertSame('download-me', Storage::disk('local')->get($filePath));

        Storage::disk('local')->delete($filePath);
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
