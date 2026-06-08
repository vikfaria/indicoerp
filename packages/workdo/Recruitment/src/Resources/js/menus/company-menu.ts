import {    Users, Tag , Briefcase , MapPin , HelpCircle , Megaphone , MessageCircle , Calendar , MessageSquare , ClipboardCheck , FileText , CheckCircle , UserCheck } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const recruitmentCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('Recruitment Dashboard'),
        href: route('recruitment.index'),
        permission: 'manage-recruitment-dashboard',
        parent: 'dashboard',
        order: 35,
    },
    {
        title: t('Recrutamento'),
        icon: Users,
        permission: 'manage-recruitment',
        parent: 'hrm',
        order: 1000,
        children: [
            {
                title: t('Locais de trabalho'),
                href: route('recruitment.job-locations.index'),
                permission: 'manage-job-locations',
            },
            {
                title: t('Perguntas personalizadas'),
                href: route('recruitment.custom-questions.index'),
                permission: 'manage-custom-questions',
            },
            {
                title: t('Vagas publicadas'),
                href: route('recruitment.job-postings.index'),
                permission: 'manage-job-postings',
            },
            {
                title: t('Candidatos'),
                href: route('recruitment.candidates.index'),
                permission: 'manage-candidates',
            },
            {
                title: t('Rondas de entrevista'),
                href: route('recruitment.interview-rounds.index'),
                permission: 'manage-interview-rounds',
            },
            {
                title: t('Entrevistas'),
                href: route('recruitment.interviews.index'),
                permission: 'manage-interviews',
            },
            {
                title: t('Feedback de entrevista'),
                href: route('recruitment.interview-feedbacks.index'),
                permission: 'manage-interview-feedbacks',
            },
            {
                title: t('Avaliações de candidatos'),
                href: route('recruitment.candidate-assessments.index'),
                permission: 'manage-candidate-assessments',
            },
            {
                title: t('Ofertas'),
                href: route('recruitment.offers.index'),
                permission: 'manage-offers',
            },
            {
                title: t('Itens de checklist'),
                href: route('recruitment.checklist-items.index'),
                permission: 'manage-checklist-items',
            },
            {
                title: t('Onboarding de candidatos'),
                href: route('recruitment.candidate-onboardings.index'),
                permission: 'manage-candidate-onboardings',
            },
            {
                title: t('Configuração'),
                href: route('recruitment.job-types.index'),
                permission: 'manage-recruitment-system-setup',
                activePaths: [
                    route('recruitment.candidate-sources.index'),
                    route('recruitment.interview-types.index'),
                    route('recruitment.onboarding-checklists.index'),
                    route('recruitment.settings.index'),
                    route('recruitment.about-company.index'),
                    route('recruitment.application-tips.index'),
                    route('recruitment.what-happens-next.index'),
                    route('recruitment.need-help.index'),
                    route('recruitment.tracking-faq.index'),
                    route('recruitment.offer-letter-template.index')
                ],
            },
        ],
    },
];
