import { CreditCard, Settings } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getStripeSuperAdminSettings = (t: (key: string) => string): SettingMenuItem[] => [
  {
    order: 1010,
    title: t('Stripe Settings'),
    href: '#stripe-settings',
    icon: CreditCard,
    permission: 'manage-stripe-settings',
    component: 'stripe-settings'
  }
];