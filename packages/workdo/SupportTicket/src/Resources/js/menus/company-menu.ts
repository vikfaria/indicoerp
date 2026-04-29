import { Headphones } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const supportticketCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('Support Dashboard'),
        href: route('dashboard.support-tickets'),
        permission: 'manage-support-tickets',
        parent: 'dashboard',
        order: 140,
    },
    {
        title: t('Support Ticket'),
        icon: Headphones,
        permission: 'manage-support-tickets',
        order: 700,
        children: [
            {
                title: t('Tickets'),
                href: route('support-tickets.index'),
                permission: 'manage-support-tickets',
            },
            {
                title: t('Knowledge Base'),
                href: route('support-ticket-knowledge.index'),
                permission: 'manage-knowledge-base',
            },
            {
                title: t('FAQ'),
                href: route('support-ticket-faq.index'),
                permission: 'manage-faq',
            },
            {
                title: t('Contact'),
                href: route('support-ticket-contact.index'),
                permission: 'manage-contact',
            },
            {
                title: t('System Setup'),
                href: route('ticket-category.index'),
                permission: 'manage-ticket-categories',
                activePaths: [
                    route('knowledge-category.index'),
                    route('support-ticket.settings.brand'),
                    route('support-ticket.custom-pages.index'),
                    route('support-ticket.title-sections.index'),
                    route('support-ticket.cta-sections.index'),
                    route('support-ticket.quick-links.index'),
                    route('support-ticket.support-information.index'),
                    route('support-ticket.contact-information.index')
                ],
            },
        ],
    }
];