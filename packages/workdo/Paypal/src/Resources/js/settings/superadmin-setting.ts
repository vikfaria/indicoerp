import { CreditCard } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getPaypalSuperAdminSettings = (t: (key: string) => string): SettingMenuItem[] => [
  {
    order: 1020,
    title: t('PayPal Settings'),
    href: '#paypal-settings',
    icon: CreditCard,
    permission: 'manage-paypal-settings',
    component: 'paypal-settings'
  }
];