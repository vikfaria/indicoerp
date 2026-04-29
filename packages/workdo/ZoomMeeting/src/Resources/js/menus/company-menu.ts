import { Video } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const zoommeetingCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('Zoom Meetings'),
        icon: Video,
        permission: 'manage-zoom-meetings',
        href: route('zoommeeting.zoom-meetings.index'),
        order: 950        
    },
];