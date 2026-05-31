<?php

namespace Tests\Feature;

use App\Models\AuditTrail;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Workdo\Hrm\Models\Warning;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_invoice_create_update_delete_are_logged(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeClient($company);
        $warehouse = $this->makeWarehouse($company);

        $this->actingAs($company);

        $invoice = SalesInvoice::create([
            'invoice_number' => 'SI-AUDIT-001',
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
            'status' => 'draft',
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invoice->update([
            'status' => 'posted',
        ]);

        $invoiceId = $invoice->id;
        $invoice->delete();

        $entries = AuditTrail::query()
            ->where('auditable_type', SalesInvoice::class)
            ->where('auditable_id', $invoiceId)
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $entries);
        $this->assertSame(['created', 'updated', 'deleted'], $entries->pluck('event')->all());

        $this->assertSame($company->id, $entries[0]->company_id);
        $this->assertSame($company->id, $entries[0]->user_id);
        $this->assertSame('draft', $entries[0]->new_values['status']);

        $this->assertSame('draft', $entries[1]->old_values['status']);
        $this->assertSame('posted', $entries[1]->new_values['status']);
        $this->assertSame(['status' => 'posted'], $entries[1]->changes);

        $this->assertSame('posted', $entries[2]->old_values['status']);
    }

    public function test_payroll_create_is_logged_when_hrm_module_is_available(): void
    {
        $payrollModelClass = 'Workdo\\Hrm\\Models\\Payroll';

        if (! class_exists($payrollModelClass)) {
            $this->markTestSkipped('HRM payroll model is not available.');
        }

        $company = $this->makeCompany();
        $this->actingAs($company);

        $payroll = $payrollModelClass::create([
            'title' => 'Payroll Janeiro 2026',
            'payroll_frequency' => 'monthly',
            'pay_period_start' => now()->startOfMonth()->toDateString(),
            'pay_period_end' => now()->endOfMonth()->toDateString(),
            'pay_date' => now()->toDateString(),
            'status' => 'draft',
            'is_payroll_paid' => 'unpaid',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $entry = AuditTrail::query()
            ->where('auditable_type', $payrollModelClass)
            ->where('auditable_id', $payroll->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame('created', $entry->event);
        $this->assertSame($company->id, $entry->company_id);
        $this->assertSame($company->id, $entry->user_id);
        $this->assertSame('Payroll Janeiro 2026', $entry->new_values['title']);
    }

    public function test_warning_cancellation_update_is_logged_when_hrm_module_is_available(): void
    {
        if (! class_exists(Warning::class)) {
            $this->markTestSkipped('HRM warning model is not available.');
        }

        $company = $this->makeCompany();
        $employee = User::factory()->create([
            'type' => 'staff',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);

        $this->actingAs($company);

        $warning = Warning::query()->create([
            'employee_id' => $employee->id,
            'subject' => 'Teste de auditoria disciplinar',
            'severity' => 'Minor',
            'warning_date' => now()->toDateString(),
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $warning->update([
            'is_cancelled' => true,
            'cancelled_at' => now(),
            'cancelled_by' => $company->id,
            'cancellation_reason' => 'Registo duplicado.',
        ]);

        $entries = AuditTrail::query()
            ->where('auditable_type', Warning::class)
            ->where('auditable_id', $warning->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $entries);
        $this->assertSame(['created', 'updated'], $entries->pluck('event')->all());
        $this->assertSame($company->id, $entries[0]->company_id);
        $this->assertSame($company->id, $entries[1]->user_id);
        $this->assertTrue((bool) ($entries[1]->new_values['is_cancelled'] ?? false));
        $this->assertSame('Registo duplicado.', $entries[1]->new_values['cancellation_reason'] ?? null);
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

    private function makeClient(User $company): User
    {
        return User::factory()->create([
            'type' => 'client',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);
    }

    private function makeWarehouse(User $company): Warehouse
    {
        return Warehouse::create([
            'name' => 'Audit Warehouse',
            'address' => 'Address',
            'city' => 'Maputo',
            'zip_code' => '1100',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }
}
