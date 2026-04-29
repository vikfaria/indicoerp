<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FiscalDocumentComplianceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_sales_invoice_number_uses_establishment_series_when_configured(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeClient($company, 'Cliente Serie');
        $warehouseMaputo = $this->makeWarehouse($company, 'Maputo Warehouse');
        $warehouseMatola = $this->makeWarehouse($company, 'Matola Warehouse');

        setSetting('sales_invoice_prefix', 'FT', $company->id);
        setSetting('sales_invoice_series', 'GRL', $company->id);
        setSetting('sales_invoice_series_warehouse_' . $warehouseMaputo->id, 'MPM', $company->id);

        $maputoInvoice = SalesInvoice::create([
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouseMaputo->id,
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => 'draft',
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $matolaInvoice = SalesInvoice::create([
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouseMatola->id,
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => 'draft',
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $period = now()->format('Y-m');

        $this->assertSame("FT-MPM-{$period}-001", $maputoInvoice->invoice_number);
        $this->assertSame("FT-GRL-{$period}-001", $matolaInvoice->invoice_number);
        $this->assertSame('FT', $maputoInvoice->document_type);
        $this->assertSame('MPM', $maputoInvoice->document_series);
        $this->assertSame($warehouseMaputo->id, $maputoInvoice->establishment_id);
        $this->assertSame(1, $maputoInvoice->document_sequence);
    }

    public function test_sales_invoice_fiscal_submission_and_cancellation_rules_are_enforced(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeClient($company, 'Cliente Fiscal');
        $warehouse = $this->makeWarehouse($company, 'Fiscal Warehouse');

        $this->grantPermissions($company, [
            'post-sales-invoices',
            'delete-sales-invoices',
            'manage-own-sales-invoices',
        ]);

        $invoice = SalesInvoice::create([
            'invoice_number' => 'FT-2026-04-300',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => 'posted',
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company)
            ->post(route('sales-invoices.fiscal-status', $invoice), [
                'status' => 'submitted',
                'reference' => 'EDC-2026-0001',
            ])
            ->assertSessionHasNoErrors();

        $invoice->refresh();
        $this->assertSame('submitted', $invoice->fiscal_submission_status);
        $this->assertSame('EDC-2026-0001', $invoice->fiscal_submission_reference);
        $this->assertNotNull($invoice->fiscal_submitted_at);

        $this->actingAs($company)
            ->post(route('sales-invoices.fiscal-status', $invoice), [
                'status' => 'validated',
                'reference' => 'AT-VAL-2026-0099',
            ])
            ->assertSessionHasNoErrors();

        $invoice->refresh();
        $this->assertSame('validated', $invoice->fiscal_submission_status);
        $this->assertSame('AT-VAL-2026-0099', $invoice->fiscal_submission_reference);
        $this->assertNotNull($invoice->fiscal_validated_at);

        $this->actingAs($company)
            ->post(route('sales-invoices.cancel-fiscal', $invoice), [
                'reason' => 'Documento invalidado sem referencia de retificacao',
            ])
            ->assertSessionHasErrors('rectification_reference');

        $this->actingAs($company)
            ->post(route('sales-invoices.cancel-fiscal', $invoice), [
                'reason' => 'Anulacao fiscal com documento de retificacao',
                'cancellation_reference' => 'ANU-2026-88',
                'rectification_reference' => 'NC-2026-04-888',
            ])
            ->assertSessionHasNoErrors();

        $invoice->refresh();
        $this->assertTrue((bool) $invoice->is_cancelled);
        $this->assertSame('rejected', $invoice->fiscal_submission_status);
        $this->assertSame('ANU-2026-88', $invoice->cancellation_reference);
        $this->assertSame('NC-2026-04-888', $invoice->rectification_reference);
        $this->assertNotNull($invoice->cancelled_at);
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

    private function makeClient(User $company, string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'type' => 'client',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);
    }

    private function makeWarehouse(User $company, string $name): Warehouse
    {
        return Warehouse::create([
            'name' => $name,
            'address' => 'Address',
            'city' => 'Maputo',
            'zip_code' => '1100',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
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
