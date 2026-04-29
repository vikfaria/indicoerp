import { CreditCard } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getPayfastCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
  // {
  //   order: 1060,
  //   title: t('Payfast Settings'),
  //   href: '#payfast-settings',
  //   icon: CreditCard,
  //   permission: 'manage-payfast-settings',
  //   component: 'payfast-settings'
  // }
];