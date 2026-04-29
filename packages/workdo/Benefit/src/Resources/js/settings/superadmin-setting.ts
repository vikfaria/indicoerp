import { CreditCard } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getBenefitSuperAdminSettings = (t: (key: string) => string): SettingMenuItem[] => [
  {
    order: 1160,
    title: t('Benefit Settings'),
    href: '#benefit-settings',
    icon: CreditCard,
    permission: 'manage-benefit-settings',
    component: 'benefit-settings'
  }
];