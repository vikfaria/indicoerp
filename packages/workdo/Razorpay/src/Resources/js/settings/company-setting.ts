import { CreditCard, Settings } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getRazorpayCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
  // {
  //   order: 1050,
  //   title: t('Razorpay Settings'),
  //   href: '#razorpay-settings',
  //   icon: CreditCard,
  //   permission: 'manage-razorpay-settings',
  //   component: 'razorpay-settings'
  // }
];
