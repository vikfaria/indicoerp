import { GraduationCap } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const trainingCompanyMenu = (t: (key: string) => string) => [    
    {
        title: t('Formação'),
        icon: GraduationCap,
        permission: 'manage-training',
        parent: 'hrm',
        order: 1020,
        children: [
            {
                title: t('Tipos de formação'),
                href: route('training.training-types.index'),
                permission: 'manage-training-types',
            },
            {
                title: t('Formadores'),
                href: route('training.trainers.index'),
                permission: 'manage-trainers',
            },
            {
                title: t('Lista de formações'),
                href: route('training.trainings.index'),
                permission: 'manage-trainings',
                activePaths: [
                    route('training.trainings.index').replace('/trainings', '/tasks')
                ],
            },
        ],
    },
];
