import { TrendingUp, Target, Users, MessageSquare, Award, BarChart3 } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const performanceCompanyMenu = (t: (key: string) => string) => [
   
    {
        title: t('Performance'),
        icon: TrendingUp,
        permission: 'manage-performance',
        parent: '',
        order: 451,
        children: [
            {
                title: t('Performance Indicators'),
                href: route('performance.indicators.index'),
                permission: 'manage-performance-indicators',
            },
            {
                title: t('Employee Goals'),
                href: route('performance.employee-goals.index'),
                permission: 'manage-employee-goals',
            },
            {
                title: t('Review Cycles'),
                href: route('performance.review-cycles.index'),
                permission: 'manage-review-cycles',
            },
            {
                title: t('Employee Reviews'),
                href: route('performance.employee-reviews.index'),
                permission: 'manage-employee-reviews',
            },
            {
                title: t('System Setup'),
                href: route('performance.indicator-categories.index'),
                permission: 'manage-performance-system-setup',
                activePaths: [route('performance.goal-types.index')],
            },
        ],
    },
];