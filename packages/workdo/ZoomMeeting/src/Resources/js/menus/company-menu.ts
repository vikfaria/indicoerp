import { Video } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const zoommeetingCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('Reuniões Zoom'),
        icon: Video,
        permission: 'manage-zoom-meetings',
        href: route('zoommeeting.zoom-meetings.index'),
        parent: 'operations',
        order: 50
    },
];
