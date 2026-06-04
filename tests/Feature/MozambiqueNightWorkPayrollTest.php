<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\Setting;
use App\Models\User;
use App\Models\MozMinimumWage;
use App\Services\MozambiquePayrollLegalDefaultsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Hrm\Models\Attendance;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Payroll;
use Workdo\Hrm\Models\PayrollEntry;
use Workdo\Hrm\Models\Shift;

class MozambiqueNightWorkPayrollTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_run_payroll_applies_night_work_premium_from_attendance(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['run-payrolls', 'manage-any-payrolls']);

        app(MozambiquePayrollLegalDefaultsService::class)->seedForCompany($company->id);
        MozMinimumWage::query()->updateOrCreate([
            'created_by' => $company->id,
            'sector_code' => 'GENERAL',
            'effective_from' => now()->startOfYear()->toDateString(),
        ], [
            'sector_name' => 'General',
            'monthly_amount' => 12000,
            'effective_to' => null,
            'is_active' => true,
        ]);
        $this->setWorkingDays($company->id);
        $this->setNightWorkPremium($company->id, 25);

        $employeeUser = $this->makeEmployeeUser($company, 'Night Shift Worker');
        $employee = Employee::query()->create([
            'employee_id' => 'EMP-NIGHT-001',
            'user_id' => $employeeUser->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 24000,
            'hours_per_day' => 8,
            'rate_per_hour' => 100,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $shift = Shift::query()->create([
            'shift_name' => 'Night Shift',
            'start_time' => '22:00:00',
            'end_time' => '06:00:00',
            'break_start_time' => null,
            'break_end_time' => null,
            'is_night_shift' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Attendance::query()->create([
            'employee_id' => $employeeUser->id,
            'shift_id' => $shift->id,
            'date' => '2026-06-03',
            'clock_in' => '2026-06-03 22:00:00',
            'clock_out' => '2026-06-04 06:00:00',
            'break_hour' => 0,
            'total_hour' => 8,
            'overtime_hours' => 0,
            'overtime_amount' => 0,
            'status' => 'present',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $payroll = Payroll::query()->create([
            'title' => 'Payroll June 2026',
            'payroll_frequency' => 'monthly',
            'pay_period_start' => '2026-06-01',
            'pay_period_end' => '2026-06-30',
            'pay_date' => '2026-06-30',
            'status' => 'draft',
            'is_payroll_paid' => 'unpaid',
            'employee_count' => 0,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->post(route('hrm.payrolls.run', $payroll));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $payroll->refresh();
        $entry = PayrollEntry::query()
            ->where('payroll_id', $payroll->id)
            ->where('employee_id', $employeeUser->id)
            ->firstOrFail();

        $expectedLabel = 'Night Work Premium (8.00h @ 25.00%)';

        $this->assertSame('completed', $payroll->status);
        $this->assertSame(1, (int) $payroll->employee_count);
        $this->assertSame(24200.00, (float) $payroll->total_gross_pay);
        $this->assertSame(200.00, (float) $entry->total_allowances);
        $this->assertSame(24200.00, (float) $entry->gross_pay);
        $this->assertArrayHasKey($expectedLabel, $entry->allowances_breakdown);
        $this->assertSame(200.00, (float) $entry->allowances_breakdown[$expectedLabel]);
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

    private function makeEmployeeUser(User $company, string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'type' => 'staff',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);
    }

    private function setWorkingDays(int $companyId): void
    {
        Setting::query()->updateOrCreate(
            ['key' => 'working_days', 'created_by' => $companyId],
            ['value' => json_encode([1, 2, 3, 4, 5], JSON_THROW_ON_ERROR), 'is_public' => false]
        );

        Cache::forget('company_settings_' . $companyId);
        Cache::forget('company_settings_' . $companyId . '_public');
        Cache::forget('company_settings_owner:' . $companyId);
    }

    private function setNightWorkPremium(int $companyId, float $percent): void
    {
        Setting::query()->updateOrCreate(
            ['key' => 'mz_night_work_premium_percent', 'created_by' => $companyId],
            ['value' => (string) $percent, 'is_public' => 1]
        );

        Cache::forget('company_settings_' . $companyId);
        Cache::forget('company_settings_' . $companyId . '_public');
        Cache::forget('company_settings_owner:' . $companyId);
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
