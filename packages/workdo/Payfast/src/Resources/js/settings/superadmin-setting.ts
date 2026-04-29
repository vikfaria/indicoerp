import { CreditCard } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getPayfastSuperAdminSettings = (t: (key: string) => string): SettingMenuItem[] => [
  {
    order: 1070,
    title: t('Payfast Settings'),
    href: '#payfast-payment-settings',
    icon: CreditCard,
    permission: 'manage-payfast-settings',
    component: 'payfast-payment-settings'
  }
];