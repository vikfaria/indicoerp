<?php

namespace Tests\Feature;

use App\Classes\Module;
use App\Models\AccountingPeriod;
use App\Models\AddOn;
use App\Models\CompanyFiscalProfile;
use App\Models\FiscalDocumentSeries;
use App\Models\FiscalDocumentType;
use App\Models\MzVatCode;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Models\UserActiveModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Helpers\AccountUtility;
use Workdo\Account\Models\Customer;
use Workdo\Account\Models\JournalEntry as AccountJournalEntry;
use Workdo\Account\Services\JournalService;
use Workdo\ProductService\Models\ProductServiceItem;

class AssistantActivationEndToEndFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        app(Module::class)->moduleCacheForget();
    }

    public function test_new_company_can_issue_a_service_invoice_post_it_and_reach_a_balanced_trial_balance(): void
    {
        $company = $this->makeCompany();

        $this->enableModule($company, 'Account');
        $this->enableModule($company, 'DoubleEntry');

        AccountUtility::defaultdata($company->id);
        FiscalDocumentType::seedDefaults();
        MzVatCode::seedDefaults();
        AccountingPeriod::generateForYear($company->id, '2026');

        $this->saveCompanySettings($company, [
            'company_name' => 'Empresa Nova GoLive',
            'company_address' => 'Av. Julius Nyerere, 100',
            'company_city' => 'Maputo',
            'company_state' => 'Maputo',
            'company_zipcode' => '1100',
            'company_country' => 'Mozambique',
            'company_telephone' => '+258840000000',
            'company_email' => 'info@empresa-nova.test',
            'company_tax_number' => '400123456',
            'tax_type' => 'NUIT',
            'sales_invoice_series' => 'A',
        ]);

        $this->makeFiscalProfile($company);
        $fiscalSeries = $this->makeFiscalSeries($company, 'A', 2026);
        $customer = $this->makeClient($company, 'Cliente GoLive');
        $this->makeCustomerDetails($company, $customer, [
            'company_name' => 'Cliente GoLive Lda',
            'tax_number' => '400555666',
            'billing_address' => [
                'name' => 'Cliente GoLive',
                'address_line_1' => 'Bairro Central',
                'city' => 'Matola',
                'state' => 'Maputo',
                'zip_code' => '1114',
                'country' => 'Mozambique',
            ],
            'shipping_address' => [
                'name' => 'Cliente GoLive Warehouse',
                'address_line_1' => 'Av. 24 de Julho',
                'city' => 'Maputo',
                'state' => 'Maputo',
                'zip_code' => '1100',
                'country' => 'Mozambique',
            ],
        ]);

        $service = $this->makeServiceItem($company, 'Serviço Básico', 'SRV-001', 100.00);

        $this->grantPermissions($company, [
            'create-sales-invoices',
            'manage-any-sales-invoices',
            'post-sales-invoices',
            'manage-trial-balance',
        ]);

        $storeResponse = $this->actingAs($company->fresh())->post(route('sales-invoices.store'), [
            'invoice_date' => '2026-06-05',
            'due_date' => '2026-06-12',
            'customer_id' => $customer->id,
            'type' => 'service',
            'payment_terms' => '7 days',
            'notes' => 'E2E go-live flow',
            'items' => [
                [
                    'product_id' => $service->id,
                    'quantity' => 1,
                    'unit_price' => 100,
                    'discount_percentage' => 0,
                    'tax_percentage' => 16,
                    'vat_code' => 'NOR',
                ],
            ],
        ]);

        $storeResponse->assertSessionHasNoErrors();
        $storeResponse->assertRedirect(route('sales-invoices.index'));

        $invoice = SalesInvoice::query()->latest('id')->firstOrFail();

        $this->assertSame('draft', (string) $invoice->status);
        $this->assertSame('SI-A-2026-06-001', (string) $invoice->invoice_number);
        $this->assertSame('A', (string) $invoice->document_series);
        $this->assertSame(100.00, round((float) $invoice->subtotal, 2));
        $this->assertSame(16.00, round((float) $invoice->tax_amount, 2));
        $this->assertSame(116.00, round((float) $invoice->total_amount, 2));
        $this->assertSame('Empresa Nova GoLive', data_get($invoice->issuer_snapshot, 'company_name'));
        $this->assertSame('400123456', data_get($invoice->issuer_snapshot, 'tax_number'));
        $this->assertSame('Cliente GoLive', data_get($invoice->counterparty_snapshot, 'name'));
        $this->assertSame('Cliente GoLive Lda', data_get($invoice->counterparty_snapshot, 'company_name'));
        $this->assertSame('Matola', data_get($invoice->counterparty_snapshot, 'billing_address.city'));
        $this->assertDatabaseHas('fiscal_document_series', [
            'id' => $fiscalSeries->id,
            'company_id' => $company->id,
            'series_code' => 'A',
            'fiscal_year' => 2026,
            'is_active' => 1,
        ]);

        $postResponse = $this->actingAs($company->fresh())->post(route('sales-invoices.post', $invoice));

        $postResponse->assertSessionHasNoErrors();
        $invoice->refresh();
        $fiscalSeries->refresh();

        $this->assertSame('posted', (string) $invoice->status);
        $this->assertSame(1, (int) $invoice->document_sequence);

        $journal = AccountJournalEntry::query()
            ->where('reference_type', 'service_invoice')
            ->where('reference_id', $invoice->id)
            ->first();

        if (! $journal) {
            $journal = app(JournalService::class)->createServiceInvoiceJournal($invoice);
        }

        $journal = AccountJournalEntry::query()
            ->where('reference_type', 'service_invoice')
            ->where('reference_id', $invoice->id)
            ->firstOrFail();

        $this->assertSame(116.00, round((float) $journal->total_debit, 2));
        $this->assertSame(116.00, round((float) $journal->total_credit, 2));
        $this->assertSame('posted', (string) $journal->status);

        $trialBalanceResponse = $this->actingAs($company->fresh())->get(route('double-entry.trial-balance.index', [
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-30',
        ]));

        $trialBalanceResponse->assertOk();
        $trialBalanceResponse->assertInertia(function (Assert $page): void {
            $page->where('trialBalance.is_balanced', true)
                ->where('trialBalance.total_debit', fn (mixed $value): bool => round((float) $value, 2) === 116.00)
                ->where('trialBalance.total_credit', fn (mixed $value): bool => round((float) $value, 2) === 116.00)
                ->where('trialBalance.accounts', function (mixed $accounts): bool {
                    $accounts = collect($accounts)->keyBy('account_code');

                    return round((float) data_get($accounts->get('1100'), 'debit', 0), 2) === 116.00
                        && round((float) data_get($accounts->get('4200'), 'credit', 0), 2) === 100.00
                        && round((float) data_get($accounts->get('2210'), 'credit', 0), 2) === 16.00;
                });
        });
    }

    private function makeCompany(): User
    {
        $company = User::factory()->create([
            'type' => 'company',
            'created_by' => null,
            'creator_id' => null,
            'email_verified_at' => now(),
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);

        $company->ensureCompanyAccessRole();

        return $company;
    }

    private function makeFiscalProfile(User $company): CompanyFiscalProfile
    {
        return CompanyFiscalProfile::updateOrCreate(
            ['company_id' => $company->id],
            [
                'nuit' => '400123456',
                'legal_name' => 'Empresa Nova GoLive',
                'fiscal_regime' => 'normal',
                'entity_classification' => 'small',
                'accounting_framework' => 'pgc_nirf',
                'fiscal_year_start_month' => 1,
                'entity_type' => 'company',
                'taxpayer_type' => 'normal',
                'is_active' => true,
                'created_by' => $company->id,
            ]
        );
    }

    private function makeFiscalSeries(User $company, string $seriesCode, int $year): FiscalDocumentSeries
    {
        $documentType = FiscalDocumentType::query()
            ->where('code', 'FT')
            ->firstOrFail();

        return FiscalDocumentSeries::updateOrCreate(
            [
                'company_id' => $company->id,
                'fiscal_document_type_id' => $documentType->id,
                'series_code' => $seriesCode,
                'fiscal_year' => $year,
            ],
            [
                'last_sequence' => 0,
                'last_hash' => null,
                'is_active' => true,
                'assigned_user_id' => null,
                'terminal_code' => null,
                'fiscal_regime_code' => null,
                'created_by' => $company->id,
            ]
        );
    }

    private function makeClient(User $company, string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'type' => 'client',
            'created_by' => $company->id,
            'creator_id' => $company->id,
            'email_verified_at' => now(),
        ]);
    }

    private function makeCustomerDetails(User $company, User $customer, array $overrides = []): Customer
    {
        return Customer::create(array_merge([
            'user_id' => $customer->id,
            'customer_code' => 'CUST-' . str_pad((string) (Customer::count() + 1), 4, '0', STR_PAD_LEFT),
            'company_name' => 'Cliente Demo',
            'contact_person_name' => 'Joana Cliente',
            'contact_person_email' => 'cliente@example.test',
            'contact_person_mobile' => '+258841111111',
            'tax_number' => '400000001',
            'payment_terms' => '15 days',
            'billing_address' => [
                'name' => 'Cliente Demo',
                'address_line_1' => 'Bairro Central',
                'city' => 'Matola',
                'state' => 'Maputo',
                'zip_code' => '1114',
                'country' => 'Mozambique',
            ],
            'shipping_address' => [
                'name' => 'Cliente Demo Armazem',
                'address_line_1' => 'Av. 24 de Julho',
                'city' => 'Maputo',
                'state' => 'Maputo',
                'zip_code' => '1100',
                'country' => 'Mozambique',
            ],
            'same_as_billing' => false,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ], $overrides));
    }

    private function makeServiceItem(User $company, string $name, string $sku, float $salePrice): ProductServiceItem
    {
        return ProductServiceItem::create([
            'name' => $name,
            'sku' => $sku,
            'description' => $name,
            'sale_price' => $salePrice,
            'purchase_price' => 0,
            'type' => 'service',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function saveCompanySettings(User $company, array $settings): void
    {
        foreach ($settings as $key => $value) {
            setSetting($key, $value, $company->id);
        }
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

            if (! $user->hasPermissionTo($permission)) {
                $user->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->refresh();
    }

    private function enableModule(User $company, string $module): void
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

        UserActiveModule::updateOrCreate(
            ['user_id' => $company->id, 'module' => $module],
            ['module' => $module]
        );

        app(Module::class)->moduleCacheForget($module);
    }
}
