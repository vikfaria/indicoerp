<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CompanyProgressOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_superadmin_can_view_the_company_progress_overview(): void
    {
        $this->createPermission();
        $company = $this->makeCompany();
        $superAdmin = $this->makeSuperAdmin();

        $response = $this->actingAs($superAdmin)->get(route('assistant-activation.company-progress.index'));

        $response->assertOk();
        $response->assertInertia(function (Assert $page) use ($company): void {
            $page->component('assistant-activation/company-progress')
                ->has('overview.companies', 1)
                ->has('overview.metrics')
                ->has('overview.selected_company')
                ->where('overview.summary.companies_total', 1)
                ->where('overview.summary.companies_in_view', 1)
                ->where('overview.metrics.summary.companies_total', 1)
                ->where('overview.companies.0.id', $company->id)
                ->where('overview.companies.0.name', $company->name)
                ->where('overview.companies.0.select_url', route('assistant-activation.company-progress.index', ['company_id' => $company->id]))
                ->where('overview.selected_company.id', $company->id)
                ->where('overview.selected_company.snapshot.meta.company_name', $company->name)
                ->where('overview.selected_company.snapshot.meta.session_status', 'not_started')
                ->has('overview.selected_company.snapshot.summary')
                ->has('overview.selected_company.top_blocks');
        });
    }

    public function test_authorized_consultant_can_view_the_company_progress_overview(): void
    {
        $permission = $this->createPermission();
        $company = $this->makeCompany();
        $consultant = $this->makeConsultant();
        $consultant->givePermissionTo($permission);

        $response = $this->actingAs($consultant)
            ->withSession(['company_role_checked' => true])
            ->get(route('assistant-activation.company-progress.index'));

        $response->assertOk();
        $response->assertInertia(function (Assert $page) use ($company): void {
            $page->component('assistant-activation/company-progress')
                ->has('overview.companies', 1)
                ->where('overview.summary.ready_companies', fn (mixed $value): bool => is_numeric($value))
                ->where('overview.selected_company.id', $company->id)
                ->where('overview.selected_company.snapshot.meta.company_name', $company->name);
        });
    }

    public function test_company_users_without_permission_are_forbidden_from_the_company_progress_overview(): void
    {
        $this->createPermission();
        $this->makeCompany();
        $companyUser = $this->makeCompanyUserWithoutPermission();

        $response = $this->actingAs($companyUser)
            ->withSession(['company_role_checked' => true])
            ->get(route('assistant-activation.company-progress.index'));

        $response->assertForbidden();
    }

    public function test_dashboard_redirects_consultants_to_the_company_progress_mode(): void
    {
        $permission = $this->createPermission();
        $consultant = $this->makeConsultant();
        $consultant->givePermissionTo($permission);

        $response = $this->actingAs($consultant)
            ->withSession(['company_role_checked' => true])
            ->get(route('dashboard'));

        $response->assertRedirect(route('assistant-activation.company-progress.index'));
    }

    private function createPermission(): Permission
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'view-company-onboarding-progress', 'guard_name' => 'web'],
            [
                'module' => 'assistant-activation',
                'label' => 'View Company Onboarding Progress',
                'add_on' => 'general',
            ]
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $permission;
    }

    private function makeSuperAdmin(): User
    {
        return User::forceCreate([
            'name' => 'Super Admin',
            'email' => 'superadmin+company-progress@example.com',
            'email_verified_at' => now(),
            'password' => 'password',
            'type' => 'superadmin',
            'lang' => 'en',
            'total_user' => -1,
        ]);
    }

    private function makeConsultant(): User
    {
        $plan = $this->createPlan();

        return User::forceCreate([
            'name' => 'Consultor',
            'email' => 'consultant+company-progress@example.com',
            'email_verified_at' => now(),
            'password' => 'password',
            'type' => 'consultant',
            'active_plan' => $plan->id,
            'plan_expire_date' => now()->addMonth(),
            'trial_expire_date' => null,
            'created_by' => null,
            'creator_id' => null,
        ]);
    }

    private function makeCompanyUserWithoutPermission(): User
    {
        $plan = $this->createPlan();

        return User::forceCreate([
            'name' => 'Empresa Sem Permissão',
            'email' => 'company-without-permission@example.com',
            'email_verified_at' => now(),
            'password' => 'password',
            'type' => 'company',
            'active_plan' => $plan->id,
            'plan_expire_date' => now()->addMonth(),
            'trial_expire_date' => null,
            'created_by' => null,
            'creator_id' => null,
        ]);
    }

    private function makeCompany(): User
    {
        $plan = $this->createPlan();

        return User::forceCreate([
            'name' => 'Empresa Progress',
            'email' => 'company-progress@example.com',
            'email_verified_at' => now(),
            'password' => 'password',
            'type' => 'company',
            'active_plan' => $plan->id,
            'plan_expire_date' => now()->addMonth(),
            'trial_expire_date' => null,
            'created_by' => null,
            'creator_id' => null,
        ]);
    }

    private function createPlan(): Plan
    {
        return Plan::firstOrCreate(
            ['name' => 'Professional Plan'],
            [
                'status' => true,
                'free_plan' => false,
                'modules' => ['Account', 'ProductService', 'DoubleEntry', 'Hrm'],
                'package_price_yearly' => 960,
                'package_price_monthly' => 99,
                'storage_limit' => 51200,
                'trial' => true,
                'trial_days' => 30,
                'number_of_users' => 100,
            ]
        );
    }
}
