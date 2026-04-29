import { Headphones } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getSupportTicketCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
  {
    order: 320,
    title: t('Support Setting'),
    href: '#support-setting',
    icon: Headphones,
    permission: 'manage-support-settings',
    component: 'support-setting'
  }
];