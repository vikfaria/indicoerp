import { CreditCard } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getPayTRCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
  // {
  //   order: 1060,
  //   title: t('PayTR Settings'),
  //   href: '#paytr-settings',
  //   icon: CreditCard,
  //   permission: 'manage-paytr-settings',
  //   component: 'paytr-settings'
  // }
];