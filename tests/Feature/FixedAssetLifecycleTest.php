<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\AuditTrail;
use App\Models\FixedAsset;
use App\Services\DepreciationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Helpers\AccountUtility;
use Workdo\Account\Models\JournalEntry;
use Workdo\Account\Models\JournalEntryItem;

class FixedAssetLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_fixed_asset_creation_blocks_unsupported_depreciation_methods(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account']);
        $this->actingAs($company);
        AccountUtility::defaultdata($company->id);

        $this->from(route('sce.fixed-assets.create'))
            ->post(route('sce.fixed-assets.store'), [
                'asset_code' => 'AF-SCOPE-001',
                'name' => 'Servidor de testes',
                'category' => 'tangible',
                'acquisition_date' => '2026-06-01',
                'acquisition_cost' => 1200,
                'residual_value' => 0,
                'useful_life_months' => 12,
                'depreciation_method' => 'declining_balance',
            ])
            ->assertSessionHasErrors(['depreciation_method']);

        $this->assertDatabaseMissing('fixed_assets', [
            'company_id' => $company->id,
            'asset_code' => 'AF-SCOPE-001',
        ]);
    }

    public function test_fixed_asset_depreciation_and_disposal_are_posted_with_audit_trail(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account']);
        $this->actingAs($company);
        AccountUtility::defaultdata($company->id);

        $this->from(route('sce.fixed-assets.create'))
            ->post(route('sce.fixed-assets.store'), [
                'asset_code' => 'AF-SCOPE-002',
                'name' => 'Servidor principal',
                'category' => 'tangible',
                'acquisition_date' => '2026-06-01',
                'acquisition_cost' => 1200,
                'residual_value' => 0,
                'useful_life_months' => 12,
                'depreciation_method' => 'straight_line',
            ])
            ->assertSessionHas('success');

        $asset = FixedAsset::query()->where('company_id', $company->id)->where('asset_code', 'AF-SCOPE-002')->firstOrFail();

        $this->from(route('sce.fixed-assets.index'))
            ->post(route('sce.fixed-assets.depreciation'), [
                'year' => '2026',
                'month' => 6,
            ])
            ->assertSessionHas('success');

        $asset->refresh();
        $this->assertSame(100.0, round((float) $asset->accumulated_depreciation, 2));
        $this->assertSame(1100.0, round((float) $asset->net_book_value, 2));

        $this->from(route('sce.fixed-assets.show', $asset))
            ->post(route('sce.fixed-assets.dispose', $asset), [
                'disposal_date' => '2026-07-15',
                'disposal_proceeds' => 1300,
            ])
            ->assertSessionHas('success');

        $asset->refresh();
        $this->assertSame('disposed', $asset->status);
        $this->assertSame('2026-07-15', $asset->disposal_date?->toDateString());
        $this->assertSame(1300.0, round((float) $asset->disposal_proceeds, 2));
        $this->assertSame(0.0, round((float) $asset->net_book_value, 2));

        $disposalJournal = JournalEntry::query()
            ->where('reference_type', 'fixed_asset_disposal')
            ->where('reference_id', $asset->id)
            ->firstOrFail();

        $this->assertSame(1400.0, round((float) $disposalJournal->total_debit, 2));
        $this->assertSame(1400.0, round((float) $disposalJournal->total_credit, 2));

        $disposalItems = JournalEntryItem::query()
            ->with('account')
            ->where('journal_entry_id', $disposalJournal->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(4, $disposalItems);
        $this->assertContains('1610', $disposalItems->pluck('account.account_code')->all());
        $this->assertContains('1010', $disposalItems->pluck('account.account_code')->all());
        $this->assertContains('1600', $disposalItems->pluck('account.account_code')->all());
        $this->assertContains('4300', $disposalItems->pluck('account.account_code')->all());

        $auditTrail = AuditTrail::query()
            ->where('auditable_type', FixedAsset::class)
            ->where('auditable_id', $asset->id)
            ->where('event', 'disposed')
            ->first();

        $this->assertNotNull($auditTrail);
        $this->assertSame('disposed', $auditTrail->new_values['status'] ?? null);

        $result = app(DepreciationService::class)->runMonthlyDepreciation($company->id, '2026', 7);
        $this->assertSame(0, $result['processed']);
        $this->assertSame(1, FixedAsset::query()->where('id', $asset->id)->count());
        $this->assertSame(1, $asset->depreciationEntries()->count());
    }

    public function test_disposal_without_proceeds_records_loss_on_miscellaneous_expense_account(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account']);
        $this->actingAs($company);
        AccountUtility::defaultdata($company->id);

        $asset = FixedAsset::query()->create([
            'company_id' => $company->id,
            'asset_code' => 'AF-SCOPE-LOSS',
            'name' => 'Activo em perda',
            'description' => null,
            'category' => 'tangible',
            'sub_category' => null,
            'acquisition_date' => '2026-05-01',
            'acquisition_cost' => 1000,
            'residual_value' => 0,
            'useful_life_months' => 10,
            'depreciation_method' => 'straight_line',
            'depreciation_rate' => 10,
            'accumulated_depreciation' => 300,
            'net_book_value' => 700,
            'impairment_losses' => 0,
            'revaluation_surplus' => 0,
            'last_depreciation_date' => '2026-05-01',
            'status' => 'active',
            'disposal_date' => null,
            'disposal_proceeds' => null,
            'location' => null,
            'responsible_person' => null,
            'pgc_asset_account' => '1600',
            'pgc_depreciation_account' => '1610',
            'pgc_expense_account' => '5430',
            'serial_number' => null,
            'supplier' => null,
            'invoice_reference' => null,
            'created_by' => $company->id,
        ]);

        $result = app(DepreciationService::class)->disposeAsset($asset, '2026-06-30', 0);

        $this->assertSame(-700.0, round((float) $result['gain_or_loss'], 2));

        $asset->refresh();
        $this->assertSame('disposed', $asset->status);
        $this->assertSame(0.0, round((float) $asset->disposal_proceeds, 2));
        $this->assertSame(0.0, round((float) $asset->net_book_value, 2));

        $journal = JournalEntry::query()
            ->where('reference_type', 'fixed_asset_disposal')
            ->where('reference_id', $asset->id)
            ->firstOrFail();

        $items = JournalEntryItem::query()
            ->with('account')
            ->where('journal_entry_id', $journal->id)
            ->get();

        $this->assertContains('5800', $items->pluck('account.account_code')->all());
        $this->assertSame(1000.0, round((float) $journal->total_debit, 2));
        $this->assertSame(1000.0, round((float) $journal->total_credit, 2));
    }

    private function makeCompany()
    {
        return \App\Models\User::factory()->create([
            'type' => 'company',
            'created_by' => null,
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);
    }

    private function grantPermissions($user, array $permissions): void
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
    }
}
