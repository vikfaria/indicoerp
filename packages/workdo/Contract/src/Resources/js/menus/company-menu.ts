import { FileSignature, Tag } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const contractCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('Contract'),
        icon: FileSignature,
        permission: 'manage-contracts',
        order: 725,
        name: 'contract',
        children: [
            {
                title: t('Contracts'),
                href: route('contract.index'),
                permission: 'manage-contracts',
                order: 10,
            },
            {
                title: t('Contract Types'),
                href: route('contract-types.index'),
                permission: 'manage-contract-types',
                order: 30,
            },
        ],
    },
];