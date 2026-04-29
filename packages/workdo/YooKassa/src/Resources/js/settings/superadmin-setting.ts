import { CreditCard } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getYooKassaSuperAdminSettings = (t: (key: string) => string): SettingMenuItem[] => [
  {
    order: 1080,
    title: t('YooKassa Settings'),
    href: '#yookassa-settings',
    icon: CreditCard,
    permission: 'manage-yookassa-settings',
    component: 'yookassa-settings'
  }
];