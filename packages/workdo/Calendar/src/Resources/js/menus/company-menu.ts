import { Calendar } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const calendarCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('Calendário'),
        icon: Calendar,
        href: route('calendar.view.index'),
        permission: 'manage-calendar',
        parent: 'operations',
        order: 40,
    },
];
