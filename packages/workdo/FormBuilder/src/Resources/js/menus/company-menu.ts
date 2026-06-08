import { FormInput } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const formbuilderCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('Formulários'),
        icon: FormInput,
        permission: 'manage-formbuilder',
        parent: 'operations',
        order: 60,
        href: route('formbuilder.forms.index'),
    },
];
