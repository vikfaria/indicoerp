import {  Target , Tag } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const goalCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('Objectivos'),
        icon: Target,
        permission: 'manage-goal',
        parent: 'hrm',
        order: 1030,
        children: [
            {
                title: t('Lista de objectivos'),
                href: route('goal.goals.index'),
                permission: 'manage-goals',
            },
            {
                title: t('Marcos'),
                href: route('goal.milestones.index'),
                permission: 'manage-goal-milestones',
            },
            {
                title: t('Contribuições'),
                href: route('goal.contributions.index'),
                permission: 'manage-goal-contributions',
            },
            {
                title: t('Acompanhamento'),
                href: route('goal.tracking.index'),
                permission: 'manage-goal-tracking',
            },
            {
                title: t('Categorias'),
                href: route('goal.categories.index'),
                permission: 'manage-categories',
            },
        ],
    },
];
