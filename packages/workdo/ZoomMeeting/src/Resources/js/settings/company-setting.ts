import { Video } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getZoomMeetingCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
  {
    order: 650,
    title: t('Zoom Meeting Settings'),
    href: '#zoom-meeting-settings',
    icon: Video,
    permission: 'manage-zoom-meeting-settings',
    component: 'zoom-meeting-settings'
  }
];