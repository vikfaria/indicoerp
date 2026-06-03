<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Payroll;
use Workdo\Hrm\Models\PayrollEntry;

class HrmPayrollSubmissionFormatsAndApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_modelo19_and_inss_exports_support_json_xml_and_xlsx_formats(): void
    {
        $company = $this->makeCompany();
        $this->seedPayrollForReferencePeriod($company, 'Formato Export User', '2026-06');
        $this->grantPermissions($company, ['view-payrolls', 'view-sensitive-employee-data']);

        $modelo19Json = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.modelo19-support.json',
            ['reference_period' => '2026-06']
        ));
        $modelo19Json->assertOk();
        $modelo19Json->assertJsonPath('reference_period', '2026-06');

        $modelo19Xml = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.modelo19-support.xml',
            ['reference_period' => '2026-06']
        ));
        $modelo19Xml->assertOk();
        $modelo19Xml->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $modelo19Xml->assertSee('modelo19_irps_support', false);

        $modelo19Xlsx = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.modelo19-support.xlsx',
            ['reference_period' => '2026-06']
        ));
        $modelo19Xlsx->assertOk();
        $modelo19Xlsx->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $inssJson = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.inss-guide.json',
            ['reference_period' => '2026-06']
        ));
        $inssJson->assertOk();
        $inssJson->assertJsonPath('reference_period', '2026-06');

        $inssXml = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.inss-guide.xml',
            ['reference_period' => '2026-06']
        ));
        $inssXml->assertOk();
        $inssXml->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $inssXml->assertSee('inss_monthly_guide', false);

        $inssXlsx = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.inss-guide.xlsx',
            ['reference_period' => '2026-06']
        ));
        $inssXlsx->assertOk();
        $inssXlsx->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_payroll_submission_api_exports_are_scoped_and_mask_sensitive_bank_data_without_permission(): void
    {
        $company = $this->makeCompany();
        $this->seedPayrollForReferencePeriod($company, 'API Submission User', '2026-06');
        $this->grantPermissions($company, ['view-payrolls']);

        $modelo19Response = $this->actingAs($company, 'sanctum')
            ->getJson('/api/hrm/payroll-submission/modelo19?reference_period=2026-06');
        $modelo19Response->assertOk();
        $modelo19Response->assertJsonPath('success', true);
        $modelo19Response->assertJsonPath('data.reference_period', '2026-06');

        $inssResponse = $this->actingAs($company, 'sanctum')
            ->getJson('/api/hrm/payroll-submission/inss?reference_period=2026-06');
        $inssResponse->assertOk();
        $inssResponse->assertJsonPath('success', true);
        $inssResponse->assertJsonPath('data.reference_period', '2026-06');

        $bankResponse = $this->actingAs($company, 'sanctum')
            ->getJson('/api/hrm/payroll-submission/bank-payment-file?reference_period=2026-06');
        $bankResponse->assertOk();
        $bankResponse->assertJsonPath('success', true);
        $maskedAccountHolder = (string) data_get($bankResponse->json(), 'data.rows.0.account_holder_name');
        $maskedEmployeeNuit = (string) data_get($bankResponse->json(), 'data.rows.0.employee_nuit');
        $maskedAccountNumber = (string) data_get($bankResponse->json(), 'data.rows.0.account_number');

        $this->assertStringStartsWith('A', $maskedAccountHolder);
        $this->assertStringContainsString('*', $maskedAccountHolder);
        $this->assertNotSame('API Submission User 321', $maskedAccountHolder);

        $this->assertSame('41*****56', $maskedEmployeeNuit);
        $this->assertSame('**********6789', $maskedAccountNumber);

        $annualResponse = $this->actingAs($company, 'sanctum')
            ->getJson('/api/hrm/payroll-submission/annual-fiscal-history?fiscal_year=2026');
        $annualResponse->assertOk();
        $annualResponse->assertJsonPath('success', true);
        $annualResponse->assertJsonPath('data.fiscal_year', '2026');
        $annualResponse->assertJsonPath('data.summary.workers', 1);
    }

    public function test_payroll_submission_api_exports_require_view_payroll_permission(): void
    {
        $company = $this->makeCompany();
        $this->seedPayrollForReferencePeriod($company, 'No Permission User', '2026-06');

        $response = $this->actingAs($company, 'sanctum')
            ->getJson('/api/hrm/payroll-submission/modelo19?reference_period=2026-06');

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
    }

    private function seedPayrollForReferencePeriod(User $company, string $employeeName, string $referencePeriod): void
    {
        [$year, $month] = explode('-', $referencePeriod);
        $periodStart = sprintf('%s-%s-01', $year, $month);
        $periodEnd = date('Y-m-t', strtotime($periodStart));

        $employeeUser = User::factory()->create([
            'name' => $employeeName,
            'type' => 'staff',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);

        Employee::query()->create([
            'employee_id' => 'EMP-' . $company->id . '-' . $employeeUser->id,
            'user_id' => $employeeUser->id,
            'tax_payer_id' => '411223456',
            'employment_type' => 'GENERAL',
            'basic_salary' => 26000,
            'account_holder_name' => 'API Submission User 321',
            'bank_name' => 'Banco MZ',
            'bank_branch' => 'Maputo',
            'bank_identifier_code' => 'BMAOMZMA',
            'account_number' => '00000123456789',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $payroll = Payroll::query()->create([
            'title' => 'Payroll ' . $referencePeriod,
            'payroll_frequency' => 'monthly',
            'pay_period_start' => $periodStart,
            'pay_period_end' => $periodEnd,
            'pay_date' => $periodEnd,
            'status' => 'completed',
            'is_payroll_paid' => 'paid',
            'total_gross_pay' => 26000,
            'total_deductions' => 1820,
            'total_net_pay' => 24180,
            'total_irps' => 820,
            'total_inss_employee' => 780,
            'total_inss_employer' => 1040,
            'employee_count' => 1,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        PayrollEntry::query()->create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employeeUser->id,
            'basic_salary' => 26000,
            'gross_pay' => 26000,
            'taxable_income' => 26000,
            'irps_amount' => 820,
            'inss_employee_rate' => 3,
            'inss_employee_amount' => 780,
            'inss_employer_rate' => 4,
            'inss_employer_amount' => 1040,
            'statutory_deductions_total' => 1600,
            'total_allowances' => 1000,
            'total_manual_overtimes' => 0,
            'total_deductions' => 1820,
            'total_loans' => 0,
            'net_pay' => 24180,
            'working_days' => 22,
            'status' => 'paid',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
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

    private function grantPermissions(User $user, array $permissions): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'add_on' => 'hrm',
                    'module' => 'hrm',
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
