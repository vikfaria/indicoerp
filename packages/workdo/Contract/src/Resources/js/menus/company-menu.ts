import { FileSignature, Tag } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const contractCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('Contratos'),
        icon: FileSignature,
        permission: 'manage-contracts',
        parent: 'operations',
        order: 30,
        name: 'contract',
        children: [
            {
                title: t('Lista de contratos'),
                href: route('contract.index'),
                permission: 'manage-contracts',
                order: 10,
            },
            {
                title: t('Tipos de contrato'),
                href: route('contract-types.index'),
                permission: 'manage-contract-types',
                order: 30,
            },
        ],
    },
];
