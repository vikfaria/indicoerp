import { CreditCard } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getMidtransSuperAdminSettings = (t: (key: string) => string): SettingMenuItem[] => [
  {
    order: 1220,
    title: t('Midtrans Settings'),
    href: '#midtrans-settings',
    icon: CreditCard,
    permission: 'manage-midtrans-settings',
    component: 'midtrans-settings'
  }
];