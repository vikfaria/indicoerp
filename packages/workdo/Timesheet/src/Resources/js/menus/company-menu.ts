import { Package,Clock  } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const timesheetCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('Ficha de horário'),
        icon: Clock,
        permission: 'manage-timesheet',
        parent: 'hrm',
        order: 1040,
        href: route('timesheet.index'),
    },
];
