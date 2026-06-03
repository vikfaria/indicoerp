<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Hrm\Models\Attendance;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Shift;

class HrmBiometricAttendanceDeviceSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_can_update_device_settings_and_export_health_json(): void
    {
        $company = $this->makeCompany();
        $staff = $this->makeStaff($company, 'Biometric Device Staff');
        $this->grantPermissions($company, ['edit-payrolls', 'view-payrolls']);

        $shift = Shift::query()->create([
            'shift_name' => 'Default Shift',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Employee::query()->create([
            'employee_id' => 'BIO-SET-001',
            'user_id' => $staff->id,
            'shift' => $shift->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 12000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $recentClosed = Attendance::query()->create([
            'employee_id' => $staff->id,
            'shift_id' => $shift->id,
            'date' => now()->toDateString(),
            'clock_in' => now()->subHours(2)->toDateTimeString(),
            'clock_out' => now()->subHour()->toDateTimeString(),
            'status' => 'present',
            'source_channel' => 'biometric',
            'source_device_id' => 'TERM-01',
            'source_device_label' => 'Main Gate',
            'source_reference' => 'device-ref-001',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
        Attendance::query()->whereKey($recentClosed->id)->update([
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHour(),
        ]);

        $recentOpen = Attendance::query()->create([
            'employee_id' => $staff->id,
            'shift_id' => $shift->id,
            'date' => now()->toDateString(),
            'clock_in' => now()->subMinutes(45)->toDateTimeString(),
            'clock_out' => null,
            'status' => 'present',
            'source_channel' => 'biometric',
            'source_device_id' => 'TERM-01',
            'source_device_label' => 'Main Gate',
            'source_reference' => 'device-ref-002',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
        Attendance::query()->whereKey($recentOpen->id)->update([
            'created_at' => now()->subMinutes(45),
            'updated_at' => now()->subMinutes(45),
        ]);

        $oldRecord = Attendance::query()->create([
            'employee_id' => $staff->id,
            'shift_id' => $shift->id,
            'date' => now()->subDays(4)->toDateString(),
            'clock_in' => now()->subDays(4)->setTime(8, 0)->toDateTimeString(),
            'clock_out' => now()->subDays(4)->setTime(17, 0)->toDateTimeString(),
            'status' => 'present',
            'source_channel' => 'biometric',
            'source_device_id' => 'TERM-02',
            'source_device_label' => 'Warehouse',
            'source_reference' => 'device-ref-old',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
        Attendance::query()->whereKey($oldRecord->id)->update([
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(4),
        ]);

        $this->actingAs($company)->put(
            route('hrm.mozambique-payroll-compliance.attendance-device-settings.update'),
            [
                'enabled' => true,
                'token' => 'device-token-1234567890',
                'default_device_label' => 'Main Gate',
            ]
        )->assertRedirect();

        $tokenSetting = Setting::query()
            ->where('created_by', $company->id)
            ->where('key', 'mz_attendance_device_ingest_token')
            ->first();
        $this->assertNotNull($tokenSetting);
        $this->assertSame('device-token-1234567890', (string) $tokenSetting->value);

        $enabledSetting = Setting::query()
            ->where('created_by', $company->id)
            ->where('key', 'mz_attendance_device_ingest_enabled')
            ->first();
        $this->assertNotNull($enabledSetting);
        $this->assertSame('1', (string) $enabledSetting->value);

        $response = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.attendance-device-health.json'
        ));

        $response->assertOk();
        $response->assertJsonStructure([
            'generated_at',
            'window_start',
            'window_hours',
            'summary' => [
                'total_events_last_24h',
                'unique_devices_last_24h',
                'clockins_last_24h',
                'clockouts_last_24h',
                'open_attendances',
                'last_event_at',
            ],
            'rows',
        ]);
        $response->assertJsonPath('summary.total_events_last_24h', 2);
        $response->assertJsonPath('summary.unique_devices_last_24h', 1);
        $response->assertJsonPath('summary.clockins_last_24h', 2);
        $response->assertJsonPath('summary.clockouts_last_24h', 1);
        $response->assertJsonPath('summary.open_attendances', 1);
        $response->assertSee('Biometric Device Staff', false);
    }

    public function test_enabling_device_ingest_without_any_token_returns_validation_error(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-payrolls']);

        $this->actingAs($company)->put(
            route('hrm.mozambique-payroll-compliance.attendance-device-settings.update'),
            [
                'enabled' => true,
                'token' => '',
                'default_device_label' => '',
            ]
        )->assertSessionHasErrors('token');
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
