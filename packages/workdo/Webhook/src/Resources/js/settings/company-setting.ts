import { Webhook } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getWebhookCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
  {
    order: 700,
    title: t('Webhook Settings'),
    href: '#webhook-settings',
    icon: Webhook,
    permission: 'manage-webhooks',
    component: 'webhook-settings'
  }
];