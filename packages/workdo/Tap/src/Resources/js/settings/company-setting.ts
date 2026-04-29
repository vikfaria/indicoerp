import { CreditCard } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getTapCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
  // {
  //   order: 1060,
  //   title: t('Tap Settings'),
  //   href: '#tap-settings',
  //   icon: CreditCard,
  //   permission: 'manage-tap-settings',
  //   component: 'tap-settings'
  // }
];