import { CreditCard } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getCinetPayCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
  //   {
  //     order: 1060,
  //     title: t('CinetPay Settings'),
  //     href: '#cinetpay-settings',
  //     icon: CreditCard,
  //     permission: 'manage-cinetpay-settings',
  //     component: 'cinetpay-settings'
  //   }
];