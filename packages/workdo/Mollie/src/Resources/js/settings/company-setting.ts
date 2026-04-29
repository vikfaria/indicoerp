import { CreditCard } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getMollieCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
  // {
  //   order: 1060,
  //   title: t('Mollie Settings'),
  //   href: '#mollie-settings',
  //   icon: CreditCard,
  //   permission: 'manage-mollie-settings',
  //   component: 'mollie-settings'
  // }
];