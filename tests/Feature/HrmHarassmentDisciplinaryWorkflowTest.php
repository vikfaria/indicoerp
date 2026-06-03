<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Hrm\Models\Complaint;
use Workdo\Hrm\Models\ComplaintType;
use Workdo\Hrm\Models\Warning;

class HrmHarassmentDisciplinaryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_harassment_complaint_creates_linked_disciplinary_warning_when_owner_is_defined(): void
    {
        $company = $this->makeCompany();
        $complainant = $this->makeStaff($company, 'Complainant User');
        $accused = $this->makeStaff($company, 'Accused User');
        $owner = $this->makeStaff($company, 'HR Owner');

        $this->grantPermissions($company, ['create-complaints', 'manage-complaints']);

        $complaintType = ComplaintType::query()->create([
            'complaint_type' => 'Harassment',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->post(route('hrm.complaints.store'), [
            'employee_id' => $complainant->id,
            'against_employee_id' => $accused->id,
            'complaint_type_id' => $complaintType->id,
            'subject' => 'Workplace harassment complaint',
            'description' => 'Detailed complaint for disciplinary workflow test.',
            'complaint_date' => '2026-05-31',
            'is_harassment_report' => true,
            'is_confidential' => true,
            'confidential_channel' => 'internal_hotline',
            'confidentiality_level' => 'restricted',
            'handling_owner_id' => $owner->id,
        ]);

        $response->assertRedirect(route('hrm.complaints.index'));

        $complaint = Complaint::query()->firstOrFail();
        $this->assertNotNull($complaint->disciplinary_warning_id);
        $this->assertNotNull($complaint->disciplinary_case_opened_at);

        $warning = Warning::query()->findOrFail((int) $complaint->disciplinary_warning_id);
        $this->assertSame($company->id, (int) $warning->created_by);
        $this->assertSame($accused->id, (int) $warning->employee_id);
        $this->assertSame($owner->id, (int) $warning->warning_by);
        $this->assertSame('high', (string) $warning->severity);
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
