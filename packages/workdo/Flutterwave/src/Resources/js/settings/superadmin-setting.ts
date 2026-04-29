import { CreditCard, Settings } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getFlutterwaveSuperAdminSettings = (t: (key: string) => string): SettingMenuItem[] => [
  {
    order: 1030,
    title: t('Flutterwave Settings'),
    href: '#flutterwave-settings',
    icon: CreditCard,
    permission: 'manage-flutterwave-settings',
    component: 'flutterwave-settings'
  }
];