import { CreditCard } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getCoingateCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
  // {
  //   order: 1060,
  //   title: t('Coingate Settings'),
  //   href: '#coingate-settings',
  //   icon: CreditCard,
  //   permission: 'manage-coingate-settings',
  //   component: 'coingate-settings'
  // }
];