import { Send } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getTelegramCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
  {
    order: 530,
    title: t('Telegram Settings'),
    href: '#telegram-settings',
    icon: Send,
    permission: 'manage-telegram-settings',
    component: 'telegram-settings'
  }
];