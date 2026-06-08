import { BarChart3 } from 'lucide-react';
import { NavItem } from '@/types';

export const getConsultantMenu = (t: (key: string) => string): NavItem[] => [
    {
        title: t('Company Progress'),
        href: route('assistant-activation.company-progress.index'),
        icon: BarChart3,
        permission: 'view-company-onboarding-progress',
        order: 1,
    },
];
