import { Slack } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getSlackCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
  {
    order: 520,
    title: t('Slack Settings'),
    href: '#slack-settings',
    icon: Slack,
    permission: 'manage-slack-settings',
    component: 'slack-settings'
  }
];
