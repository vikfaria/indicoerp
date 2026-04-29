import { Calendar as CalendarIcon } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getCalendarCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
  {
    order: 660,
    title: t('Google Calendar Settings'),
    href: '#google-calendar-settings',
    icon: CalendarIcon,
    permission: 'manage-google-calendar-settings',
    component: 'google-calendar-settings'
  }
];