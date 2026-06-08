import {  Package , DollarSign } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const budgetplannerCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('Planeamento Orçamental'),
        icon: DollarSign,
        permission: 'manage-budget-planner',
        parent: 'operations',
        order: 20,
        children: [
            {
                title: t('Períodos orçamentais'),
                href: route('budget-planner.budget-periods.index'),
                permission: 'manage-budget-periods',
            },
            {
                title: t('Orçamento'),
                href: route('budget-planner.budgets.index'),
                permission: 'manage-budgets',
            },
            {
                title: t('Alocações orçamentais'),
                href: route('budget-planner.budget-allocations.index'),
                permission: 'manage-budget-allocations',
            },
            {
                title: t('Monitorização orçamental'),
                href: route('budget-planner.budget-monitorings.index'),
                permission: 'manage-budget-monitoring',
            },
        ],
    },

];
