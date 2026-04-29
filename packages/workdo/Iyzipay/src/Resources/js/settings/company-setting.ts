import { CreditCard } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getIyzipayCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
  // {
  //   order: 1060,
  //   title: t('Iyzipay Settings'),
  //   href: '#iyzipay-settings',
  //   icon: CreditCard,
  //   permission: 'manage-iyzipay-settings',
  //   component: 'iyzipay-settings'
  // }
];