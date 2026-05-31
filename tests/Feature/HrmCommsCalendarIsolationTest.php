<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Hrm\Models\Announcement;
use Workdo\Hrm\Models\AnnouncementCategory;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;
use Workdo\Hrm\Models\Event;
use Workdo\Hrm\Models\EventType;
use Workdo\Hrm\Models\Holiday;
use Workdo\Hrm\Models\HolidayType;
use Workdo\Hrm\Models\IpRestrict;

class HrmCommsCalendarIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_announcement_category_update_denies_cross_company_record_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $this->grantPermissions($companyA, ['edit-announcement-categories']);

        $categoryB = $this->makeAnnouncementCategory($companyB, 'Category B');

        $response = $this->actingAs($companyA)->put(route('hrm.announcement-categories.update', $categoryB->id), [
            'announcement_category' => 'Updated by A',
        ]);

        $response->assertRedirect(route('hrm.announcement-categories.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('announcement_categories', [
            'id' => $categoryB->id,
            'announcement_category' => 'Category B',
            'created_by' => $companyB->id,
        ]);
    }

    public function test_announcement_store_rejects_cross_company_category_and_department_references(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $this->grantPermissions($companyA, ['create-announcements']);

        $categoryB = $this->makeAnnouncementCategory($companyB, 'External Category');
        $branchB = $this->makeBranch($companyB, 'Branch B');
        $departmentB = $this->makeDepartment($companyB, $branchB, 'Department B');

        $response = $this->actingAs($companyA)->post(route('hrm.announcements.store'), [
            'title' => 'Cross-company announcement',
            'announcement_category_id' => $categoryB->id,
            'departments' => [$departmentB->id],
            'description' => 'Cross-company references should fail.',
            'priority' => 'high',
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
        ]);

        $response->assertSessionHasErrors(['announcement_category_id', 'departments.0']);
        $this->assertDatabaseMissing('announcements', [
            'title' => 'Cross-company announcement',
            'created_by' => $companyA->id,
        ]);
    }

    public function test_announcement_status_update_denies_cross_company_record_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $this->grantPermissions($companyA, ['manage-announcements-status']);

        $announcementB = $this->makeAnnouncement($companyB, 'Announcement B');

        $response = $this->actingAs($companyA)->put(route('hrm.announcements.update-status', $announcementB->id), [
            'status' => 'active',
        ]);

        $response->assertRedirect(route('hrm.announcements.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('announcements', [
            'id' => $announcementB->id,
            'status' => 'draft',
            'created_by' => $companyB->id,
        ]);
    }

    public function test_event_store_rejects_cross_company_event_type_and_department_references(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $this->grantPermissions($companyA, ['create-events']);

        $eventTypeB = $this->makeEventType($companyB, 'External Event Type');
        $branchB = $this->makeBranch($companyB, 'Branch B');
        $departmentB = $this->makeDepartment($companyB, $branchB, 'Department B');

        $response = $this->actingAs($companyA)->post(route('hrm.events.store'), [
            'title' => 'Cross-company event',
            'event_type_id' => $eventTypeB->id,
            'departments' => [$departmentB->id],
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addDays(1)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'location' => 'Maputo',
            'description' => 'Cross-company references should fail.',
            'color' => '#2563EB',
        ]);

        $response->assertSessionHasErrors(['event_type_id', 'departments.0']);
        $this->assertDatabaseMissing('events', [
            'title' => 'Cross-company event',
            'created_by' => $companyA->id,
        ]);
    }

    public function test_event_status_update_denies_cross_company_record_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $this->grantPermissions($companyA, ['manage-event-status']);

        $eventB = $this->makeEvent($companyB, 'Event B');

        $response = $this->actingAs($companyA)->put(route('hrm.events.status-update', $eventB->id), [
            'status' => 'approved',
        ]);

        $response->assertRedirect(route('hrm.events.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('events', [
            'id' => $eventB->id,
            'status' => 'pending',
            'created_by' => $companyB->id,
        ]);
    }

    public function test_holiday_store_rejects_cross_company_holiday_type_reference(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $this->grantPermissions($companyA, ['create-holidays']);

        $holidayTypeB = $this->makeHolidayType($companyB, 'External Holiday Type');

        $response = $this->actingAs($companyA)->post(route('hrm.holidays.store'), [
            'name' => 'Cross-company holiday',
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addDays(1)->toDateString(),
            'holiday_type_id' => $holidayTypeB->id,
            'description' => 'Cross-company references should fail.',
            'is_paid' => true,
            'is_sync_google_calendar' => false,
            'is_sync_outlook_calendar' => false,
        ]);

        $response->assertSessionHasErrors(['holiday_type_id']);
        $this->assertDatabaseMissing('holidays', [
            'name' => 'Cross-company holiday',
            'created_by' => $companyA->id,
        ]);
    }

    public function test_holiday_update_denies_cross_company_record_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $this->grantPermissions($companyA, ['edit-holidays']);

        $holidayTypeA = $this->makeHolidayType($companyA, 'Holiday Type A');
        $holidayB = $this->makeHoliday($companyB, 'Holiday B');

        $response = $this->actingAs($companyA)->put(route('hrm.holidays.update', $holidayB->id), [
            'name' => 'Updated by A',
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'holiday_type_id' => $holidayTypeA->id,
            'description' => 'Should be blocked',
            'is_paid' => true,
            'is_sync_google_calendar' => false,
            'is_sync_outlook_calendar' => false,
        ]);

        $response->assertRedirect(route('hrm.holidays.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('holidays', [
            'id' => $holidayB->id,
            'name' => 'Holiday B',
            'created_by' => $companyB->id,
        ]);
    }

    public function test_ip_restrict_destroy_denies_cross_company_record_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $this->grantPermissions($companyA, ['delete-ip-restricts']);

        $ipRestrictB = IpRestrict::query()->create([
            'ip' => '203.0.113.10',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->delete(route('hrm.ip-restricts.destroy', $ipRestrictB->id));

        $response->assertRedirect(route('hrm.ip-restricts.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('ip_restricts', [
            'id' => $ipRestrictB->id,
            'created_by' => $companyB->id,
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

    private function makeAnnouncementCategory(User $company, string $name): AnnouncementCategory
    {
        return AnnouncementCategory::query()->create([
            'announcement_category' => $name,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeAnnouncement(User $company, string $title): Announcement
    {
        return Announcement::query()->create([
            'title' => $title,
            'announcement_category_id' => $this->makeAnnouncementCategory($company, $title . ' Category')->id,
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'priority' => 'high',
            'status' => 'draft',
            'description' => $title . ' description',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeEventType(User $company, string $name): EventType
    {
        return EventType::query()->create([
            'event_type' => $name,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeEvent(User $company, string $title): Event
    {
        return Event::query()->create([
            'title' => $title,
            'event_type_id' => $this->makeEventType($company, $title . ' Type')->id,
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addDays(1)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'location' => 'Maputo',
            'status' => 'pending',
            'description' => $title . ' description',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeHolidayType(User $company, string $name): HolidayType
    {
        return HolidayType::query()->create([
            'holiday_type' => $name,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeHoliday(User $company, string $name): Holiday
    {
        return Holiday::query()->create([
            'name' => $name,
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addDays(1)->toDateString(),
            'holiday_type_id' => $this->makeHolidayType($company, $name . ' Type')->id,
            'description' => $name . ' description',
            'is_paid' => true,
            'is_sync_google_calendar' => false,
            'is_sync_outlook_calendar' => false,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeBranch(User $company, string $name): Branch
    {
        return Branch::query()->create([
            'branch_name' => $name,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeDepartment(User $company, Branch $branch, string $name): Department
    {
        return Department::query()->create([
            'department_name' => $name,
            'branch_id' => $branch->id,
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
