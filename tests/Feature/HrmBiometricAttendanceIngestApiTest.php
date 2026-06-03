<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Workdo\Hrm\Models\Attendance;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Shift;

class HrmBiometricAttendanceIngestApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_device_ingest_creates_clock_in_and_clock_out_records(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeStaff($company, 'Biometric User');

        $shift = Shift::query()->create([
            'shift_name' => 'Default Shift',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $employee = Employee::query()->create([
            'employee_id' => 'BIO-001',
            'user_id' => $employeeUser->id,
            'shift' => $shift->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 12000,
            'rate_per_hour' => 100,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Setting::query()->updateOrCreate(
            ['key' => 'mz_attendance_device_ingest_token', 'created_by' => $company->id],
            ['value' => 'secure-biometric-token', 'is_public' => 0]
        );

        $clockInResponse = $this->withHeader('X-HRM-DEVICE-TOKEN', 'secure-biometric-token')
            ->postJson('/api/hrm/attendance/device-ingest', [
                'company_id' => $company->id,
                'employee_identifier' => $employee->employee_id,
                'identifier_type' => 'employee_id',
                'action' => 'clockin',
                'occurred_at' => '2026-05-31 08:00:00',
                'device_id' => 'KIOSK-01',
                'device_name' => 'Main Gate Device',
                'source_reference' => 'tx-1001',
            ]);

        $clockInResponse->assertOk()->assertJsonPath('success', true);

        $clockOutResponse = $this->withHeader('X-HRM-DEVICE-TOKEN', 'secure-biometric-token')
            ->postJson('/api/hrm/attendance/device-ingest', [
                'company_id' => $company->id,
                'employee_identifier' => $employee->employee_id,
                'identifier_type' => 'employee_id',
                'action' => 'clockout',
                'occurred_at' => '2026-05-31 17:30:00',
                'device_id' => 'KIOSK-01',
                'device_name' => 'Main Gate Device',
                'source_reference' => 'tx-1002',
            ]);

        $clockOutResponse->assertOk()->assertJsonPath('success', true);

        $attendance = Attendance::query()->firstOrFail();
        $this->assertSame($company->id, (int) $attendance->created_by);
        $this->assertSame($employeeUser->id, (int) $attendance->employee_id);
        $this->assertSame('biometric', (string) $attendance->source_channel);
        $this->assertNotNull($attendance->clock_in);
        $this->assertNotNull($attendance->clock_out);
        $this->assertGreaterThan(0, (float) $attendance->total_hour);
    }

    public function test_device_ingest_rejects_invalid_token(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeStaff($company, 'Invalid Token User');

        $shift = Shift::query()->create([
            'shift_name' => 'Default Shift',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Employee::query()->create([
            'employee_id' => 'BIO-INVALID-001',
            'user_id' => $employeeUser->id,
            'shift' => $shift->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 12000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Setting::query()->updateOrCreate(
            ['key' => 'mz_attendance_device_ingest_token', 'created_by' => $company->id],
            ['value' => 'secure-biometric-token', 'is_public' => 0]
        );

        $response = $this->withHeader('X-HRM-DEVICE-TOKEN', 'wrong-token')
            ->postJson('/api/hrm/attendance/device-ingest', [
                'company_id' => $company->id,
                'employee_identifier' => 'BIO-INVALID-001',
                'identifier_type' => 'employee_id',
                'action' => 'clockin',
                'occurred_at' => '2026-05-31 08:00:00',
            ]);

        $response->assertStatus(403)->assertJsonPath('success', false);
        $this->assertSame(0, Attendance::query()->count());
    }

    public function test_device_ingest_rejects_when_biometric_ingest_is_disabled(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeStaff($company, 'Disabled Device User');

        $shift = Shift::query()->create([
            'shift_name' => 'Default Shift',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Employee::query()->create([
            'employee_id' => 'BIO-DISABLED-001',
            'user_id' => $employeeUser->id,
            'shift' => $shift->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 12000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Setting::query()->updateOrCreate(
            ['key' => 'mz_attendance_device_ingest_token', 'created_by' => $company->id],
            ['value' => 'secure-biometric-token', 'is_public' => 0]
        );
        Setting::query()->updateOrCreate(
            ['key' => 'mz_attendance_device_ingest_enabled', 'created_by' => $company->id],
            ['value' => '0', 'is_public' => 0]
        );

        $response = $this->withHeader('X-HRM-DEVICE-TOKEN', 'secure-biometric-token')
            ->postJson('/api/hrm/attendance/device-ingest', [
                'company_id' => $company->id,
                'employee_identifier' => 'BIO-DISABLED-001',
                'identifier_type' => 'employee_id',
                'action' => 'clockin',
                'occurred_at' => '2026-05-31 08:00:00',
            ]);

        $response->assertStatus(403)->assertJsonPath('success', false);
        $this->assertSame(0, Attendance::query()->count());
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

    private function makeStaff(User $company, string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'type' => 'staff',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);
    }
}
