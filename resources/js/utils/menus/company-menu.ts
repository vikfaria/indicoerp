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
        title: t('User Management'),
        icon: Users,
        permission: 'manage-users',
        order: 10,
        children: [
            {
                title: t('Roles'),
                href: route('roles.index'),
                permission: 'manage-roles',
            },
            {
                title: t('Users'),
                href: route('users.index'),
                permission: 'manage-users',
            },
        ],
    },
    {
        title: t('Proposal'),
        href: route('sales-proposals.index'),
        icon: Replace,
        permission: 'manage-sales-proposals',
        order: 20,
    },
    {
        title: t('Sales Invoice'),
        icon: Receipt,
        permission: 'manage-sales-invoices',
        order: 35,
        children: [
            {
                title: t('Sales Invoice'),
                href: route('sales-invoices.index'),
                permission: 'manage-sales-invoices',
            },
            {
                title: t('Sales Invoice Returns'),
                href: route('sales-returns.index'),
                permission: 'manage-sales-return-invoices',
            },
        ],
    },
    {
        title: t('Purchase'),
        icon: ShoppingCart,
        permission: 'manage-purchase-invoices',
        order: 40,
        children: [
            {
                title: t('Purchase Invoice'),
                href: route('purchase-invoices.index'),
                permission: 'manage-purchase-invoices',
            },
            {
                title: t('Purchase Returns'),
                href: route('purchase-returns.index'),
                permission: 'manage-purchase-return-invoices',
            },
            {
                title: t('Warehouses'),
                href: route('warehouses.index'),
                permission: 'manage-warehouses',
            },
            {
                title: t('Transfers'),
                href: route('transfers.index'),
                permission: 'manage-transfers',
            },
        ],
    },
    {
        title: t('Contabilidade SCE'),
        icon: Landmark,
        name: 'sce-accounting',
        order: 50,
        children: [
            {
                title: t('Diários'),
                href: route('sce.journals.index'),
                order: 1,
            },
            {
                title: t('Fecho Mensal'),
                href: route('sce.monthly-closing.index'),
                order: 2,
            },
        ],
    },
    {
        title: t('Fiscal'),
        icon: FileText,
        name: 'sce-fiscal',
        order: 55,
        children: [
            {
                title: t('Perfil Fiscal'),
                href: route('sce.fiscal.index'),
                order: 1,
            },
            {
                title: t('Calendário Fiscal'),
                href: route('sce.fiscal.calendar'),
                order: 2,
            },
            {
                title: t('Plano de Contas PGC'),
                href: route('sce.fiscal.pgc'),
                order: 3,
            },
            {
                title: t('Séries Documentais'),
                href: route('sce.fiscal.series'),
                order: 4,
            },
            {
                title: t('Exportação SAF-T'),
                href: route('sce.fiscal.saft-export'),
                order: 5,
            },
        ],
    },
    {
        title: t('Impostos'),
        icon: Calculator,
        name: 'sce-tax',
        order: 60,
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
        title: t('Activos & Relatórios'),
        icon: Building2,
        name: 'sce-assets',
        order: 65,
        children: [
            {
                title: t('Activos Fixos'),
                href: route('sce.fixed-assets.index'),
                order: 1,
            },
            {
                title: t('Balanço'),
                href: route('sce.reports.balance-sheet'),
                order: 2,
            },
            {
                title: t('Demonstração Resultados'),
                href: route('sce.reports.income-statement'),
                order: 3,
            },
            {
                title: t('Fluxos de Caixa'),
                href: route('sce.reports.cash-flow'),
                order: 4,
            },
        ],
    },
    {
        title: t('Media Library'),
        href: route('media-library'),
        icon: Image,
        permission: 'manage-media',
        order: 2900,
    },
    {
        title: t('Messenger'),
        href: route('messenger.index'),
        icon: MessageCircle,
        permission: 'manage-messenger',
        order: 2940,
    },
    {
        title: t('Helpdesk'),
        href: route('helpdesk-tickets.index'),
        icon: Headphones,
        permission: 'manage-helpdesk-tickets',
        order: 2950,
    },
    {
        title: t('Plan'),
        icon: CreditCard,
        permission: 'manage-plans',
        order: 2980,
        children: [
            {
                title: t('Setup Subscription Plan'),
                href: route('plans.index'),
                permission: 'manage-plans',
            },
            {
                title: t('Bank Transfer Requests'),
                href: route('bank-transfer.index'),
                permission: 'manage-bank-transfer-requests',
            },
            {
                title: t('Orders'),
                href: route('orders.index'),
                permission: 'manage-orders',
            }
        ]
    },
    {
        title: t('Settings'),
        href: route('settings.index'),
        icon: Settings,
        permission: 'manage-settings',
        order: 3000,
    },
];
