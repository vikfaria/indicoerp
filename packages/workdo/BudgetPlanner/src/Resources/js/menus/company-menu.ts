import {  Package , DollarSign } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const budgetplannerCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('Budget Planner'),
        icon: DollarSign,
        permission: 'manage-budget-planner',
        order: 420,
        children: [
            {
                title: t('Budget Periods'),
                href: route('budget-planner.budget-periods.index'),
                permission: 'manage-budget-periods',
            },
            {
                title: t('Budget'),
                href: route('budget-planner.budgets.index'),
                permission: 'manage-budgets',
            },
            {
                title: t('Budget Allocations'),
                href: route('budget-planner.budget-allocations.index'),
                permission: 'manage-budget-allocations',
            },
            {
                title: t('Budget Monitoring'),
                href: route('budget-planner.budget-monitorings.index'),
                permission: 'manage-budget-monitoring',
            },
        ],
    },

];
