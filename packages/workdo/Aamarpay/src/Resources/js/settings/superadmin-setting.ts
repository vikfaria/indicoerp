import { CreditCard } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getAamarpaySuperAdminSettings = (t: (key: string) => string): SettingMenuItem[] => [
  {
    order: 1150,
    title: t('Aamarpay Settings'),
    href: '#aamarpay-settings',
    icon: CreditCard,
    permission: 'manage-aamarpay-settings',
    component: 'aamarpay-settings'
  }
];