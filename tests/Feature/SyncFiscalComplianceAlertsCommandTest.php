<?php

namespace Tests\Feature;

use App\Models\CompanyFiscalProfile;
use App\Models\User;
use App\Services\MozambiqueFiscalComplianceAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class SyncFiscalComplianceAlertsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_syncs_all_company_profiles_by_default(): void
    {
        $companyA = $this->makeCompany('company-a@example.com');
        $companyB = $this->makeCompany('company-b@example.com');

        $this->makeProfile($companyA, 'Empresa A');
        $this->makeProfile($companyB, 'Empresa B');

        $expectedFilters = $this->expectedFilters();

        $this->mock(MozambiqueFiscalComplianceAlertService::class, function (MockInterface $mock) use ($companyA, $companyB, $expectedFilters): void {
            $mock->shouldReceive('syncFromReport')
                ->once()
                ->with((int) $companyA->id, $expectedFilters)
                ->andReturn($this->mockState(3, 2));

            $mock->shouldReceive('syncFromReport')
                ->once()
                ->with((int) $companyB->id, $expectedFilters)
                ->andReturn($this->mockState(1, 1));
        });

        $this->artisan('sce:sync-fiscal-compliance-alerts')
            ->assertExitCode(0);
    }

    public function test_command_can_target_single_company_profile(): void
    {
        $companyA = $this->makeCompany('company-target@example.com');
        $companyB = $this->makeCompany('company-other@example.com');

        $this->makeProfile($companyA, 'Empresa Alvo');
        $this->makeProfile($companyB, 'Empresa Outra');

        $expectedFilters = $this->expectedFilters();

        $this->mock(MozambiqueFiscalComplianceAlertService::class, function (MockInterface $mock) use ($companyA, $expectedFilters): void {
            $mock->shouldReceive('syncFromReport')
                ->once()
                ->with((int) $companyA->id, $expectedFilters)
                ->andReturn($this->mockState(2, 1));
        });

        $this->artisan('sce:sync-fiscal-compliance-alerts', ['--company_id' => (string) $companyA->id])
            ->assertExitCode(0);
    }

    public function test_command_fails_when_company_sync_throws_exception_and_continue_on_error_is_disabled(): void
    {
        $company = $this->makeCompany('company-fail@example.com');
        $this->makeProfile($company, 'Empresa Falha');

        $expectedFilters = $this->expectedFilters();

        $this->mock(MozambiqueFiscalComplianceAlertService::class, function (MockInterface $mock) use ($company, $expectedFilters): void {
            $mock->shouldReceive('syncFromReport')
                ->once()
                ->with((int) $company->id, $expectedFilters)
                ->andThrow(new \RuntimeException('snapshot unavailable'));
        });

        $this->artisan('sce:sync-fiscal-compliance-alerts')
            ->assertExitCode(1);
    }

    public function test_command_returns_success_when_continue_on_error_option_is_enabled(): void
    {
        $company = $this->makeCompany('company-continue@example.com');
        $this->makeProfile($company, 'Empresa Continua');

        $expectedFilters = $this->expectedFilters();

        $this->mock(MozambiqueFiscalComplianceAlertService::class, function (MockInterface $mock) use ($company, $expectedFilters): void {
            $mock->shouldReceive('syncFromReport')
                ->once()
                ->with((int) $company->id, $expectedFilters)
                ->andThrow(new \RuntimeException('temporary failure'));
        });

        $this->artisan('sce:sync-fiscal-compliance-alerts --continue-on-error')
            ->assertExitCode(0);
    }

    /**
     * @return array{from_date:string,to_date:string,due_soon_days:int}
     */
    private function expectedFilters(): array
    {
        return [
            'from_date' => now()->startOfYear()->toDateString(),
            'to_date' => now()->endOfYear()->toDateString(),
            'due_soon_days' => 7,
        ];
    }

    /**
     * @return array{metrics: array{open_alerts:int, open_critical_alerts:int}}
     */
    private function mockState(int $openAlerts, int $openCriticalAlerts): array
    {
        return [
            'metrics' => [
                'open_alerts' => $openAlerts,
                'open_critical_alerts' => $openCriticalAlerts,
            ],
        ];
    }

    private function makeCompany(string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'type' => 'company',
            'created_by' => null,
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);
    }

    private function makeProfile(User $company, string $legalName): CompanyFiscalProfile
    {
        return CompanyFiscalProfile::create([
            'company_id' => $company->id,
            'legal_name' => $legalName,
            'accounting_framework' => 'pgc_nirf',
            'fiscal_regime' => 'normal',
            'entity_classification' => 'small',
            'taxpayer_type' => 'ordinary',
            'state_of_certification' => 'not_certified',
            'software_certificate_number' => '0',
            'is_active' => true,
            'created_by' => $company->id,
        ]);
    }
}
