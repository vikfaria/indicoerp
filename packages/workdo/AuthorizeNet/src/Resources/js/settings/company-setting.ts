import { CreditCard } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getAuthorizeNetCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
  //   {
  //     order: 1050,
  //     title: t('AuthorizeNet Settings'),
  //     href: '#authorizenet-settings',
  //     icon: CreditCard,
  //     permission: 'manage-authorizenet-settings',
  //     component: 'authorizenet-settings'
  //   }
];