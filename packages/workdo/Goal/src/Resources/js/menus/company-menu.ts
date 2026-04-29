import {  Target , Tag } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const goalCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('Goal'),
        icon: Target,
        permission: 'manage-goal',
        order: 415,
        children: [
            {
                title: t('Goals'),
                href: route('goal.goals.index'),
                permission: 'manage-goals',
            },
            {
                title: t('Milestones'),
                href: route('goal.milestones.index'),
                permission: 'manage-goal-milestones',
            },
            {
                title: t('Contributions'),
                href: route('goal.contributions.index'),
                permission: 'manage-goal-contributions',
            },
            {
                title: t('Tracking'),
                href: route('goal.tracking.index'),
                permission: 'manage-goal-tracking',
            },
            {
                title: t('Category'),
                href: route('goal.categories.index'),
                permission: 'manage-categories',
            },
        ],
    },
];
