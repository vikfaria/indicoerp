import { Layers } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const productserviceCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('Produtos e Serviços'),
        icon: Layers,
        permission: 'manage-product-service-item',
        parent: 'inventory',
        order: 10,
        children: [
            {
                title: t('Artigos'),
                href: route('product-service.items.index'),
                permission: 'manage-product-service-item',
                activePaths: [route('product-service.stock.index')],
            },
            {
                title: t('Configuração'),
                href: route('product-service.item-categories.index'),
                permission: 'manage-product-service-item',
                activePaths: [route('product-service.taxes.index'), route('product-service.units.index')],
            },
        ],
    },
];
