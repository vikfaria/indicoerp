import { Phone } from 'lucide-react';

export interface SettingMenuItem {
    order: number;
    title: string;
    href: string;
    icon: any;
    permission: string;
    component: string;
}

export const getTwilioCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
    {
        order: 540,
        title: t('Twilio Settings'),
        href: '#twilio-settings',
        icon: Phone,
        permission: 'manage-twilio-settings',
        component: 'twilio-settings'
    }
];
