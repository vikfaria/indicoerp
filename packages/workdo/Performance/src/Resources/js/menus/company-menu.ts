import { TrendingUp, Target, Users, MessageSquare, Award, BarChart3 } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const performanceCompanyMenu = (t: (key: string) => string) => [
   
    {
        title: t('Desempenho'),
        icon: TrendingUp,
        permission: 'manage-performance',
        parent: 'hrm',
        order: 1010,
        children: [
            {
                title: t('Indicadores de desempenho'),
                href: route('performance.indicators.index'),
                permission: 'manage-performance-indicators',
            },
            {
                title: t('Objectivos dos colaboradores'),
                href: route('performance.employee-goals.index'),
                permission: 'manage-employee-goals',
            },
            {
                title: t('Ciclos de avaliação'),
                href: route('performance.review-cycles.index'),
                permission: 'manage-review-cycles',
            },
            {
                title: t('Avaliações dos colaboradores'),
                href: route('performance.employee-reviews.index'),
                permission: 'manage-employee-reviews',
            },
            {
                title: t('Configuração'),
                href: route('performance.indicator-categories.index'),
                permission: 'manage-performance-system-setup',
                activePaths: [route('performance.goal-types.index')],
            },
        ],
    },
];
