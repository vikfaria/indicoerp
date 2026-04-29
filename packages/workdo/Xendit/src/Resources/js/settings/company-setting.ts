import { CreditCard, Settings } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getXenditCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
  // {
  //   order: 1060,
  //   title: t('Xendit Settings'),
  //   href: '#xendit-settings',
  //   icon: CreditCard,
  //   permission: 'manage-xendit-settings',
  //   component: 'xendit-settings'
  // }
];