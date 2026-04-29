import { Package,Clock  } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const timesheetCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('Timesheet'),
        icon: Clock,
        permission: 'manage-timesheet',
        order: 1450,
        href: route('timesheet.index'),
    },
];