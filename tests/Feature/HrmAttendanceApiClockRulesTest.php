<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Workdo\Hrm\Models\Attendance;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Shift;

class HrmAttendanceApiClockRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_clock_in_api_uses_weekday_fallback_when_working_days_setting_is_missing(): void
    {
        Carbon::setTestNow('2026-06-03 08:00:00'); // Wednesday

        try {
            $company = $this->makeCompany();
            $staff = $this->makeStaff($company, 'API Clock User');

            $shift = Shift::query()->create([
                'shift_name' => 'API Default Shift',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]);

            Employee::query()->create([
                'employee_id' => 'API-CLOCK-001',
                'user_id' => $staff->id,
                'shift' => $shift->id,
                'employment_type' => 'GENERAL',
                'basic_salary' => 9000,
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]);

            $response = $this->actingAs($staff)
                ->postJson('/api/hrm/clock-in-out', [
                    'type' => 'clockin',
                ]);

            $response->assertOk();
            $response->assertJsonPath('success', true);
            $response->assertJsonPath('message', 'Clocked in successfully.');

            $this->assertDatabaseHas('attendances', [
                'employee_id' => $staff->id,
                'created_by' => $company->id,
            ]);
            $this->assertSame(1, Attendance::query()->count());
        } finally {
            Carbon::setTestNow();
        }
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
