import { CreditCard } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getToyyibpaySuperadminSettings = (t: (key: string) => string): SettingMenuItem[] => [
  {
    order: 1110,
    title: t('Toyyibpay Settings'),
    href: '#toyyibpay-settings',
    icon: CreditCard,
    permission: 'manage-toyyibpay-settings',
    component: 'toyyibpay-settings'
  }
];