import { CreditCard } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getPayTabSuperAdminSettings = (t: (key: string) => string): SettingMenuItem[] => [
  {
    order: 1090,
    title: t('PayTab Settings'),
    href: '#paytab-settings',
    icon: CreditCard,
    permission: 'manage-paytab-settings',
    component: 'paytab-settings'
  }
];