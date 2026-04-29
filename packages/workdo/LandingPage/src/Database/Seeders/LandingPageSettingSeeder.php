<?php

namespace Workdo\LandingPage\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Workdo\LandingPage\Models\LandingPageSetting;

class LandingPageSettingSeeder extends Seeder
{
    public function run()
    {
        if (LandingPageSetting::exists()) {
            return;
        }

        try {
            LandingPageSetting::create($this->getDefaultSettings());
        } catch (\Exception $e) {
            Log::error('Failed to seed landing page settings: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getDefaultSettings(): array
    {
        return [
            'company_name' => 'Índico ERP',
            'contact_email' => 'comercial@indicoerp.com',
            'contact_phone' => '+258 84 000 0000',
            'contact_address' => 'Maputo, Moçambique',
            'config_sections' => $this->getDefaultConfigSections(),
        ];
    }

    private function getDefaultConfigSections(): array
    {
        return [
            'sections' => $this->getDefaultSections(),
            'page' => $this->getDefaultPageMetadata(),
            'social' => $this->getDefaultSocialMetadata(),
            'section_visibility' => $this->getDefaultVisibility(),
            'section_order' => $this->getDefaultOrder(),
            'colors' => $this->getDefaultColors(),
        ];
    }

    private function getDefaultSections(): array
    {
        return [
            'hero' => [
                'variant' => 'hero2',
                'title' => 'O Sistema de Gestão Definitivo para Empresas em Moçambique',
                'subtitle' => 'Fature, controle o stock e gira os seus recursos humanos numa única plataforma. 100% preparado para o SAF-T da Autoridade Tributária. Poupe tempo e evite multas.',
                'primary_button_text' => 'Começar Teste Grátis (30 Dias)',
                'primary_button_link' => route('register'),
                'secondary_button_text' => 'Agendar Demonstração',
                'secondary_button_link' => 'mailto:comercial@indicoerp.com?subject=Agendar%20Demonstra%C3%A7%C3%A3o%20-%20%C3%8Dndico%20ERP',
                'highlight_text' => 'Índico ERP',
                'image' => 'hero_indico_erp_mockup.png',
            ],
            'header' => [
                'variant' => 'header3',
                'company_name' => 'Índico ERP',
                'cta_text' => 'Começar Agora',
                'enable_pricing_link' => true,
                'navigation_items' => [
                    ['text' => 'Início', 'href' => '#hero'],
                    ['text' => 'Funcionalidades', 'href' => '#features'],
                    ['text' => 'Módulos', 'href' => '#modules'],
                    ['text' => 'Benefícios', 'href' => '#benefits'],
                    ['text' => 'Contacto', 'href' => '#contact'],
                ],
            ],
            'stats' => [
                'variant' => 'stats5',
                'stats' => [
                    ['label' => 'Conformidade SAF-T (AT)', 'value' => '100%'],
                    ['label' => 'Suporte Local em Maputo', 'value' => '24/7'],
                    ['label' => 'Módulos Integrados', 'value' => '6'],
                    ['label' => 'Implementação Base', 'value' => '14 Dias'],
                ],
            ],
            'features' => [
                'variant' => 'features5',
                'title' => 'Tudo o que a sua empresa precisa, num só lugar.',
                'subtitle' => 'Diga adeus às folhas de Excel desorganizadas e aos sistemas que não comunicam entre si.',
                'features' => $this->getDefaultFeatures(),
            ],
            'modules' => [
                'variant' => 'modules1',
                'title' => 'Módulos prontos para a realidade operacional moçambicana',
                'subtitle' => 'Cada área crítica da sua empresa ligada numa única plataforma, com dados centralizados e processos mais rápidos.',
                'modules' => [
                    [
                        'key' => 'accounting',
                        'label' => 'Faturação e Contabilidade',
                        'title' => 'Faturação e Contabilidade com SAF-T',
                        'description' => 'Emita faturas, controle recebimentos e mantenha a sua operação preparada para exportação SAF-T e auditorias da Autoridade Tributária.',
                        'image' => 'module_accounting_saft.png',
                    ],
                    [
                        'key' => 'hrm',
                        'label' => 'Recursos Humanos',
                        'title' => 'Gestão de Recursos Humanos',
                        'description' => 'Organize colaboradores, salários, férias, assiduidade e processos internos de RH num ambiente centralizado e fácil de acompanhar.',
                        'image' => 'module_hrm.png',
                    ],
                    [
                        'key' => 'pos',
                        'label' => 'Ponto de Venda',
                        'title' => 'POS rápido para lojas e retalho',
                        'description' => 'Venda com rapidez, acompanhe o stock em tempo real e reduza erros no balcão com um ponto de venda desenhado para operação diária.',
                        'image' => 'module_pos.png',
                    ],
                    [
                        'key' => 'crm',
                        'label' => 'CRM e Vendas',
                        'title' => 'CRM e Pipeline Comercial',
                        'description' => 'Acompanhe oportunidades, equipas comerciais, propostas e previsões de receita com visibilidade clara sobre o funil de vendas.',
                        'image' => 'module_crm_sales.png',
                    ],
                    [
                        'key' => 'projects',
                        'label' => 'Projetos',
                        'title' => 'Gestão de Projetos e Tarefas',
                        'description' => 'Planeie entregas, acompanhe cronogramas, equipas e orçamento dos seus projetos com uma visão consolidada da execução.',
                        'image' => 'module_projects.png',
                    ],
                    [
                        'key' => 'reports',
                        'label' => 'Relatórios',
                        'title' => 'Relatórios e Dashboards',
                        'description' => 'Tome decisões com base em indicadores reais de faturação, despesas, produtividade e desempenho operacional.',
                        'image' => 'module_reports.png',
                    ],
                ],
            ],
            'benefits' => [
                'variant' => 'benefits2',
                'title' => 'Desenhado para a Realidade Empresarial Moçambicana',
                'benefits' => [
                    [
                        'title' => 'Conformidade Fiscal Automática',
                        'description' => 'Atualizações orientadas para a realidade tributária local, com foco em faturação organizada e preparação SAF-T.',
                        'image' => 'differentiator_compliance.png',
                    ],
                    [
                        'title' => 'Preço Justo em Meticais',
                        'description' => 'Uma solução pensada para empresas moçambicanas, com comunicação simples sobre investimento e sem estruturas confusas em moeda estrangeira.',
                        'image' => 'differentiator_fair_price.png',
                    ],
                    [
                        'title' => 'Suporte Local em Maputo',
                        'description' => 'Atendimento próximo, contextualizado e disponível para implementação, formação e acompanhamento contínuo da sua operação.',
                        'image' => 'differentiator_local_support.png',
                    ],
                ],
            ],
            'gallery' => [
                'variant' => 'gallery5',
                'title' => 'Veja o Índico ERP em ação',
                'subtitle' => 'Algumas das experiências e módulos que a sua equipa pode usar no dia a dia.',
                'images' => [
                    'hero_indico_erp_mockup.png',
                    'module_accounting_saft.png',
                    'module_crm_sales.png',
                    'module_hrm.png',
                    'module_pos.png',
                    'module_projects.png',
                    'module_reports.png',
                ],
            ],
            'cta' => [
                'variant' => 'cta4',
                'title' => 'Pronto para modernizar a gestão da sua empresa?',
                'subtitle' => 'Junte-se às empresas moçambicanas que já automatizaram as suas operações com o Índico ERP. Comece hoje, sem compromisso.',
                'primary_button' => 'Criar Conta Gratuita Agora',
                'primary_button_link' => route('register'),
                'secondary_button' => 'Ver Planos',
                'secondary_button_link' => route('pricing.page'),
                'image' => 'final_cta_background.png',
            ],
            'pricing' => [
                'title' => 'Planos e Subscrições',
                'subtitle' => 'Escolha o plano mais adequado ao estágio atual da sua empresa',
                'default_subscription_type' => 'pre-package',
                'default_price_type' => 'monthly',
                'show_pre_package' => true,
                'show_monthly_yearly_toggle' => true,
                'empty_message' => 'Nenhum plano disponível neste momento.',
            ],
            'footer' => [
                'variant' => 'footer4',
                'description' => 'Plataforma integrada de faturação, contabilidade, RH, CRM, POS, projetos e relatórios para empresas em Moçambique.',
                'email' => 'comercial@indicoerp.com',
                'phone' => '+258 84 000 0000',
                'newsletter_title' => 'Receba novidades do Índico ERP',
                'newsletter_description' => 'Subscreva para acompanhar atualizações do produto, novidades fiscais e conteúdo útil para gestão empresarial.',
                'newsletter_button_text' => 'Subscrever',
                'copyright_text' => '',
                'navigation_sections' => [
                    [
                        'title' => 'Produto',
                        'links' => [
                            ['text' => 'Funcionalidades', 'href' => '#features'],
                            ['text' => 'Módulos', 'href' => '#modules'],
                            ['text' => 'Planos', 'href' => route('pricing.page')],
                        ],
                    ],
                    [
                        'title' => 'Empresa',
                        'links' => [
                            ['text' => 'Benefícios', 'href' => '#benefits'],
                            ['text' => 'Galeria', 'href' => '#gallery'],
                            ['text' => 'Contacto', 'href' => '#contact'],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getDefaultFeatures(): array
    {
        return [
            ['title' => 'Faturação em Meticais', 'description' => 'Emissão de documentos e controlo financeiro adaptados ao contexto operacional moçambicano.', 'icon' => 'Calculator'],
            ['title' => 'CRM Comercial', 'description' => 'Pipeline de vendas, gestão de propostas e acompanhamento de oportunidades em tempo real.', 'icon' => 'Users'],
            ['title' => 'POS e Stock', 'description' => 'Ponto de venda com atualização de inventário para retalho, armazém e operação no balcão.', 'icon' => 'CreditCard'],
            ['title' => 'Recursos Humanos', 'description' => 'Gestão de colaboradores, férias, assiduidade e apoio aos processos internos de RH.', 'icon' => 'UserCheck'],
            ['title' => 'Projetos e Equipas', 'description' => 'Planeamento, tarefas, orçamento e acompanhamento de entregáveis numa mesma vista.', 'icon' => 'FolderOpen'],
            ['title' => 'Visão Integrada', 'description' => 'Uma plataforma única para ligar finanças, operações, vendas e gestão estratégica.', 'icon' => 'Building2'],
        ];
    }

    private function getDefaultPageMetadata(): array
    {
        return [
            'title' => 'Índico ERP - Sistema de Gestão Completo para Empresas em Moçambique',
            'description' => 'O Índico ERP é a solução completa de faturação, contabilidade, RH, CRM, POS e projetos para empresas moçambicanas. 100% preparado para o SAF-T. Teste grátis por 30 dias.',
            'keywords' => 'índico erp, erp moçambique, saft moçambique, faturação, crm, rh, pos, projetos',
            'canonical_url' => 'https://indicoerp.com/',
        ];
    }

    private function getDefaultSocialMetadata(): array
    {
        return [
            'og_title' => 'Índico ERP - Sistema de Gestão Completo para Empresas em Moçambique',
            'og_description' => 'Faturação, contabilidade, RH, CRM, POS e projetos numa única solução preparada para a realidade moçambicana.',
            'og_image' => 'og_image_indico_erp.png',
            'site_name' => 'Índico ERP',
        ];
    }

    private function getDefaultVisibility(): array
    {
        return [
            'header' => true,
            'hero' => true,
            'stats' => true,
            'features' => true,
            'modules' => true,
            'benefits' => true,
            'gallery' => true,
            'cta' => true,
            'footer' => true,
            'pricing' => true,
        ];
    }

    private function getDefaultOrder(): array
    {
        return ['header', 'hero', 'stats', 'features', 'modules', 'benefits', 'gallery', 'cta', 'footer'];
    }

    private function getDefaultColors(): array
    {
        return [
            'primary' => '#00B4D8',
            'secondary' => '#0B5D7A',
            'accent' => '#D4A017',
        ];
    }
}
