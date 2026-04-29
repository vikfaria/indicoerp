import { FileSignature } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getContractCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
  {
    order: 358,
    icon: FileSignature,
    title: t('Contract Settings'),
    href: '#contract-settings',
    permission: 'manage-contract-settings',
    component: 'contract-settings'
  }
];