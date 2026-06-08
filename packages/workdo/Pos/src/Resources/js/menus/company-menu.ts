import { Store } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const posCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('POS Dashboard'),
        href: route('pos.index'),
        permission: 'manage-pos-dashboard',
        parent: 'dashboard',
        order: 40,
    },
    {
        title: t('POS'),
        icon: Store,
        permission: 'manage-pos',
        order: 475,
        children: [
            {
                title: t('Nova venda'),
                href: route('pos.create'),
                permission: 'create-pos',
            },
            {
                title: t('Vendas POS'),
                href: route('pos.orders'),
                permission: 'manage-pos-orders',
            },
            {
                title: t('Imprimir código de barras'),
                href: route('pos.barcode'),
                permission: 'manage-pos-barcodes',
            },
            {
                title: t('Relatórios'),
                permission: 'manage-pos-reports',
                children: [
                    {
                        title: t('Relatório de vendas'),
                        href: route('pos.reports.sales'),
                        permission: 'view-pos-reports',
                    },
                    {
                        title: t('Relatório de produtos'),
                        href: route('pos.reports.products'),
                        permission: 'view-pos-reports',
                    },
                    {
                        title: t('Relatório de clientes'),
                        href: route('pos.reports.customers'),
                        permission: 'view-pos-reports',
                    }
                ],
            },
        ],
    },
];
