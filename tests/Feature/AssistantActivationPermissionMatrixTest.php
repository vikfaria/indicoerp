<?php

namespace Tests\Feature;

use App\Services\AssistantActivation\PermissionMatrixService;
use Tests\TestCase;

class AssistantActivationPermissionMatrixTest extends TestCase
{
    public function test_it_exposes_the_real_permission_matrix(): void
    {
        $service = app(PermissionMatrixService::class);

        $report = $service->buildReport();

        $this->assertSame('2026-06-06', $report['meta']['catalog_version']);
        $this->assertSame(5, $report['summary']['areas_total']);
        $this->assertSame(10, $report['summary']['role_templates_total']);
        $this->assertGreaterThan(0, $report['summary']['permissions_total']);

        $billingArea = collect($report['areas'])->firstWhere('key', 'billing');
        $this->assertNotNull($billingArea);
        $this->assertContains('manage-sales-invoices', $billingArea['permissions']);

        $financeBilling = $service->findRoleTemplate('finance-billing');
        $this->assertNotNull($financeBilling);
        $this->assertSame('Faturacao', $financeBilling['label']);
        $this->assertContains('view-customers', $financeBilling['permissions']);
    }
}
