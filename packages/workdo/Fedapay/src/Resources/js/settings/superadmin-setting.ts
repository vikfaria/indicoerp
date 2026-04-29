import { CreditCard } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getFedapaySuperAdminSettings = (t: (key: string) => string): SettingMenuItem[] => [
  {
    order: 1300,
    title: t('FedaPay Settings'),
    href: '#fedapay-payment-settings',
    icon: CreditCard,
    permission: 'manage-fedapay-settings',
    component: 'fedapay-payment-settings'
  }
];
