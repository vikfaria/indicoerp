import { BookOpen } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const doubleentryCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('Double Entry'),
        icon: BookOpen,
        permission: 'manage-double-entry',
        order: 425,
        children: [
            {
                title: t('Ledger Summary'),
                href: route('double-entry.ledger-summary.index'),
                permission: 'manage-ledger-summary',
                order: 10
            },
            {
                title: t('Trial Balance'),
                href: route('double-entry.trial-balance.index'),
                permission: 'manage-trial-balance',
                order: 20
            },
            {
                title: t('Balance Sheets'),
                href: route('double-entry.balance-sheets.index'),
                permission: 'manage-balance-sheets',
                order: 30
            },
            {
                title: t('Profit & Loss'),
                href: route('double-entry.profit-loss.index'),
                permission: 'manage-profit-loss',
                order: 40
            },
            {
                title: t('Reports'),
                href: route('double-entry.reports.index'),
                permission: 'manage-double-entry-reports',
                order: 50
            }
        ],
    },
];
