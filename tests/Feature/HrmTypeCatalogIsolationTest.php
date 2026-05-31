<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Hrm\Models\AllowanceType;
use Workdo\Hrm\Models\AwardType;
use Workdo\Hrm\Models\ComplaintType;
use Workdo\Hrm\Models\DeductionType;
use Workdo\Hrm\Models\EmployeeDocumentType;
use Workdo\Hrm\Models\EventType;
use Workdo\Hrm\Models\HolidayType;
use Workdo\Hrm\Models\LeaveType;
use Workdo\Hrm\Models\LoanType;
use Workdo\Hrm\Models\TerminationType;
use Workdo\Hrm\Models\WarningType;

class HrmTypeCatalogIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    #[DataProvider('typeCatalogKeys')]
    public function test_type_catalog_update_denies_cross_company_record_access(string $typeKey): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['edit-' . $typeKey . '-types']);

        $record = $this->createTypeRecord($typeKey, $companyB);
        [$table, $column, $originalValue] = $this->originalAssertionData($typeKey);

        $response = $this->actingAs($companyA)->put(
            route($this->routeName($typeKey, 'update'), $record->id),
            $this->updatePayload($typeKey)
        );

        $response->assertRedirect(route($this->routeName($typeKey, 'index')));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas($table, [
            'id' => $record->id,
            'created_by' => $companyB->id,
            $column => $originalValue,
        ]);
    }

    #[DataProvider('typeCatalogKeys')]
    public function test_type_catalog_destroy_denies_cross_company_record_access(string $typeKey): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['delete-' . $typeKey . '-types']);

        $record = $this->createTypeRecord($typeKey, $companyB);
        [$table, $column, $originalValue] = $this->originalAssertionData($typeKey);

        $response = $this->actingAs($companyA)->delete(route($this->routeName($typeKey, 'destroy'), $record->id));

        $response->assertRedirect(route($this->routeName($typeKey, 'index')));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas($table, [
            'id' => $record->id,
            'created_by' => $companyB->id,
            $column => $originalValue,
        ]);
    }

    public static function typeCatalogKeys(): array
    {
        return [
            ['allowance'],
            ['award'],
            ['complaint'],
            ['deduction'],
            ['employee-document'],
            ['event'],
            ['holiday'],
            ['leave'],
            ['loan'],
            ['termination'],
            ['warning'],
        ];
    }

    private function createTypeRecord(string $typeKey, User $company): Model
    {
        return match ($typeKey) {
            'allowance' => AllowanceType::query()->create([
                'name' => 'Allowance B',
                'description' => 'External allowance type',
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]),
            'award' => AwardType::query()->create([
                'name' => 'Award B',
                'description' => 'External award type',
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]),
            'complaint' => ComplaintType::query()->create([
                'complaint_type' => 'Complaint B',
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]),
            'deduction' => DeductionType::query()->create([
                'name' => 'Deduction B',
                'description' => 'External deduction type',
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]),
            'employee-document' => EmployeeDocumentType::query()->create([
                'document_name' => 'Employee ID B',
                'description' => 'External document type',
                'is_required' => true,
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]),
            'event' => EventType::query()->create([
                'event_type' => 'Event B',
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]),
            'holiday' => HolidayType::query()->create([
                'holiday_type' => 'Holiday B',
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]),
            'leave' => LeaveType::query()->create([
                'name' => 'Leave B',
                'description' => 'External leave type',
                'max_days_per_year' => 20,
                'is_paid' => true,
                'color' => '#10b981',
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]),
            'loan' => LoanType::query()->create([
                'name' => 'Loan B',
                'description' => 'External loan type',
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]),
            'termination' => TerminationType::query()->create([
                'termination_type' => 'Termination B',
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]),
            'warning' => WarningType::query()->create([
                'warning_type_name' => 'Warning B',
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]),
            default => throw new \InvalidArgumentException('Unknown type key: ' . $typeKey),
        };
    }

    private function updatePayload(string $typeKey): array
    {
        return match ($typeKey) {
            'allowance' => ['name' => 'Allowance A Updated', 'description' => 'Updated by invalid company'],
            'award' => ['name' => 'Award A Updated', 'description' => 'Updated by invalid company'],
            'complaint' => ['complaint_type' => 'Complaint A Updated'],
            'deduction' => ['name' => 'Deduction A Updated', 'description' => 'Updated by invalid company'],
            'employee-document' => ['document_name' => 'Employee ID A Updated', 'description' => 'Updated by invalid company', 'is_required' => false],
            'event' => ['event_type' => 'Event A Updated'],
            'holiday' => ['holiday_type' => 'Holiday A Updated'],
            'leave' => [
                'name' => 'Leave A Updated',
                'legal_code' => null,
                'max_days_per_year' => 18,
                'is_paid' => true,
                'requires_supporting_document' => false,
                'must_be_consecutive' => false,
                'fixed_duration_days' => null,
                'min_advance_notice_days' => 0,
                'pre_event_start_window_days' => null,
                'post_event_start_offset_days' => null,
                'allow_cash_out' => false,
                'min_effective_rest_days' => null,
                'color' => '#3b82f6',
                'description' => 'Updated by invalid company',
            ],
            'loan' => ['name' => 'Loan A Updated', 'description' => 'Updated by invalid company'],
            'termination' => ['termination_type' => 'Termination A Updated'],
            'warning' => ['warning_type_name' => 'Warning A Updated'],
            default => throw new \InvalidArgumentException('Unknown type key: ' . $typeKey),
        };
    }

    private function originalAssertionData(string $typeKey): array
    {
        return match ($typeKey) {
            'allowance' => ['allowance_types', 'name', 'Allowance B'],
            'award' => ['award_types', 'name', 'Award B'],
            'complaint' => ['complaint_types', 'complaint_type', 'Complaint B'],
            'deduction' => ['deduction_types', 'name', 'Deduction B'],
            'employee-document' => ['employee_document_types', 'document_name', 'Employee ID B'],
            'event' => ['event_types', 'event_type', 'Event B'],
            'holiday' => ['holiday_types', 'holiday_type', 'Holiday B'],
            'leave' => ['leave_types', 'name', 'Leave B'],
            'loan' => ['loan_types', 'name', 'Loan B'],
            'termination' => ['termination_types', 'termination_type', 'Termination B'],
            'warning' => ['warning_types', 'warning_type_name', 'Warning B'],
            default => throw new \InvalidArgumentException('Unknown type key: ' . $typeKey),
        };
    }

    private function routeName(string $typeKey, string $action): string
    {
        return sprintf('hrm.%s-types.%s', $typeKey, $action);
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
