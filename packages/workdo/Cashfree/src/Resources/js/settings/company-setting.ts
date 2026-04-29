import { CreditCard } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getCashfreeCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
  //   {
  //     order: 1170,
  //     title: t('Cashfree Settings'),
  //     href: '#cashfree-settings',
  //     icon: CreditCard,
  //     permission: 'manage-cashfree-settings',
  //     component: 'cashfree-settings'
  //   }
];