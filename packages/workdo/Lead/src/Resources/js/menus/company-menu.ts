import { Contact } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const leadCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('CRM Dashboard'),
        href: route('lead.index'),
        permission: 'manage-crm-dashboard',
        parent: 'dashboard',
        order: 50,
    },
    {
        title: t('CRM'),
        icon: Contact,
        permission: 'manage-leads',
        order: 500,
        children: [                       
            {
                title: t('Leads'),
                href: route('lead.leads.index'),
                permission: 'manage-leads',
            },
            {
                title: t('Deals'),
                href: route('lead.deals.index'),
                permission: 'manage-deals',
            },
            {
                title: t('System Setup'),
                href: route('lead.pipelines.index'),
                permission: 'manage-pipelines',
                activePaths: [
                    route('lead.lead-stages.index'),
                    route('lead.deal-stages.index'),
                    route('lead.labels.index'),
                    route('lead.sources.index')
                ],
            },
            {
                title: t('Reports'),
                href: route('lead.reports.index'),
                permission: 'view-reports',
                children: [                       
                    {
                        title: t('Lead Reports'),
                        href: route('lead.reports.leads'),
                        permission: 'view-reports',
                    },   
                    {
                        title: t('Deal Reports'),
                        href: route('lead.reports.deals'),
                        permission: 'view-reports',
                    },  
                ]
            },   
                     
        ],
    }   
];