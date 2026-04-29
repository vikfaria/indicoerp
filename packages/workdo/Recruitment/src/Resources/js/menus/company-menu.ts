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
        title: t('Recruitment'),
        icon: Users,
        permission: 'manage-recruitment',
        order: 453,
        children: [
            {
                title: t('Job Locations'),
                href: route('recruitment.job-locations.index'),
                permission: 'manage-job-locations',
            },
            {
                title: t('Custom Questions'),
                href: route('recruitment.custom-questions.index'),
                permission: 'manage-custom-questions',
            },
            {
                title: t('Job Postings'),
                href: route('recruitment.job-postings.index'),
                permission: 'manage-job-postings',
            },
            {
                title: t('Candidates'),
                href: route('recruitment.candidates.index'),
                permission: 'manage-candidates',
            },
            {
                title: t('Interview Rounds'),
                href: route('recruitment.interview-rounds.index'),
                permission: 'manage-interview-rounds',
            },
            {
                title: t('Interviews'),
                href: route('recruitment.interviews.index'),
                permission: 'manage-interviews',
            },
            {
                title: t('Interview Feedback'),
                href: route('recruitment.interview-feedbacks.index'),
                permission: 'manage-interview-feedbacks',
            },
            {
                title: t('Candidate Assessments'),
                href: route('recruitment.candidate-assessments.index'),
                permission: 'manage-candidate-assessments',
            },
            {
                title: t('Offers'),
                href: route('recruitment.offers.index'),
                permission: 'manage-offers',
            },
            {
                title: t('Checklist Items'),
                href: route('recruitment.checklist-items.index'),
                permission: 'manage-checklist-items',
            },
            {
                title: t('Candidate Onboarding'),
                href: route('recruitment.candidate-onboardings.index'),
                permission: 'manage-candidate-onboardings',
            },
            {
                title: t('System Setup'),
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