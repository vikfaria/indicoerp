import { Shield, Image, Settings, Layout, Package, FileText } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const cmsSuperAdminMenu = (t: (key: string) => string) => ({
    title: t('CMS'),
    href: route('landing-page.index'),
    permission: 'manage-landing-page',
    parent: 'dashboard',
    order: 2825,
    icon: Layout,
    children: [
        {
            title: t('Landing Page'),
            href: route('landing-page.index'),
            permission: 'manage-landing-page',
            order: 1
        },
        {
            title: t('Marketplace'),
            href: route('marketplace.settings'),
            permission: 'manage-marketplace-settings',
            order: 2
        },
        {
            title: t('Custom Pages'),
            href: route('custom-pages.index'),
            permission: 'manage-custom-pages',
            order: 3
        },
        {
            title: t('Newsletter Subscribers'),
            href: route('newsletter-subscribers.index'),
            permission: 'manage-newsletter-subscribers',
            order: 4
        }
    ]
});