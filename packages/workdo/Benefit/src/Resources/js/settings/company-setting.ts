import { CreditCard } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getBenefitCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
  // {
  //   order: 1060,
  //   title: t('Benefit Settings'),
  //   href: '#benefit-settings',
  //   icon: CreditCard,
  //   permission: 'manage-benefit-settings',
  //   component: 'benefit-settings'
  // }
];