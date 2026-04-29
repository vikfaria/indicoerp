import { FormInput } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const formbuilderCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('Form Builder'),
        icon: FormInput,
        permission: 'manage-formbuilder',
        order: 510,
        href: route('formbuilder.forms.index'),
    },
];