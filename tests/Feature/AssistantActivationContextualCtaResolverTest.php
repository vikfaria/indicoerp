<?php

namespace Tests\Feature;

use App\Services\AssistantActivation\ContextualCtaResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantActivationContextualCtaResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_contextual_ctas_for_blocks_and_recommendations(): void
    {
        $resolver = app(ContextualCtaResolverService::class);

        $configCta = $resolver->forBlock([
            'type' => 'config_missing',
            'key' => 'fiscal_profile',
            'label' => 'Perfil fiscal',
            'message' => 'Perfil fiscal em falta.',
        ]);

        $addonCta = $resolver->forRecommendation([
            'action' => 'activate_addon',
            'label' => 'Activar add-on',
            'message' => 'Active o add-on necessário.',
        ], [
            'key' => 'billing.invoice.create',
            'type' => 'feature',
        ]);

        $configRecommendationCta = $resolver->forRecommendation([
            'action' => 'complete_configuration',
            'label' => 'Completar configuração',
            'message' => 'Complete a configuração.',
        ], [
            'key' => 'billing.invoice.create',
            'type' => 'feature',
            'missing_config_keys' => ['fiscal_profile'],
        ]);

        $supportCta = $resolver->forRecommendation([
            'action' => 'contact_support',
            'label' => 'Contactar suporte',
            'message' => 'Abra um pedido de suporte.',
        ], [
            'key' => 'billing.invoice.create',
            'type' => 'feature',
        ]);

        $this->assertSame('complete_configuration', $configCta['action']);
        $this->assertSame('Configurar perfil fiscal', $configCta['label']);
        $this->assertSame(route('sce.fiscal.index'), $configCta['href']);

        $this->assertSame('activate_addon', $addonCta['action']);
        $this->assertSame(route('add-ons.index'), $addonCta['href']);
        $this->assertSame('Activar add-on', $addonCta['label']);

        $this->assertSame('complete_configuration', $configRecommendationCta['action']);
        $this->assertSame('Configurar perfil fiscal', $configRecommendationCta['label']);
        $this->assertSame(route('sce.fiscal.index'), $configRecommendationCta['href']);

        $this->assertSame('contact_support', $supportCta['action']);
        $this->assertSame(route('helpdesk-tickets.create'), $supportCta['href']);
        $this->assertSame('Contactar suporte', $supportCta['label']);
    }

    public function test_it_resolves_mozambique_fiscal_compliance_ctas_to_the_specific_setup_route(): void
    {
        $resolver = app(ContextualCtaResolverService::class);

        $seriesCta = $resolver->forBlock([
            'type' => 'config_missing',
            'key' => 'mozambique_fiscal_compliance',
            'reason' => 'missing_series',
            'details' => [
                'missing_items' => ['document_series'],
            ],
            'label' => 'Conformidade fiscal Moçambique',
            'message' => 'Séries documentais em falta.',
        ]);

        $this->assertSame('complete_configuration', $seriesCta['action']);
        $this->assertSame('Criar séries documentais', $seriesCta['label']);
        $this->assertSame(route('sce.fiscal.series'), $seriesCta['href']);
        $this->assertStringContainsString('séries documentais', $seriesCta['message']);
    }

    public function test_it_resolves_treasury_ctas_to_the_correct_workflows(): void
    {
        $resolver = app(ContextualCtaResolverService::class);

        $bankAccountsCta = $resolver->forBlock([
            'type' => 'config_missing',
            'key' => 'bank_accounts',
            'label' => 'Contas bancárias',
            'message' => 'Contas bancárias em falta.',
        ]);

        $paymentMethodsCta = $resolver->forBlock([
            'type' => 'config_missing',
            'key' => 'payment_methods',
            'label' => 'Meios de pagamento',
            'message' => 'Meios de pagamento em falta.',
        ]);

        $reconciliationCta = $resolver->forBlock([
            'type' => 'config_missing',
            'key' => 'reconciliation_rules',
            'label' => 'Reconciliação',
            'message' => 'Regras de reconciliação em falta.',
        ]);

        $initialPaymentsCta = $resolver->forBlock([
            'type' => 'step_incomplete',
            'key' => 'treasury.record_initial_payments',
            'label' => 'Registar pagamentos iniciais',
            'message' => 'Pagamentos iniciais pendentes.',
        ]);

        $this->assertSame(route('account.bank-accounts.index'), $bankAccountsCta['href']);
        $this->assertSame('Configurar métodos de pagamento', $paymentMethodsCta['label']);
        $this->assertSame(route('settings.index') . '#company-settings', $paymentMethodsCta['href']);
        $this->assertSame(route('account.bank-transactions.index'), $reconciliationCta['href']);
        $this->assertSame(route('account.customer-payments.index'), $initialPaymentsCta['href']);
    }

    public function test_it_resolves_hr_payroll_contribution_ctas_to_compliance_setup(): void
    {
        $resolver = app(ContextualCtaResolverService::class);

        $payrollContributionsCta = $resolver->forBlock([
            'type' => 'config_missing',
            'key' => 'payroll_contributions',
            'label' => 'Contribuições da folha',
            'message' => 'Contribuições da folha em falta.',
        ]);

        $this->assertSame('complete_configuration', $payrollContributionsCta['action']);
        $this->assertSame('Configurar contribuições da folha', $payrollContributionsCta['label']);
        $this->assertSame(route('hrm.mozambique-payroll-compliance.index'), $payrollContributionsCta['href']);
    }

    public function test_it_resolves_inventory_and_pos_ctas_to_the_correct_workflows(): void
    {
        $resolver = app(ContextualCtaResolverService::class);

        $warehouseCta = $resolver->forBlock([
            'type' => 'config_missing',
            'key' => 'warehouses',
            'label' => 'Armazéns',
            'message' => 'Armazéns em falta.',
        ]);

        $initialStockCta = $resolver->forBlock([
            'type' => 'config_missing',
            'key' => 'initial_stock',
            'label' => 'Stock inicial',
            'message' => 'Stock inicial em falta.',
        ]);

        $fifoLayersCta = $resolver->forBlock([
            'type' => 'config_missing',
            'key' => 'fifo_layers',
            'label' => 'FIFO',
            'message' => 'FIFO em falta.',
        ]);

        $posRegistersCta = $resolver->forBlock([
            'type' => 'config_missing',
            'key' => 'pos_registers',
            'label' => 'POS',
            'message' => 'POS em falta.',
        ]);

        $inventoryStepCta = $resolver->forBlock([
            'type' => 'step_incomplete',
            'key' => 'inventory.create_warehouse',
            'label' => 'Criar armazém',
            'message' => 'Armazém pendente.',
        ]);

        $posStepCta = $resolver->forBlock([
            'type' => 'step_incomplete',
            'key' => 'pos.run_test_sale',
            'label' => 'Venda POS',
            'message' => 'Venda POS pendente.',
        ]);

        $this->assertSame(route('warehouses.index'), $warehouseCta['href']);
        $this->assertSame('Criar armazém', $warehouseCta['label']);
        $this->assertSame(route('product-service.stock.index'), $initialStockCta['href']);
        $this->assertSame('Carregar stock inicial', $initialStockCta['label']);
        $this->assertSame(route('product-service.stock.index'), $fifoLayersCta['href']);
        $this->assertSame('Validar FIFO', $fifoLayersCta['label']);
        $this->assertSame(route('pos.create'), $posRegistersCta['href']);
        $this->assertSame('Validar POS', $posRegistersCta['label']);
        $this->assertSame(route('warehouses.index'), $inventoryStepCta['href']);
        $this->assertSame(route('pos.create'), $posStepCta['href']);
    }
}
