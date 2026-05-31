<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Recruitment\Models\CandidateSources;
use Workdo\Recruitment\Models\JobPosting;

class RecruitmentMozambiqueComplianceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_rejects_candidate_younger_than_twelve_years_old(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-candidates']);

        $source = $this->makeCandidateSource($company);
        $jobPosting = $this->makeJobPosting($company);

        $response = $this->actingAs($company)->post(
            route('recruitment.candidates.store'),
            $this->candidatePayload($jobPosting, $source, [
                'email' => 'under12@example.com',
                'dob' => Carbon::now()->subYears(11)->toDateString(),
            ])
        );

        $response->assertSessionHasErrors(['dob']);
        $this->assertDatabaseCount('candidates', 0);
    }

    public function test_requires_minor_authorization_for_candidates_between_twelve_and_fifteen(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-candidates']);

        $source = $this->makeCandidateSource($company);
        $jobPosting = $this->makeJobPosting($company);
        $minorDob = Carbon::now()->subYears(13)->toDateString();

        $invalidResponse = $this->actingAs($company)->post(
            route('recruitment.candidates.store'),
            $this->candidatePayload($jobPosting, $source, [
                'email' => 'minor-missing-docs@example.com',
                'dob' => $minorDob,
                'minor_work_authorization_path' => '',
                'legal_exception_notes' => '',
            ])
        );

        $invalidResponse->assertSessionHasErrors([
            'minor_work_authorization_path',
            'legal_exception_notes',
        ]);

        $validResponse = $this->actingAs($company)->post(
            route('recruitment.candidates.store'),
            $this->candidatePayload($jobPosting, $source, [
                'email' => 'minor-with-docs@example.com',
                'dob' => $minorDob,
                'minor_work_authorization_path' => '/docs/minors/authorization-001.pdf',
                'legal_exception_notes' => 'Autorizacao especial registrada para trabalho excepcional.',
            ])
        );

        $validResponse->assertRedirect(route('recruitment.candidates.index'));
        $this->assertDatabaseHas('candidates', [
            'email' => 'minor-with-docs@example.com',
            'minor_work_authorization_path' => '/docs/minors/authorization-001.pdf',
            'created_by' => $company->id,
        ]);
    }

    public function test_requires_professional_license_for_regulated_profession_candidates(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-candidates']);

        $source = $this->makeCandidateSource($company);
        $jobPosting = $this->makeJobPosting($company);

        $invalidResponse = $this->actingAs($company)->post(
            route('recruitment.candidates.store'),
            $this->candidatePayload($jobPosting, $source, [
                'email' => 'regulated-missing-license@example.com',
                'is_regulated_profession' => true,
                'professional_license_type' => '',
                'professional_license_number' => '',
            ])
        );

        $invalidResponse->assertSessionHasErrors([
            'professional_license_type',
            'professional_license_number',
        ]);

        $validResponse = $this->actingAs($company)->post(
            route('recruitment.candidates.store'),
            $this->candidatePayload($jobPosting, $source, [
                'email' => 'regulated-with-license@example.com',
                'is_regulated_profession' => true,
                'professional_license_type' => 'Ordem Profissional',
                'professional_license_number' => 'OP-223344',
                'professional_license_expiry_date' => Carbon::now()->addYear()->toDateString(),
            ])
        );

        $validResponse->assertRedirect(route('recruitment.candidates.index'));
        $this->assertDatabaseHas('candidates', [
            'email' => 'regulated-with-license@example.com',
            'is_regulated_profession' => true,
            'professional_license_number' => 'OP-223344',
        ]);
    }

    public function test_blocks_discriminatory_custom_question_creation(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-custom-questions']);

        $response = $this->actingAs($company)->post(
            route('recruitment.custom-questions.store'),
            [
                'question' => 'Qual e o seu estado civil?',
                'type' => 'text',
                'options' => null,
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $response->assertSessionHasErrors(['question']);
        $this->assertDatabaseMissing('custom_questions', [
            'question' => 'Qual e o seu estado civil?',
            'created_by' => $company->id,
        ]);

        $allowedResponse = $this->actingAs($company)->post(
            route('recruitment.custom-questions.store'),
            [
                'question' => 'Descreva a sua experiencia na funcao.',
                'type' => 'text',
                'options' => null,
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $allowedResponse->assertRedirect(route('recruitment.custom-questions.index'));
        $this->assertDatabaseHas('custom_questions', [
            'question' => 'Descreva a sua experiencia na funcao.',
            'created_by' => $company->id,
        ]);
    }

    private function candidatePayload(JobPosting $jobPosting, CandidateSources $source, array $overrides = []): array
    {
        $base = [
            'first_name' => 'Ana',
            'last_name' => 'Mabunda',
            'email' => 'ana.mabunda@example.com',
            'phone' => '',
            'gender' => 'female',
            'dob' => Carbon::now()->subYears(25)->toDateString(),
            'nationality' => 'MZ',
            'identification_document_type' => 'bi',
            'identification_document_number' => '1101020304050A',
            'nuit' => '400123456',
            'desired_professional_category' => 'Contabilidade',
            'is_regulated_profession' => false,
            'professional_license_type' => '',
            'professional_license_number' => '',
            'professional_license_expiry_date' => null,
            'minor_work_authorization_path' => '',
            'legal_exception_notes' => '',
            'country' => 'Mozambique',
            'state' => 'Maputo',
            'city' => 'Maputo',
            'current_company' => '',
            'current_position' => '',
            'experience_years' => '3',
            'current_salary' => '',
            'expected_salary' => '',
            'notice_period' => '',
            'skills' => '',
            'education' => '',
            'portfolio_url' => '',
            'linkedin_url' => '',
            'status' => '0',
            'application_date' => Carbon::today()->toDateString(),
            'custom_question' => '',
            'job_id' => $jobPosting->id,
            'source_id' => $source->id,
        ];

        return array_merge($base, $overrides);
    }

    private function makeJobPosting(User $company): JobPosting
    {
        return JobPosting::query()->create([
            'code' => 'JP-' . $company->id . '-001',
            'title' => 'Contabilista',
            'position' => 1,
            'is_published' => true,
            'status' => 'active',
            'job_application' => 'existing',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeCandidateSource(User $company): CandidateSources
    {
        return CandidateSources::query()->create([
            'name' => 'LinkedIn',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeCompany(): User
    {
        return User::factory()->create([
            'type' => 'company',
        ]);
    }

    private function grantPermissions(User $user, array $permissions): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'add_on' => 'recruitment',
                    'module' => 'recruitment',
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
