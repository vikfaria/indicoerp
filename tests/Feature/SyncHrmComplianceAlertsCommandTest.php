<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MozambiqueHrComplianceAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class SyncHrmComplianceAlertsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_syncs_all_company_users_by_default(): void
    {
        $companyA = $this->makeCompany('company-a@example.com');
        $companyB = $this->makeCompany('company-b@example.com');

        User::factory()->create([
            'type' => 'staff',
            'created_by' => $companyA->id,
            'creator_id' => $companyA->id,
        ]);

        $this->mock(MozambiqueHrComplianceAlertService::class, function (MockInterface $mock) use ($companyA, $companyB): void {
            $mock->shouldReceive('syncFromSnapshot')
                ->once()
                ->with((int) $companyA->id)
                ->andReturn($this->mockState(3, 2));

            $mock->shouldReceive('syncFromSnapshot')
                ->once()
                ->with((int) $companyB->id)
                ->andReturn($this->mockState(1, 1));
        });

        $this->artisan('hrm:sync-compliance-alerts')
            ->assertExitCode(0);
    }

    public function test_command_can_target_single_company(): void
    {
        $companyA = $this->makeCompany('company-target@example.com');
        $companyB = $this->makeCompany('company-other@example.com');

        $this->mock(MozambiqueHrComplianceAlertService::class, function (MockInterface $mock) use ($companyA): void {
            $mock->shouldReceive('syncFromSnapshot')
                ->once()
                ->with((int) $companyA->id)
                ->andReturn($this->mockState(2, 1));
        });

        $this->artisan('hrm:sync-compliance-alerts', ['--company_id' => (string) $companyA->id])
            ->assertExitCode(0);

        $this->assertNotEquals($companyA->id, $companyB->id);
    }

    public function test_command_fails_when_company_sync_throws_exception_and_continue_on_error_is_disabled(): void
    {
        $company = $this->makeCompany('company-fail@example.com');

        $this->mock(MozambiqueHrComplianceAlertService::class, function (MockInterface $mock) use ($company): void {
            $mock->shouldReceive('syncFromSnapshot')
                ->once()
                ->with((int) $company->id)
                ->andThrow(new \RuntimeException('snapshot unavailable'));
        });

        $this->artisan('hrm:sync-compliance-alerts')
            ->assertExitCode(1);
    }

    public function test_command_returns_success_when_continue_on_error_option_is_enabled(): void
    {
        $company = $this->makeCompany('company-continue@example.com');

        $this->mock(MozambiqueHrComplianceAlertService::class, function (MockInterface $mock) use ($company): void {
            $mock->shouldReceive('syncFromSnapshot')
                ->once()
                ->with((int) $company->id)
                ->andThrow(new \RuntimeException('temporary failure'));
        });

        $this->artisan('hrm:sync-compliance-alerts --continue-on-error')
            ->assertExitCode(0);
    }

    /**
     * @return array{metrics: array{open_alerts:int, open_high_alerts:int}}
     */
    private function mockState(int $openAlerts, int $openHighAlerts): array
    {
        return [
            'metrics' => [
                'open_alerts' => $openAlerts,
                'open_high_alerts' => $openHighAlerts,
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
}
