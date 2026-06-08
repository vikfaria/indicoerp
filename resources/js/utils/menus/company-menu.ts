import { LayoutGrid, Users, Warehouse,ArrowRightLeft, Package, Tag, Tags, Shield, Settings, Image, CreditCard, Headphones, ShoppingCart, Kanban, Calendar, MessageCircle, Replace ,Receipt, Landmark, Calculator, FileText, Building2, BarChart3} from 'lucide-react';
import { NavItem } from '@/types';

export const getCompanyMenu = (t: (key: string) => string): NavItem[] => [
    {
        title: t('Dashboard'),
        icon: LayoutGrid,
        permission: 'manage-dashboard',
        name: 'dashboard',
        order: 1,
    },
    {
        title: t('Meu Plano'),
        href: route('plans.my-plan'),
        icon: CreditCard,
        permission: 'manage-dashboard',
        order: 15,
    },
    {
        title: t('Propostas'),
        href: route('sales-proposals.index'),
        icon: Replace,
        permission: 'manage-sales-proposals',
        order: 20,
    },
    {
        title: t('Facturação'),
        icon: Receipt,
        permission: 'manage-sales-invoices',
        order: 35,
        children: [
            {
                title: t('Facturas de venda'),
                href: route('sales-invoices.index'),
                permission: 'manage-sales-invoices',
            },
            {
                title: t('Devoluções de venda'),
                href: route('sales-returns.index'),
                permission: 'manage-sales-return-invoices',
            },
        ],
    },
    {
        title: t('Compras'),
        icon: ShoppingCart,
        permission: 'manage-purchase-invoices',
        order: 40,
        children: [
            {
                title: t('Facturas de compra'),
                href: route('purchase-invoices.index'),
                permission: 'manage-purchase-invoices',
            },
            {
                title: t('Devoluções de compra'),
                href: route('purchase-returns.index'),
                permission: 'manage-purchase-return-invoices',
            },
        ],
    },
    {
        title: t('Inventário'),
        icon: Warehouse,
        name: 'inventory',
        permissionsAny: ['manage-product-service-item', 'manage-warehouses', 'manage-transfers'],
        order: 41,
        children: [
            {
                title: t('Warehouses'),
                href: route('warehouses.index'),
                permission: 'manage-warehouses',
                order: 20,
            },
            {
                title: t('Transfers'),
                href: route('transfers.index'),
                permission: 'manage-transfers',
                order: 30,
            },
        ],
    },
    {
        title: t('Contabilidade'),
        icon: Landmark,
        name: 'contabilidade',
        permissionsAny: ['manage-account', 'manage-account-reports', 'view-tax-summary'],
        order: 50,
        children: [
            {
                title: t('Contabilidade SCE'),
                icon: Landmark,
                permissionsAny: ['manage-account', 'manage-account-reports', 'view-tax-summary'],
                order: 10,
                children: [
                    {
                        title: t('Diários'),
                        href: route('sce.journals.index'),
                        permissionsAny: ['manage-account', 'manage-account-reports', 'view-tax-summary'],
                        order: 1,
                    },
                    {
                        title: t('Fecho Mensal'),
                        href: route('sce.monthly-closing.index'),
                        permissionsAny: ['manage-account', 'manage-account-reports', 'view-tax-summary'],
                        order: 2,
                    },
                ],
            },
            {
                title: t('Fiscal'),
                icon: FileText,
                permissionsAny: ['manage-account', 'manage-account-reports', 'view-tax-summary'],
                order: 20,
                children: [
                    {
                        title: t('Perfil Fiscal'),
                        href: route('sce.fiscal.index'),
                        permissionsAny: ['manage-account', 'manage-account-reports', 'view-tax-summary'],
                        order: 1,
                    },
                    {
                        title: t('Calendário Fiscal'),
                        href: route('sce.fiscal.calendar'),
                        permissionsAny: ['manage-account', 'manage-account-reports', 'view-tax-summary'],
                        order: 2,
                    },
                    {
                        title: t('Plano de Contas PGC'),
                        href: route('sce.fiscal.pgc'),
                        permissionsAny: ['manage-account', 'manage-account-reports', 'view-tax-summary'],
                        order: 3,
                    },
                    {
                        title: t('Séries Documentais'),
                        href: route('sce.fiscal.series'),
                        permissionsAny: ['manage-account', 'manage-account-reports', 'view-tax-summary'],
                        order: 4,
                    },
                    {
                        title: t('Exportação SAF-T'),
                        href: route('sce.fiscal.saft-export'),
                        permissionsAny: ['manage-account', 'manage-account-reports', 'view-tax-summary'],
                        order: 5,
                    },
                ],
            },
            {
                title: t('Impostos'),
                icon: Calculator,
                permissionsAny: ['view-tax-summary', 'manage-account-reports'],
                order: 30,
                children: [
                    {
                        title: t('Mapa IVA'),
                        href: route('sce.tax.vat-map'),
                        permissionsAny: ['view-tax-summary', 'manage-account-reports'],
                        order: 1,
                    },
                    {
                        title: t('IRPC'),
                        href: route('sce.tax.irpc'),
                        permissionsAny: ['view-tax-summary', 'manage-account-reports'],
                        order: 2,
                    },
                    {
                        title: t('Retenções na Fonte'),
                        href: route('sce.tax.withholding'),
                        permissionsAny: ['view-tax-summary', 'manage-account-reports'],
                        order: 3,
                    },
                    {
                        title: t('Declaração de Retenções'),
                        href: route('sce.tax.withholding.declaration.page'),
                        permissionsAny: ['view-tax-summary', 'manage-account-reports'],
                        order: 4,
                    },
                    {
                        title: t('Modelo 20'),
                        href: route('sce.tax.modelo20.page'),
                        permissionsAny: ['view-tax-summary', 'manage-account-reports'],
                        order: 5,
                    },
                    {
                        title: t('Declaração Anual'),
                        href: route('sce.tax.annual-declaration.page'),
                        permissionsAny: ['view-tax-summary', 'manage-account-reports'],
                        order: 6,
                    },
                ],
            },
            {
                title: t('Activos'),
                icon: Building2,
                permissionsAny: ['manage-account', 'manage-account-reports', 'view-tax-summary'],
                order: 40,
                children: [
                    {
                        title: t('Activos Fixos'),
                        href: route('sce.fixed-assets.index'),
                        permissionsAny: ['manage-account', 'manage-account-reports', 'view-tax-summary'],
                        order: 1,
                    },
                ],
            },
            {
                title: t('Relatórios'),
                icon: BarChart3,
                permissionsAny: ['manage-account', 'manage-account-reports', 'view-tax-summary'],
                order: 50,
                children: [
                    {
                        title: t('Balanço'),
                        href: route('sce.reports.balance-sheet'),
                        permissionsAny: ['manage-account', 'manage-account-reports', 'view-tax-summary'],
                        order: 1,
                    },
                    {
                        title: t('Demonstração Resultados'),
                        href: route('sce.reports.income-statement'),
                        permissionsAny: ['manage-account', 'manage-account-reports', 'view-tax-summary'],
                        order: 2,
                    },
                    {
                        title: t('Alterações no Capital Próprio'),
                        href: route('sce.reports.equity-changes'),
                        permissionsAny: ['manage-account', 'manage-account-reports', 'view-tax-summary'],
                        order: 3,
                    },
                    {
                        title: t('Fluxos de Caixa'),
                        href: route('sce.reports.cash-flow'),
                        permissionsAny: ['manage-account', 'manage-account-reports', 'view-tax-summary'],
                        order: 4,
                    },
                ],
            },
        ],
    },
    {
        title: t('Operações'),
        icon: Kanban,
        name: 'operations',
        permissionsAny: [
            'manage-project',
            'manage-budget-planner',
            'manage-contracts',
            'manage-calendar',
            'manage-zoom-meetings',
            'manage-formbuilder',
        ],
        order: 2400,
    },
    {
        title: t('CRM / Suporte'),
        icon: MessageCircle,
        name: 'crm-support',
        permissionsAny: [
            'manage-leads',
            'manage-support-tickets',
            'manage-messenger',
            'manage-helpdesk-tickets',
        ],
        order: 2500,
        children: [
            {
                title: t('Helpdesk'),
                href: route('helpdesk-tickets.index'),
                icon: Headphones,
                permission: 'manage-helpdesk-tickets',
                order: 30,
            },
            {
                title: t('Messenger'),
                href: route('messenger.index'),
                icon: MessageCircle,
                permission: 'manage-messenger',
                order: 40,
            },
        ],
    },
    {
        title: t('Sistema'),
        icon: Shield,
        name: 'admin',
        permissionsAny: ['manage-users', 'manage-roles', 'manage-plans', 'manage-settings', 'manage-media'],
        order: 2600,
        children: [
            {
                title: t('Gestão de Utilizadores'),
                icon: Users,
                permissionsAny: ['manage-users', 'manage-roles'],
                order: 10,
                children: [
                    {
                        title: t('Perfis'),
                        href: route('roles.index'),
                        permission: 'manage-roles',
                    },
                    {
                        title: t('Utilizadores'),
                        href: route('users.index'),
                        permission: 'manage-users',
                    },
                ],
            },
            {
                title: t('Planos'),
                icon: CreditCard,
                permission: 'manage-plans',
                order: 20,
                children: [
                    {
                        title: t('Configurar plano'),
                        href: route('plans.index'),
                        permission: 'manage-plans',
                    },
                    {
                        title: t('Pedidos de transferência bancária'),
                        href: route('bank-transfer.index'),
                        permission: 'manage-bank-transfer-requests',
                    },
                    {
                        title: t('Encomendas'),
                        href: route('orders.index'),
                        permission: 'manage-orders',
                    }
                ]
            },
            {
                title: t('Configurações'),
                href: route('settings.index'),
                icon: Settings,
                permission: 'manage-settings',
                order: 30,
            },
            {
                title: t('Biblioteca de mídia'),
                href: route('media-library'),
                icon: Image,
                permission: 'manage-media',
                order: 40,
            },
        ],
    },
];
