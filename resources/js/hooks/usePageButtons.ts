import { ReactNode } from 'react';
import { usePage } from '@inertiajs/react';
import { adminPackages } from '../utils/helpers';

interface PageButton {
    id: string;
    component: ReactNode;
    order?: number;
    route?: string;
}

export const usePageButtons = (hookName: string, data?: any, admin: boolean = false): PageButton[] => {
    try {
        const { auth } = usePage().props as any;
        // const activatedPackages = auth?.user?.activatedPackages || [];

        const activatedPackages = admin
            ? adminPackages()
            : auth?.user?.activatedPackages || [];

        const allModules = import.meta.glob('../../../packages/workdo/*/src/Resources/js/buttons/buttons.tsx', { eager: true });

        const buttons: PageButton[] = [];

        activatedPackages.forEach((packageName: string) => {
            const buttonPath = `../../../packages/workdo/${packageName}/src/Resources/js/buttons/buttons.tsx`;

            const module = allModules[buttonPath] as any;

            if (module && module[hookName]) {
                const buttonExport = module[hookName];
                if (typeof buttonExport === 'function') {
                    const buttonComponents = buttonExport(data);
                    if (Array.isArray(buttonComponents)) {
                        buttons.push(...buttonComponents);
                    } else if (buttonComponents) {
                        buttons.push(buttonComponents);
                    }
                }
            }
        });

        return buttons.sort((a, b) => (a.order || 999) - (b.order || 999));
    } catch (error) {
        return [];
    }
};