import { GraduationCap } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const trainingCompanyMenu = (t: (key: string) => string) => [    
    {
        title: t('Training'),
        icon: GraduationCap,
        permission: 'manage-training',
        order: 452,
        children: [
            {
                title: t('Training Types'),
                href: route('training.training-types.index'),
                permission: 'manage-training-types',
            },
            {
                title: t('Trainers'),
                href: route('training.trainers.index'),
                permission: 'manage-trainers',
            },
            {
                title: t('Training List'),
                href: route('training.trainings.index'),
                permission: 'manage-trainings',
                activePaths: [
                    route('training.trainings.index').replace('/trainings', '/tasks')
                ],
            },
        ],
    },
];