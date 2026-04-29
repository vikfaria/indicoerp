import { Shield } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

console.log('GoogleCaptcha: SuperAdmin settings menu loaded');
export const getGoogleCaptchaSuperAdminSettings = (t: (key: string) => string): SettingMenuItem[] => [
  {
    order: 600,
    title: t('Google reCAPTCHA Settings'),
    href: '#google-captcha-settings',
    icon: Shield,
    permission: 'manage-google-captcha-settings',
    component: 'google-captcha-settings'
  }
];