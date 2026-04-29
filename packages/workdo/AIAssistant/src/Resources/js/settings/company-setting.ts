import { Bot } from 'lucide-react';

export interface SettingMenuItem {
    order: number;
    title: string;
    href: string;
    icon: any;
    permission: string;
    component: string;
}

export const getAIAssistantCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
    {
        order: 586,
        title: t('AI Assistant Settings'),
        href: '#ai-assistant-settings',
        icon: Bot,
        permission: 'manage-ai-assistant-settings',
        component: 'ai-assistant-settings'
    }
];
