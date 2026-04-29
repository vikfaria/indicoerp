import { CreditCard } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getPaystackCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
  //   {
  //     order: 1040,
  //     title: t('Paystack Settings'),
  //     href: '#paystack-settings',
  //     icon: CreditCard,
  //     permission: 'manage-paystack-settings',
  //     component: 'paystack-settings'
  //   }
];