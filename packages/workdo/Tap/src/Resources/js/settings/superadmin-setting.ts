import { CreditCard } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getTapSuperAdminSettings = (t: (key: string) => string): SettingMenuItem[] => [
  {
    order: 1240,
    title: t('Tap Settings'),
    href: '#tap-payment-settings',
    icon: CreditCard,
    permission: 'manage-tap-settings',
    component: 'tap-payment-settings'
  }
];