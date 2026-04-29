import { CreditCard } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getFedapayCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
  //   {
  //     order: 1070,
  //     title: t('Fedapay Settings'),
  //     href: '#fedapay-settings',
  //     icon: CreditCard,
  //     permission: 'manage-fedapay-settings',
  //     component: 'fedapay-settings'
  //   }
];
