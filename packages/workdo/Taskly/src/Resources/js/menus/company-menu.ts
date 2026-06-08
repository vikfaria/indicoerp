import { FolderKanban } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const projectCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('Project Dashboard'),
        href: route('project.dashboard.index'),
        permission: 'manage-project-dashboard',
        parent: 'dashboard',
        order: 20,
    },
    {
        title: t('Projetos'),
        icon: FolderKanban,
        permission: 'manage-project',
        parent: 'operations',
        order: 10,
        name : 'project',
        children: [
            {
                title: t('Lista de projectos'),
                href: route('project.index'),
                permission: 'manage-project',
                order: 5,
            },
            {
                title: t('Relatório de projectos'),
                href: route('project.report.index'),
                permission: 'manage-project-report',
                order: 10,
            },
            {
                title: t('Configuração'),
                href: route('project.task-stages.index'),
                permission: 'manage-task-stages',
                order: 20,
                activePaths: [route('project.bug-stages.index')],
            },
        ],
    },
];
