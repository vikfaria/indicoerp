<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Workdo\Account\Helpers\AccountUtility;
use Workdo\Account\Models\AccountCategory;
use Workdo\Account\Models\AccountType;
use Workdo\Account\Models\ChartOfAccount;

class AccountDefaultCatalogLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_account_catalog_is_seeded_in_portuguese(): void
    {
        $company = User::factory()->create([
            'type' => 'company',
            'created_by' => null,
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);

        AccountUtility::defaultdata($company->id);

        $this->assertSame(
            'Ativos',
            AccountCategory::where('created_by', $company->id)->where('code', 'AST')->value('name')
        );

        $this->assertSame(
            'Ativos Correntes',
            AccountType::where('created_by', $company->id)->where('code', 'CA')->value('name')
        );

        $this->assertSame(
            'Clientes',
            ChartOfAccount::where('created_by', $company->id)->where('account_code', '1100')->value('account_name')
        );

        $this->assertSame(
            'Encargos Bancarios',
            ChartOfAccount::where('created_by', $company->id)->where('account_code', '5510')->value('account_name')
        );
    }
}
